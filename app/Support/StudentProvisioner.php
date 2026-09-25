<?php

namespace App\Support;

use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Support\Arr;
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
        $cohort = (int) ($data['cohort'] ?? config('accelerator.cohort_number', 2));

        $enrollment = $this->rowForManualEnrol($email, $cohort);

        $attributes = [
            'email'                 => $email,
            'full_name'             => $data['name'],
            'whatsapp'              => $data['whatsapp'] ?? null,
            'payment_reference'     => 'MAN_' . strtoupper(Str::random(8)),
            'amount'                => $amount,
            'amount_total'          => $amount,
            'balance_due'           => 0,            // manual = paid in full
            'plan_type'             => $data['plan_type'] ?? 'full',
            'second_payment_status' => 'none',
            'cohort'                => $cohort,
            'currency'              => $data['currency'] ?? 'NGN',
            'status'                => 'paid',
            'paid_at'               => now(),
            'access_suspended'      => false,
        ];

        // Re-running a manual enrol for a cohort the student is ALREADY paid into
        // (a double-submitted form, usually). The row is a real payment record, so
        // refresh only the details an admin might be correcting - never rewrite the
        // reference, amount or paid_at over a payment that actually happened.
        if ($enrollment->exists && $enrollment->status === 'paid') {
            $attributes = Arr::only($attributes, ['full_name', 'whatsapp']);
        }

        $enrollment->fill($attributes)->save();

        // Only hand out a temp password if we just created the account.
        $this->fireWelcome($enrollment, $created ? $tempPassword : null);

        // Offline sale, so no browser session - goes to Meta as an "other" source
        // Purchase. Still worth sending: it matches on email/phone and feeds the
        // buyer signal Meta optimises on.
        app(MetaConversions::class)->purchase($enrollment->fresh());

        return ['enrollment' => $enrollment, 'temp_password' => $created ? $tempPassword : null, 'created' => $created];
    }

    /**
     * Which enrollment row should a manual enrolment write to?
     *
     * NOT `updateOrCreate(['email' => $email])`. That resolves through an unfiltered
     * `first()`, so it grabs whichever row is oldest - which for anyone who abandoned
     * a checkout is a stale `pending` row, and for a returning student is their
     * PREVIOUS cohort. Writing this payload over either one is destructive: it
     * rewrites the payment reference, amount and paid_at of a real payment, moves
     * that row's cohort, and zeroes an installment balance that is still owed.
     * Moving the cohort is the worse half - it drags a mid-course student onto
     * another cohort's module-01 date floor.
     *
     * So:
     *   - already paid into THIS cohort -> reuse that row (a re-run; the caller
     *     keeps its payment fields intact rather than minting a duplicate),
     *   - paid into a DIFFERENT cohort -> a previous enrolment. Leave it alone and
     *     mint a new row; `Enrollment::currentFor()` will pick the newer one,
     *   - no paid row at all -> reuse their latest unpaid checkout attempt. They've
     *     now paid, so converting it is right, and it stops the abandoned-cart
     *     segment chasing someone who already bought.
     */
    private function rowForManualEnrol(string $email, int $cohort): Enrollment
    {
        $paidThisCohort = Enrollment::where('email', $email)
            ->where('status', 'paid')
            ->where('cohort', $cohort)
            ->orderByDesc('id')
            ->first();

        if ($paidThisCohort) {
            return $paidThisCohort;
        }

        if (Enrollment::where('email', $email)->where('status', 'paid')->exists()) {
            return new Enrollment();
        }

        return Enrollment::where('email', $email)->orderByDesc('id')->first() ?? new Enrollment();
    }

    /**
     * Approve an existing PENDING enrollment that was paid offline (bank transfer).
     * Unlike manualEnrol (which mints a fresh paid-in-full row), this finalizes the
     * row the student already created at checkout — keeping its plan, amount, and
     * balance — then provisions the account + welcome, mirroring the webhook's
     * first-payment path.
     *
     * @return array{enrollment:Enrollment,temp_password:?string,created:bool}
     */
    public function approve(Enrollment $enrollment): array
    {
        $email = strtolower(trim($enrollment->email));
        $tempPassword = Str::random(14);

        $user = User::firstOrCreate(
            ['email' => $email],
            ['name' => $enrollment->full_name, 'password' => Hash::make($tempPassword)],
        );
        $created = $user->wasRecentlyCreated;

        $updates = [
            'status'           => 'paid',
            'paid_at'          => now(),
            'access_suspended' => false,
        ];

        // Installment paid offline: schedule the 2nd payment if it isn't already,
        // so installments:process picks it up on the due date. Keep balance_due.
        if ($enrollment->plan_type === 'installment'
            && (float) $enrollment->balance_due > 0
            && ! $enrollment->second_payment_due_at) {
            $updates['second_payment_due_at'] = Accelerator::installmentDueAt();
            $updates['second_payment_status'] = 'pending';
        }

        $enrollment->update($updates);
        $enrollment = $enrollment->fresh();

        $this->fireWelcome($enrollment, $created ? $tempPassword : null);

        // The row came through checkout, so it carries the buyer's meta_context.
        app(MetaConversions::class)->purchase($enrollment);

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
