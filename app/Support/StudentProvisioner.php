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

    /**
     * Re-fire the welcome automation for one student — for when n8n failed or
     * dropped the original (the student never got their credentials).
     *
     * The original temp password is Hash::make()'d at provision time, so the
     * plaintext is NOT recoverable and cannot be replayed. Re-sending therefore
     * issues a NEW temporary password by default; pass false to re-send the
     * welcome without touching the account (the email's password block will then
     * be empty, so only do that for someone who can already log in).
     *
     * @return array{ok:bool,temp_password:?string}
     */
    public function resendWelcome(Enrollment $enrollment, bool $issueNewPassword = true): array
    {
        $tempPassword = null;

        if ($issueNewPassword) {
            $user = User::where('email', $enrollment->email)->first();
            if ($user) {
                $tempPassword = Str::random(14);
                $user->update(['password' => Hash::make($tempPassword)]);
            }
        }

        $ok = $this->fireWelcome($enrollment, $tempPassword, ['resent' => true]);

        return ['ok' => $ok, 'temp_password' => $tempPassword];
    }

    /**
     * Returns true only when n8n accepted the POST. A non-2xx used to be
     * swallowed silently, which would make an admin "resend" button report
     * success for a send that never happened.
     */
    private function fireWelcome(Enrollment $enrollment, ?string $tempPassword, array $extra = []): bool
    {
        $url = config('services.n8n.enrollment_webhook');
        if (! $url) {
            Log::warning('Enrolment webhook skipped — no n8n URL configured. ' . $enrollment->email);
            return false;
        }

        try {
            $response = Http::timeout(45)->post($url, array_merge([
                'event'                 => 'enrollment_finalized',
                // Admin-initiated either way — keep the value n8n already routes
                // on rather than inventing a new one it might not match.
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
            ], $extra));

            if (! $response->successful()) {
                Log::error("Enrolment webhook rejected for {$enrollment->email}: HTTP {$response->status()} {$response->body()}");
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('Enrolment webhook failed for ' . $enrollment->email . ': ' . $e->getMessage());
            return false;
        }
    }
}
