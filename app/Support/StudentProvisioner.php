<?php

namespace App\Support;

use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Provisions a paid student: account + paid enrollment + the n8n welcome flow.
 * Shared by the admin manual-enrol form and the enroll:user command so offline
 * enrolments behave exactly like a verified webhook payment.
 */
class StudentProvisioner
{
    /**
     * @param  array{name:string,email:string,whatsapp?:?string,amount?:float|int,currency?:string,plan_type?:string,cohort?:int}  $data
     * @return array{enrollment:Enrollment,temp_password:?string,created:bool}
     */
    public function manualEnrol(array $data): array
    {
        $email = strtolower(trim($data['email']));
        $tempPassword = Str::random(14);

        $user = User::firstOrCreate(
            ['email' => $email],
            ['name' => $data['name'], 'password' => Hash::make($tempPassword)],
        );
        $created = $user->wasRecentlyCreated;

        $amount = (float) ($data['amount'] ?? 0);

        $enrollment = Enrollment::updateOrCreate(
            ['email' => $email],
            [
                'full_name'             => $data['name'],
                'whatsapp'              => $data['whatsapp'] ?? null,
                'payment_reference'     => 'MAN_' . strtoupper(Str::random(8)),
                'amount'                => $amount,
                'amount_total'          => $amount,
                'balance_due'           => 0,            // manual = paid in full
                'plan_type'             => $data['plan_type'] ?? 'full',
                'second_payment_status' => 'none',
                'cohort'                => (int) ($data['cohort'] ?? config('accelerator.cohort_number', 2)),
                'currency'              => $data['currency'] ?? 'NGN',
                'status'                => 'paid',
                'paid_at'               => now(),
                'access_suspended'      => false,
            ],
        );

        // Only hand out a temp password if we just created the account.
        $this->fireWelcome($enrollment, $created ? $tempPassword : null);

        return ['enrollment' => $enrollment, 'temp_password' => $created ? $tempPassword : null, 'created' => $created];
    }

    private function fireWelcome(Enrollment $enrollment, ?string $tempPassword): void
    {
        $url = config('services.n8n.enrollment_webhook');
        if (! $url) {
            return;
        }

        try {
            Http::post($url, [
                'event'                 => 'enrollment_finalized',
                'gateway'               => 'manual',
                'full_name'             => $enrollment->full_name,
                'email'                 => $enrollment->email,
                'phone'                 => $enrollment->whatsapp,
                'temp_password'         => $tempPassword,
                'login_url'             => url('/login'),
                'amount'                => $enrollment->amount,
                'currency'              => $enrollment->currency,
                'plan_type'             => $enrollment->plan_type,
                'amount_total'          => $enrollment->amount_total,
                'balance_due'           => $enrollment->balance_due,
                'second_payment_status' => $enrollment->second_payment_status,
                'reference'             => $enrollment->payment_reference,
                'paid_at'               => optional($enrollment->paid_at)->toIso8601String(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Manual enrolment webhook failed for ' . $enrollment->email . ': ' . $e->getMessage());
        }
    }
}
