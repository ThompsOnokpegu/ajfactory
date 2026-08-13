<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class FlutterwaveWebhookController extends Controller
{
    /**
     * Handle Flutterwave Webhook for International Payments
     */
    public function handleWebhook(Request $request)
    {
        // 1. Verify Signature
        // Ensure you add FLUTTERWAVE_SECRET_HASH to your .env
        $secretHash = config('services.flutterwave.secret_hash', env('FLUTTERWAVE_SECRET_HASH'));
        $signature = $request->header('verif-hash');

        if (!$signature || ($secretHash && $signature !== $secretHash)) {
            Log::error('Flutterwave Webhook Signature Mismatch');
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $payload = $request->all();

        // 2. Check Event Type
        if (($payload['event'] ?? '') === 'charge.completed' && ($payload['data']['status'] ?? '') === 'successful') {
            $data = $payload['data'];
            $reference = $data['tx_ref'];
            $flwId = $data['id'];

            $secretKey = config('services.flutterwave.secret_key', env('FLW_SECRET_KEY'));

            // Resource-store purchases (RES_*) — separate from enrollments.
            if (str_starts_with($reference, 'RES_')) {
                $this->handleResourcePurchase($reference, $flwId, $secretKey);
                return response()->json(['status' => 'success']);
            }

            $enrollment = Enrollment::where('payment_reference', $reference)->first();

            if ($enrollment) {
                // ---- FIRST PAYMENT (full, early-bird, or 1st installment) ----
                if ($enrollment->status !== 'paid') {

                    // Server-to-Server Verification (Critical)
                    $verify = Http::withToken($secretKey)->get("https://api.flutterwave.com/v3/transactions/{$flwId}/verify");

                    if ($verify->successful() && $verify->json('data.status') === 'successful') {
                        $verifiedData = $verify->json('data');

                        // Check Currency and Amount
                        if ($verifiedData['currency'] === $enrollment->currency && $verifiedData['amount'] >= $enrollment->amount) {

                            // A. Update Enrollment
                            $enrollment->update([
                                'status' => 'paid',
                                'paid_at' => now(),
                                'paystack_payload' => $verifiedData // Storing FLW data in the existing payload column
                            ]);

                            // A2. Installment: schedule the 2nd payment due date.
                            if ($enrollment->plan_type === 'installment' && (float)$enrollment->balance_due > 0) {
                                $enrollment->update([
                                    'second_payment_due_at' => \App\Support\Accelerator::installmentDueAt(),
                                ]);
                            }

                            // B. Create User Account
                            $tempPassword = Str::random(14);
                            User::firstOrCreate(
                                ['email' => $enrollment->email],
                                [
                                    'name' => $enrollment->full_name,
                                    'password' => Hash::make($tempPassword)
                                ]
                            );

                            // C. Trigger n8n Automation
                            $this->triggerAutomation($enrollment, $tempPassword);

                            Log::info("Flutterwave Payment Verified: {$reference}");
                        } else {
                            Log::error("Flutterwave Amount/Currency Mismatch: {$reference}");
                            $enrollment->update(['status' => 'amount_mismatch']);
                        }
                    }
                }
            } else {
                // ---- SECOND INSTALLMENT PAYMENT (paid via the n8n link, different tx_ref) ----
                $this->handleSecondInstallment($reference, $flwId, $secretKey);
            }
        }

        return response()->json(['status' => 'success']);
    }

    /** A paid-resource purchase (RES_*, USD path): verify, mark paid, deliver. */
    private function handleResourcePurchase(string $reference, $flwId, string $secretKey): void
    {
        $purchase = \App\Models\ResourcePurchase::where('payment_reference', $reference)
            ->where('status', '!=', 'paid')
            ->first();

        if (! $purchase) {
            return;
        }

        $verify = Http::withToken($secretKey)->get("https://api.flutterwave.com/v3/transactions/{$flwId}/verify");

        if (! $verify->successful() || $verify->json('data.status') !== 'successful') {
            return;
        }

        $v = $verify->json('data');

        if (($v['currency'] ?? null) === $purchase->currency && (float) ($v['amount'] ?? 0) >= (float) $purchase->amount) {
            $purchase->update(['status' => 'paid', 'paid_at' => now()]);
            \App\Support\ResourceDelivery::deliver($purchase->fresh());
        } else {
            Log::error("Flutterwave resource amount/currency mismatch: {$reference}");
            $purchase->update(['status' => 'amount_mismatch']);
        }
    }

    /**
     * Reconcile a second installment payment (USD path). The tx_ref was
     * pre-generated by installments:process and stored on the enrollment.
     */
    private function handleSecondInstallment(string $reference, $flwId, string $secretKey): void
    {
        $enrollment = Enrollment::where('second_payment_reference', $reference)
            ->where('second_payment_status', '!=', 'paid')
            ->first();

        if (! $enrollment) {
            return;
        }

        $verify = Http::withToken($secretKey)->get("https://api.flutterwave.com/v3/transactions/{$flwId}/verify");

        if (! $verify->successful() || $verify->json('data.status') !== 'successful') {
            return;
        }

        $verifiedData = $verify->json('data');

        if ($verifiedData['currency'] === $enrollment->currency && $verifiedData['amount'] >= (float)$enrollment->balance_due) {
            $enrollment->update([
                'second_payment_status' => 'paid',
                'balance_due' => 0,
                'access_suspended' => false,
            ]);

            $this->triggerInstallmentCompleted($enrollment);
        } else {
            Log::error("Flutterwave Installment Amount/Currency Mismatch: {$reference}");
        }
    }

    private function triggerInstallmentCompleted($enrollment): void
    {
        $url = config('services.n8n.installment_webhook') ?: config('services.n8n.enrollment_webhook');

        if (! $url) {
            return;
        }

        try {
            Http::post($url, [
                'event' => 'installment_completed',
                'gateway' => 'flutterwave',
                'full_name' => $enrollment->full_name,
                'email' => $enrollment->email,
                'phone' => $enrollment->whatsapp,
                'amount' => $enrollment->amount_total,
                'currency' => $enrollment->currency,
                'reference' => $enrollment->second_payment_reference,
                'original_reference' => $enrollment->payment_reference,
            ]);
        } catch (\Exception $e) {
            Log::error("Installment completion trigger failed for {$enrollment->email}: " . $e->getMessage());
        }
    }

    private function triggerAutomation($enrollment, $tempPassword)
    {
        try {
            Http::post(config('services.n8n.enrollment_webhook'), [
                'event' => 'enrollment_finalized',
                'gateway' => 'flutterwave',
                'full_name' => $enrollment->full_name,
                'email' => $enrollment->email,
                'phone' => $enrollment->whatsapp,
                'temp_password' => $tempPassword,
                'login_url' => url('/login'),
                'amount' => $enrollment->amount,
                'currency' => $enrollment->currency,
                'plan_type' => $enrollment->plan_type,
                'amount_total' => $enrollment->amount_total,
                'balance_due' => $enrollment->balance_due,
                'second_payment_status' => $enrollment->second_payment_status,
                'reference' => $enrollment->payment_reference,
                'paid_at' => $enrollment->paid_at->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            Log::error("n8n Trigger Failed for {$enrollment->payment_reference}: " . $e->getMessage());
        }
    }
}