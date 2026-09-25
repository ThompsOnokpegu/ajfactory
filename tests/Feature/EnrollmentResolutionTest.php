<?php

use App\Models\Checkpoint;
use App\Models\Enrollment;
use App\Models\LiveAttendance;
use App\Models\User;
use App\Support\Progress;
use Livewire\Volt\Volt;

/*
 * The "shipped 3 modules, admin says 0/9" bug.
 *
 * Checkout writes a fresh `pending` enrollment on every attempt and only the row
 * matching the payment reference is flipped to `paid`. A student who abandoned one
 * checkout therefore owns an OLDER pending row. The dashboard used to resolve them
 * with an unfiltered `->first()`, which returns that older row, so every checkpoint
 * they submitted was attached to a row the admin progress screen cannot see.
 *
 * These tests pin both halves: the resolver always lands on the paid row, and the
 * reconcile command moves progress already written against the wrong one.
 */

function erCurriculum(): void
{
    config(['curriculum' => [
        'core' => [
            ['id' => 'module-01', 'title' => 'Module 01', 'videos' => [['id' => 'm1v1', 'title' => 'V1', 'video_id' => 'x', 'duration' => '1:00']]],
            ['id' => 'module-02', 'title' => 'Module 02', 'videos' => [['id' => 'm2v1', 'title' => 'V1', 'video_id' => 'x', 'duration' => '1:00']]],
        ],
        'live' => [
            ['id' => 'live-01', 'title' => 'Live 1', 'release_at' => '2020-01-01 00:00:00',
             'videos' => [['id' => 'l1v1', 'title' => 'Rec', 'video_id' => 'y', 'duration' => '1:00']]],
        ],
    ]]);
}

function erRow(string $email, string $status, int $cohort = 3, ?string $paidAt = null): Enrollment
{
    return Enrollment::create([
        'full_name' => 'Chidi Okonkwo',
        'email' => $email,
        'payment_reference' => 'R_' . uniqid(),
        'amount' => 79000,
        'status' => $status,
        'cohort' => $cohort,
        'paid_at' => $status === 'paid' ? ($paidAt ?? now()) : null,
    ]);
}

/* -------------------------------------------------------------- resolution -- */

it('resolves the paid row, not an older abandoned checkout', function () {
    $email = 'chidi@test.dev';
    $abandoned = erRow($email, 'pending');      // created first, so lower id
    $paid = erRow($email, 'paid');

    expect(Enrollment::where('email', $email)->first()->id)->toBe($abandoned->id)  // the old bug
        ->and(Enrollment::currentFor($email)->id)->toBe($paid->id);               // the fix
});

it('resolves the most recent paid row for a returning student', function () {
    $email = 'returning@test.dev';
    $cohort2 = erRow($email, 'paid', 2, '2026-06-01 10:00:00');
    $cohort3 = erRow($email, 'paid', 3, '2026-09-01 10:00:00');

    expect(Enrollment::currentFor($email)->id)->toBe($cohort3->id)
        ->and(Enrollment::currentFor($email)->cohort)->toBe(3);
});

it('returns nothing when no row is paid', function () {
    erRow('broke@test.dev', 'pending');

    expect(Enrollment::currentFor('broke@test.dev'))->toBeNull()
        ->and(Enrollment::currentFor(null))->toBeNull();
});

it('writes a new checkpoint against the paid row, so admin can see it', function () {
    erCurriculum();
    $email = 'chidi@test.dev';
    erRow($email, 'pending');
    $paid = erRow($email, 'paid');
    User::factory()->create(['email' => $email, 'name' => 'Chidi Okonkwo']);

    $this->actingAs(User::where('email', $email)->first());

    Volt::test('dashboard.terminal')
        ->set('proofUrl', 'https://loom.test/proof')
        ->call('submitCheckpoint')
        ->assertHasNoErrors();

    expect(Checkpoint::where('enrollment_id', $paid->id)->count())->toBe(1);
});

/* --------------------------------------------------------------- reconcile -- */

it('moves progress off an abandoned row onto the paid one', function () {
    erCurriculum();
    $email = 'chidi@test.dev';
    $abandoned = erRow($email, 'pending');
    $paid = erRow($email, 'paid');

    // What the old code wrote: everything against the pending row.
    Checkpoint::create(['enrollment_id' => $abandoned->id, 'module_id' => 'module-01', 'status' => 'approved',
        'submitted_at' => now()->subDay(), 'reviewed_at' => now()]);
    Checkpoint::create(['enrollment_id' => $abandoned->id, 'module_id' => 'module-02', 'status' => 'submitted',
        'submitted_at' => now()]);
    LiveAttendance::create(['enrollment_id' => $abandoned->id, 'session_key' => 'live-01', 'attended_at' => now()]);
    $abandoned->update(['completed_lessons' => ['m1v1', 'm2v1']]);

    // Before: the student is invisible to the progress screen.
    expect(Progress::forCohort(3)->firstWhere('enrollment_id', $paid->id)['approved'])->toBe(0);

    $this->artisan('enrollments:reconcile')->assertSuccessful();

    expect(Checkpoint::where('enrollment_id', $paid->id)->count())->toBe(2)
        ->and(Checkpoint::where('enrollment_id', $abandoned->id)->count())->toBe(0)
        ->and(LiveAttendance::where('enrollment_id', $paid->id)->count())->toBe(1)
        ->and($paid->fresh()->completed_lessons)->toEqualCanonicalizing(['m1v1', 'm2v1'])
        ->and(Progress::forCohort(3)->firstWhere('enrollment_id', $paid->id)['approved'])->toBe(1);
});

