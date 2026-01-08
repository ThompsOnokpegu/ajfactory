<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EnrollmentController extends Controller
{
    /**
     * Paystack Webhook Handler
     * Verifies the money is in the bank before updating the DB.
     */
    public function handleWebhook(Request $request)
    {
        $paystackSignature = $request->header('x-paystack-signature');
        $secretKey = config('services.paystack.secret_key');

        // 1. Critical: Verify Signature
        if (!$paystackSignature || $paystackSignature !== hash_hmac('sha512', $request->getContent(), $secretKey)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $payload = $request->all();

        // 2. Only act on success
        if ($payload['event'] === 'charge.success') {
            $data = $payload['data'];
            $reference = $data['reference'];

            $enrollment = Enrollment::where('payment_reference', $reference)->first();

            if ($enrollment && $enrollment->status !== 'paid') {
                
                // 3. Server-to-Server Verification (Double Check)
                $verify = Http::withToken($secretKey)
                    ->get("https://api.paystack.co/transaction/verify/{$reference}");

                if ($verify->successful() && $verify->json('data.status') === 'success') {
                    
                    // 4. Update status and save the RAW payload for auditing
                    $enrollment->update([
                        'status' => 'paid',
                        'paid_at' => now(),
                        'paystack_payload' => $data // The entire JSON for future audit
                    ]);

                    // 5. Trigger n8n Automation
                    $this->triggerAutomation($enrollment);
                }
            }
        }

        return response()->json(['status' => 'success']);
    }

    private function triggerAutomation($enrollment)
    {
        try {
            Http::post(config('services.n8n.enrollment_webhook'), [
                'event' => 'enrollment_finalized',
                'full_name' => $enrollment->full_name,
                'email' => $enrollment->email,
                'whatsapp' => $enrollment->whatsapp,
                'reference' => $enrollment->payment_reference,
                'amount' => $enrollment->amount,
                'paid_at' => $enrollment->paid_at->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            Log::error("n8n Trigger Failed for {$enrollment->payment_reference}: " . $e->getMessage());
        }
    }
}