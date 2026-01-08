<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EnrollmentController extends Controller
{
    public function handleWebhook(Request $request)
    {
        $paystackSignature = $request->header('x-paystack-signature');
        $secretKey = config('services.paystack.secret_key');

        if (!$paystackSignature || $paystackSignature !== hash_hmac('sha512', $request->getContent(), $secretKey)) {
            Log::error('Unauthorized Webhook Attempt: Invalid Signature');
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $payload = $request->all();

        if (($payload['event'] ?? '') === 'charge.success') {
            $data = $payload['data'];
            $reference = $data['reference'];

            $enrollment = Enrollment::where('payment_reference', $reference)->first();

            if ($enrollment && $enrollment->status !== 'paid') {
                $verify = Http::withToken($secretKey)
                    ->get("https://api.paystack.co/transaction/verify/{$reference}");

                if ($verify->successful() && $verify->json('data.status') === 'success') {
                    
                    $paystackAmount = $verify->json('data.amount') / 100;
                    
                    if ((float)$paystackAmount === (float)$enrollment->amount) {
                        
                        // 1. Finalize Enrollment Status
                        $enrollment->update([
                            'status' => 'paid',
                            'paid_at' => now(),
                            'paystack_payload' => $data 
                        ]);

                        // 2. Provision User for Fortify Access
                        // We generate a high-entropy temporary password
                        $tempPassword = Str::random(14);
                        
                        User::firstOrCreate(
                            ['email' => $enrollment->email],
                            [
                                'name' => $enrollment->full_name,
                                'password' => Hash::make($tempPassword),
                            ]
                        );

                        // 3. Dispatch Fulfillment via n8n
                        $this->triggerAutomation($enrollment, $tempPassword);

                    } else {
                        Log::error("Security Alert: Amount Mismatch for {$reference}");
                        $enrollment->update(['status' => 'amount_mismatch']);
                    }
                }
            }
        }

        return response()->json(['status' => 'success']);
    }

    private function triggerAutomation($enrollment, $temporaryPassword)
    {
        try {
            Http::post(config('services.n8n.enrollment_webhook'), [
                'event' => 'enrollment_finalized',
                'full_name' => $enrollment->full_name,
                'email' => $enrollment->email,
                'temp_password' => $temporaryPassword, 
                'login_url' => url('/login'),
                'amount' => $enrollment->amount,
                'reference' => $enrollment->payment_reference,
                'paid_at' => $enrollment->paid_at->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            Log::error("Critical: n8n Provisioning Trigger Failed for {$enrollment->email}: " . $e->getMessage());
        }
    }
}