it('changes nothing on a dry run', function () {
    erCurriculum();
    $email = 'chidi@test.dev';
    $abandoned = erRow($email, 'pending');
    $paid = erRow($email, 'paid');
    Checkpoint::create(['enrollment_id' => $abandoned->id, 'module_id' => 'module-01', 'status' => 'approved',
        'submitted_at' => now()->subDay(), 'reviewed_at' => now()]);

    $this->artisan('enrollments:reconcile', ['--dry-run' => true])->assertSuccessful();

    expect(Checkpoint::where('enrollment_id', $abandoned->id)->count())->toBe(1)
        ->and(Checkpoint::where('enrollment_id', $paid->id)->count())->toBe(0);
});

it('keeps the better checkpoint when both rows have one for the same module', function () {
    erCurriculum();
    $email = 'chidi@test.dev';
    $abandoned = erRow($email, 'pending');
    $paid = erRow($email, 'paid');

    // Approved on the orphan, only submitted on the current row: approval must win.
    Checkpoint::create(['enrollment_id' => $abandoned->id, 'module_id' => 'module-01', 'status' => 'approved',
        'submitted_at' => now()->subDays(2), 'reviewed_at' => now()->subDay()]);
    Checkpoint::create(['enrollment_id' => $paid->id, 'module_id' => 'module-01', 'status' => 'submitted',
        'submitted_at' => now()]);

    $this->artisan('enrollments:reconcile')->assertSuccessful();

    $kept = Checkpoint::where('enrollment_id', $paid->id)->where('module_id', 'module-01')->get();
    expect($kept)->toHaveCount(1)
        ->and($kept->first()->status)->toBe('approved');
});

it('does not demote an approval that is already on the current row', function () {
    erCurriculum();
    $email = 'chidi@test.dev';
    $abandoned = erRow($email, 'pending');
    $paid = erRow($email, 'paid');

    Checkpoint::create(['enrollment_id' => $abandoned->id, 'module_id' => 'module-01', 'status' => 'rejected',
        'submitted_at' => now()->subDays(2), 'reviewed_at' => now()->subDay()]);
    Checkpoint::create(['enrollment_id' => $paid->id, 'module_id' => 'module-01', 'status' => 'approved',
        'submitted_at' => now()->subDay(), 'reviewed_at' => now()]);

    $this->artisan('enrollments:reconcile')->assertSuccessful();

    expect(Checkpoint::where('enrollment_id', $paid->id)->where('module_id', 'module-01')->first()->status)
        ->toBe('approved');
});

it('is safe to run twice', function () {
    erCurriculum();
    $email = 'chidi@test.dev';
    $abandoned = erRow($email, 'pending');
    $paid = erRow($email, 'paid');
    Checkpoint::create(['enrollment_id' => $abandoned->id, 'module_id' => 'module-01', 'status' => 'approved',
        'submitted_at' => now()->subDay(), 'reviewed_at' => now()]);
    LiveAttendance::create(['enrollment_id' => $abandoned->id, 'session_key' => 'live-01', 'attended_at' => now()]);

    $this->artisan('enrollments:reconcile')->assertSuccessful();
    $this->artisan('enrollments:reconcile')->assertSuccessful();

    expect(Checkpoint::where('enrollment_id', $paid->id)->count())->toBe(1)
        ->and(LiveAttendance::where('enrollment_id', $paid->id)->count())->toBe(1);
});

it('never deletes an enrollment row - abandoned carts are a sales segment', function () {
    erCurriculum();
    $email = 'chidi@test.dev';
    $abandoned = erRow($email, 'pending');
    erRow($email, 'paid');
    Checkpoint::create(['enrollment_id' => $abandoned->id, 'module_id' => 'module-01', 'status' => 'approved',
        'submitted_at' => now()->subDay(), 'reviewed_at' => now()]);

    $this->artisan('enrollments:reconcile')->assertSuccessful();

    expect(Enrollment::find($abandoned->id))->not->toBeNull()
        ->and(Enrollment::where('email', $email)->count())->toBe(2);
});

it('leaves a student alone when they have no paid row', function () {
    erCurriculum();
    $a = erRow('nocash@test.dev', 'pending');
    erRow('nocash@test.dev', 'pending');
    Checkpoint::create(['enrollment_id' => $a->id, 'module_id' => 'module-01', 'status' => 'submitted',
        'submitted_at' => now()]);

    $this->artisan('enrollments:reconcile')->assertSuccessful();

    expect(Checkpoint::where('enrollment_id', $a->id)->count())->toBe(1);
});
