<?php

use App\Models\Enrollment;
use App\Support\StudentProvisioner;
use Illuminate\Support\Facades\Http;

/*
 * Which enrollment row a manual enrolment writes to.
 *
 * It used to be `Enrollment::updateOrCreate(['email' => $email])`, which resolves
 * through an unfiltered `first()` and therefore grabs the OLDEST row for that email.
 * For anyone who abandoned a checkout that's a stale pending row; for a returning
 * student it's their previous cohort. Writing a paid-in-full payload over either is
 * destructive - it rewrites a real payment reference, moves that row's cohort, and
 * zeroes an installment balance that is still owed.
 */

beforeEach(function () {
    Http::fake();   // the welcome webhook + Meta purchase
});

function meRow(string $email, string $status, int $cohort, array $extra = []): Enrollment
{
    return Enrollment::create(array_merge([
        'full_name' => 'Chidi Okonkwo',
        'email' => $email,
        'payment_reference' => strtoupper($status) . '_' . uniqid(),
        'amount' => 79000,
        'amount_total' => 79000,
        'status' => $status,
        'cohort' => $cohort,
        'paid_at' => $status === 'paid' ? now()->subMonths(3) : null,
    ], $extra));
}

function meEnrol(string $email, int $cohort, array $extra = []): array
{
    return app(StudentProvisioner::class)->manualEnrol(array_merge([
        'name' => 'Chidi Okonkwo',
        'email' => $email,
        'amount' => 50000,
        'cohort' => $cohort,
    ], $extra));
}

it('never overwrites a paid enrollment from an earlier cohort', function () {
    $email = 'returning@test.dev';
    $cohort2 = meRow($email, 'paid', 2);
    $originalRef = $cohort2->payment_reference;

    meEnrol($email, 3);

    // The Cohort 2 payment record survives intact...
    $cohort2->refresh();
    expect($cohort2->cohort)->toBe(2)
        ->and($cohort2->payment_reference)->toBe($originalRef)
        ->and((float) $cohort2->amount)->toBe(79000.0);

    // ...and Cohort 3 is a separate row, which is the one they're now current in.
    expect(Enrollment::where('email', $email)->count())->toBe(2)
        ->and(Enrollment::currentFor($email)->cohort)->toBe(3);
});

it('does not drag a mid-course student onto another cohort date floor', function () {
    // The specific damage of overwriting: cohort moves under someone already running,
    // and Accelerator::startFloorFor() then re-locks their module 01.
    $email = 'midcourse@test.dev';
    $running = meRow($email, 'paid', 2);

    meEnrol($email, 3);

    expect($running->fresh()->cohort)->toBe(2);
});

it('does not hijack an abandoned pending row when a paid row exists', function () {
    $email = 'chidi@test.dev';
    $abandoned = meRow($email, 'pending', 3);   // created first, lowest id
    $paid = meRow($email, 'paid', 2);

    meEnrol($email, 3);

    // The stale row is untouched - it must not be flipped to paid and become
    // "most recently paid", which would strand the real row's progress.
    expect($abandoned->fresh()->status)->toBe('pending')
        ->and(Enrollment::currentFor($email)->id)->not->toBe($abandoned->id);
});

it('reuses the latest unpaid checkout attempt when nothing is paid yet', function () {
    $email = 'chidi@test.dev';
    meRow($email, 'pending', 3);
    $latest = meRow($email, 'pending', 3);

    meEnrol($email, 3);

    // Converted in place, so no duplicate row and the abandoned-cart segment
    // stops chasing someone who has now bought.
    expect(Enrollment::where('email', $email)->count())->toBe(2)
        ->and($latest->fresh()->status)->toBe('paid')
        ->and(Enrollment::currentFor($email)->id)->toBe($latest->id);
});

it('creates a single row for a brand new student', function () {
    meEnrol('brand@new.dev', 3);

    $rows = Enrollment::where('email', 'brand@new.dev')->get();
    expect($rows)->toHaveCount(1)
        ->and($rows->first()->status)->toBe('paid')
        ->and($rows->first()->cohort)->toBe(3);
});

it('does not mint a duplicate when the form is submitted twice', function () {
    $email = 'double@test.dev';

    meEnrol($email, 3);
    meEnrol($email, 3);

    expect(Enrollment::where('email', $email)->where('status', 'paid')->count())->toBe(1);
});

it('keeps the original payment record when re-run over a card payment', function () {
    $email = 'paidbycard@test.dev';
    $card = meRow($email, 'paid', 3, ['payment_reference' => 'PSK_REAL_REF', 'amount' => 79000]);

    // An admin manually enrolling someone who already paid by card for this cohort:
    // their name is corrected, the payment trail is not rewritten.
    meEnrol($email, 3, ['name' => 'Chidi A. Okonkwo', 'amount' => 1]);

    $card->refresh();
    expect($card->payment_reference)->toBe('PSK_REAL_REF')
        ->and((float) $card->amount)->toBe(79000.0)
        ->and($card->full_name)->toBe('Chidi A. Okonkwo')
        ->and(Enrollment::where('email', $email)->count())->toBe(1);
});

it('does not zero an installment balance that is still owed', function () {
    $email = 'installment@test.dev';
    $owing = meRow($email, 'paid', 2, [
        'plan_type' => 'installment',
        'balance_due' => 42000,
        'second_payment_status' => 'pending',
    ]);

    meEnrol($email, 3);

    expect((float) $owing->fresh()->balance_due)->toBe(42000.0)
        ->and($owing->fresh()->second_payment_status)->toBe('pending');
});
