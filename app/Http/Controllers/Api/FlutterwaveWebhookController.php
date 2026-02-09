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

            $enrollment = Enrollment::where('payment_reference', $reference)->first();

            // 3. Prevent Duplicate Processing
            if ($enrollment && $enrollment->status !== 'paid') {
                
                // 4. Server-to-Server Verification (Critical)
                $secretKey = config('services.flutterwave.secret_key', env('FLW_SECRET_KEY'));
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
        }

        return response()->json(['status' => 'success']);
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
                'reference' => $enrollment->payment_reference,
                'paid_at' => $enrollment->paid_at->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            Log::error("n8n Trigger Failed for {$enrollment->payment_reference}: " . $e->getMessage());
        }
    }
}