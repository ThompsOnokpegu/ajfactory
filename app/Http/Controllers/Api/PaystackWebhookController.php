<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaystackWebhookController extends Controller
{
    /**
     * Handle Paystack Webhook
     */
    public function handle(Request $request)
    {
        // 1. Verify Signature (Security)
        $paystackSignature = $request->header('x-paystack-signature');
        $secretKey = config('services.paystack.secret_key');

        if (!$paystackSignature || $paystackSignature !== hash_hmac('sha512', $request->getContent(), $secretKey)) {
            Log::error('Invalid Paystack Webhook Signature');
            return response()->json(['status' => 'invalid signature'], 400);
        }

        $event = $request->all();

        // 2. Only process successful charges
        if ($event['event'] === 'charge.success') {
            $data = $event['data'];
            $reference = $data['reference'];
            
            // 3. Double Check with Paystack API (Server-to-Server)
            $verification = Http::withToken($secretKey)
                ->get("https://api.paystack.co/transaction/verify/{$reference}");

            if ($verification->successful() && $verification->json('data.status') === 'success') {
                
                $customerEmail = $data['customer']['email'];
                $amountPaid = $data['amount'] / 100; // Convert kobo to Naira

                // 4. Trigger n8n Automation
                // This sends the signal to n8n to send onboarding emails, give course access, etc.
                try {
                    Http::post(config('services.n8n.payment_webhook_url'), [
                        'source' => 'accelerator_payment',
                        'email' => $customerEmail,
                        'amount' => $amountPaid,
                        'reference' => $reference,
                        'timestamp' => now()->toIso8601String(),
                        'metadata' => $data['metadata'] ?? []
                    ]);

                    Log::info("Accelerator Payment Verified: {$customerEmail} - Ref: {$reference}");
                } catch (\Exception $e) {
                    Log::error("n8n Handshake Failed for payment {$reference}: " . $e->getMessage());
                }
            }
        }

        return response()->json(['status' => 'success'], 200);
    }
}