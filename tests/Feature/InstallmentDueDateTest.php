<?php

use App\Models\Enrollment;
use App\Support\Accelerator;
use Carbon\Carbon;

function installmentEnrollment(array $overrides = []): Enrollment
{
    return Enrollment::create(array_merge([
        'full_name' => 'Ada Builder',
        'email' => 'ada'.uniqid().'@example.com',
        'payment_reference' => 'TEST_'.uniqid(),
        'amount' => 42000,
        'amount_total' => 84000,
        'balance_due' => 42000,
        'plan_type' => 'installment',
        'second_payment_status' => 'pending',
        'cohort' => 2,
        'status' => 'paid',
    ], $overrides));
}

beforeEach(function () {
    config([
        'accelerator.cohort_starts_at' => '2026-07-31',
        'accelerator.installment_due_days' => 21,
        'accelerator.installment_grace_hours' => 24,
    ]);
});

afterEach(fn () => Carbon::setTestNow());

it('counts the window from the cohort start for someone who paid early', function () {
    // Paid 10 days before the cohort opened — the window must start at the cohort
    // start, not at payment, or enrolling early costs you time.
    $due = Accelerator::installmentDueAt(Carbon::parse('2026-07-21 10:00', 'Africa/Lagos'));

    expect($due->toDateString())->toBe('2026-08-21');
});

it('gives a late enroller the full window from their payment', function () {
    // Anchoring purely to the cohort start would leave this student ~6 days.
    $due = Accelerator::installmentDueAt(Carbon::parse('2026-08-15 10:00', 'Africa/Lagos'));

    expect($due->toDateString())->toBe('2026-09-05');
});

it('gives everyone at least the configured window', function () {
    foreach (['2026-07-01', '2026-07-31', '2026-08-20'] as $paid) {
        $paidAt = Carbon::parse($paid.' 09:00', 'Africa/Lagos');

        expect(Accelerator::installmentDueAt($paidAt)->greaterThanOrEqualTo($paidAt->copy()->addDays(21)))
            ->toBeTrue("paid {$paid} got less than the full window");
    }
});

it('extends the old 14-day deadline of an early enroller', function () {
    Carbon::setTestNow('2026-08-13 09:00');

    $e = installmentEnrollment([
        'paid_at' => Carbon::parse('2026-07-21 10:00'),
        'second_payment_due_at' => Carbon::parse('2026-08-04 10:00'), // the old 14-day stamp
    ]);

    $this->artisan('installments:realign')->assertSuccessful();

    expect($e->fresh()->second_payment_due_at->toDateString())->toBe('2026-08-21');
});

it('never shortens a deadline a student already has', function () {
    Carbon::setTestNow('2026-08-13 09:00');

    $generous = Carbon::parse('2026-12-01 10:00');
    $e = installmentEnrollment([
        'paid_at' => Carbon::parse('2026-07-21 10:00'),
        'second_payment_due_at' => $generous,
    ]);

    $this->artisan('installments:realign')->assertSuccessful();

    expect($e->fresh()->second_payment_due_at->toDateString())->toBe('2026-12-01');
});

it('restores access and re-arms the link when the deadline moves into the future', function () {
    Carbon::setTestNow('2026-08-13 09:00');

    $e = installmentEnrollment([
        'paid_at' => Carbon::parse('2026-07-21 10:00'),
        'second_payment_due_at' => Carbon::parse('2026-08-04 10:00'),
        'second_payment_status' => 'link_sent',
        'installment_reminder_sent_at' => Carbon::parse('2026-08-04 10:05'),
        'access_suspended' => true,
    ]);

    $this->artisan('installments:realign')->assertSuccessful();

    $e->refresh();
    expect($e->access_suspended)->toBeFalse()
        ->and($e->second_payment_status)->toBe('pending')
        ->and($e->installment_reminder_sent_at)->toBeNull()
        ->and($e->second_payment_due_at->toDateString())->toBe('2026-08-21');
});

it('leaves a genuinely overdue student suspended', function () {
    // Paid well into the cohort, so even the recomputed date is in the past.
    Carbon::setTestNow('2026-10-01 09:00');

    $e = installmentEnrollment([
        'paid_at' => Carbon::parse('2026-08-20 10:00'),
        'second_payment_due_at' => Carbon::parse('2026-09-10 10:00'),
        'second_payment_status' => 'link_sent',
        'access_suspended' => true,
    ]);

    $this->artisan('installments:realign')->assertSuccessful();

    $e->refresh();
    expect($e->access_suspended)->toBeTrue()
        ->and($e->second_payment_status)->toBe('link_sent');
});

it('writes nothing on a dry run', function () {
    Carbon::setTestNow('2026-08-13 09:00');

    $e = installmentEnrollment([
        'paid_at' => Carbon::parse('2026-07-21 10:00'),
        'second_payment_due_at' => Carbon::parse('2026-08-04 10:00'),
    ]);

    $this->artisan('installments:realign', ['--dry-run' => true])->assertSuccessful();

    expect($e->fresh()->second_payment_due_at->toDateString())->toBe('2026-08-04');
});

it('ignores students who have already cleared their balance', function () {
    Carbon::setTestNow('2026-08-13 09:00');

    $e = installmentEnrollment([
        'paid_at' => Carbon::parse('2026-07-21 10:00'),
        'second_payment_due_at' => Carbon::parse('2026-08-04 10:00'),
        'second_payment_status' => 'paid',
        'balance_due' => 0,
    ]);

    $this->artisan('installments:realign')->assertSuccessful();

    expect($e->fresh()->second_payment_due_at->toDateString())->toBe('2026-08-04');
});

it('is idempotent', function () {
    Carbon::setTestNow('2026-08-13 09:00');

    $e = installmentEnrollment([
        'paid_at' => Carbon::parse('2026-07-21 10:00'),
        'second_payment_due_at' => Carbon::parse('2026-08-04 10:00'),
    ]);

    $this->artisan('installments:realign')->assertSuccessful();
    $first = $e->fresh()->second_payment_due_at;

    $this->artisan('installments:realign')->assertSuccessful();

    expect($e->fresh()->second_payment_due_at->toDateString())->toBe($first->toDateString());
});
