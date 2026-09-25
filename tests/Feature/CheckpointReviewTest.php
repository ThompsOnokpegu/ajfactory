<?php

use App\Models\Checkpoint;
use App\Models\Enrollment;
use App\Models\User;
use App\Support\Progress;
use Livewire\Volt\Volt;

/*
 * The admin checkpoint screen, and who is left off a ranked view.
 *
 * The reviewed list used to be a hard limit(15), so anything older than the last
 * fifteen decisions could not be reached at all.
 */

function crAdmin(): User
{
    return User::factory()->create(['is_admin' => true]);
}

function crStudent(string $name, int $cohort = 3, string $email = null): Enrollment
{
    return Enrollment::create([
        'full_name' => $name,
        'email' => $email ?? (str($name)->slug() . '_' . uniqid() . '@test.dev'),
        'payment_reference' => 'P_' . uniqid(),
        'amount' => 79000,
        'status' => 'paid',
        'cohort' => $cohort,
        'paid_at' => now(),
    ]);
}

function crCheckpoint(Enrollment $e, string $module, string $status, ?string $reviewedAt = null): Checkpoint
{
    return Checkpoint::create([
        'enrollment_id' => $e->id,
        'module_id' => $module,
        'status' => $status,
        'proof_url' => 'https://loom.test/x',
        'note' => $status === 'rejected' ? 'Link is private.' : null,
        'submitted_at' => now()->subDay(),
        'reviewed_at' => $status === 'submitted' ? null : ($reviewedAt ? \Carbon\Carbon::parse($reviewedAt) : now()),
    ]);
}

/* ------------------------------------------------------- reviewed history -- */

it('reaches further back than the last 15 decisions', function () {
    $this->actingAs(crAdmin());

    $e = crStudent('Chidi Okonkwo');
    foreach (range(1, 30) as $i) {
        crCheckpoint($e, "module-{$i}", 'approved', now()->subDays(40 - $i)->toDateTimeString());
    }

    $c = Volt::test('admin.checkpoints');

    // All 30 are reachable, not just a truncated 15.
    expect($c->viewData('reviewed')->total())->toBe(30);

    // And the oldest is on a later page rather than lost.
    $c->call('gotoPage', 2);
    expect($c->viewData('reviewed')->count())->toBeGreaterThan(0);
});

it('filters the reviewed list by decision', function () {
    $this->actingAs(crAdmin());

    $e = crStudent('Chidi Okonkwo');
    crCheckpoint($e, 'module-01', 'approved');
    crCheckpoint($e, 'module-02', 'rejected');

    $c = Volt::test('admin.checkpoints')->set('status', 'rejected');

    expect($c->viewData('reviewed')->total())->toBe(1)
        ->and($c->viewData('reviewed')->first()->module_id)->toBe('module-02');
});

it('finds a student by name or email', function () {
    $this->actingAs(crAdmin());

    $wanted = crStudent('Ada Nwosu', 3, 'ada@test.dev');
    $other = crStudent('Chidi Okonkwo');
    crCheckpoint($wanted, 'module-01', 'approved');
    crCheckpoint($other, 'module-01', 'approved');

    expect(Volt::test('admin.checkpoints')->set('search', 'Ada')->viewData('reviewed')->total())->toBe(1)
        ->and(Volt::test('admin.checkpoints')->set('search', 'ada@test.dev')->viewData('reviewed')->total())->toBe(1);
});

it('filters by cohort and by module', function () {
    $this->actingAs(crAdmin());

    $c2 = crStudent('Old Hand', 2);
    $c3 = crStudent('New Hand', 3);
    crCheckpoint($c2, 'module-01', 'approved');
    crCheckpoint($c3, 'module-02', 'approved');

    expect(Volt::test('admin.checkpoints')->set('cohort', '2')->viewData('reviewed')->total())->toBe(1)
        ->and(Volt::test('admin.checkpoints')->set('module', 'module-02')->viewData('reviewed')->total())->toBe(1);
});

it('keeps pending submissions out of the reviewed list and never truncates them', function () {
    $this->actingAs(crAdmin());

    $e = crStudent('Chidi Okonkwo');
    foreach (range(1, 20) as $i) {
        crCheckpoint($e, "module-{$i}", 'submitted');
    }

    $c = Volt::test('admin.checkpoints');

    expect($c->viewData('pending'))->toHaveCount(20)
        ->and($c->viewData('reviewed')->total())->toBe(0);
});

/* ------------------------------------------------------ correcting a call -- */

it('re-notifies the student when a decision is reversed', function () {
    $this->actingAs(crAdmin());

    $e = crStudent('Chidi Okonkwo');
    $cp = crCheckpoint($e, 'module-01', 'approved', '2026-09-01 10:00:00');
    $cp->update(['student_seen_at' => '2026-09-01 11:00:00']);   // they already saw it

    \Carbon\Carbon::setTestNow('2026-09-20 09:00:00');
    Volt::test('admin.checkpoints')->call('reject', $cp->id, 'Actually, the bot is not live.');
    \Carbon\Carbon::setTestNow();

    $cp->refresh();
    expect($cp->status)->toBe('rejected')
        ->and($cp->note)->toBe('Actually, the bot is not live.')
        ->and($cp->isUnseenReview())->toBeTrue();   // the reversal reaches them
});

it('clears the rejection note when a checkpoint is later approved', function () {
    $this->actingAs(crAdmin());

    $e = crStudent('Chidi Okonkwo');
    $cp = crCheckpoint($e, 'module-01', 'rejected');

    Volt::test('admin.checkpoints')->call('approve', $cp->id);

    expect($cp->fresh()->status)->toBe('approved')
        ->and($cp->fresh()->note)->toBeNull();
});

it('stays behind the admin gate', function () {
    $e = crStudent('Nosy Student');
    User::factory()->create(['email' => $e->email]);

    $this->actingAs(User::where('email', $e->email)->first())
        ->get('/admin/checkpoints')
        ->assertForbidden();
});

/* ----------------------------------------------------------- exclusions --- */

it('keeps an excluded account off the leaderboard and out of the ranks', function () {
    config(['accelerator.progress_excluded_emails' => ['tommyriode@gmail.com']]);
    config(['curriculum' => ['core' => [['id' => 'module-01', 'title' => 'M1', 'videos' => []]], 'live' => []]]);

    $owner = crStudent('AJ Thompson', 3, 'tommyriode@gmail.com');
    crCheckpoint($owner, 'module-01', 'approved');       // would otherwise rank #1
    $student = crStudent('Chidi Okonkwo', 3);

    $rows = Progress::forCohort(3);

    expect($rows->pluck('email'))->not->toContain('tommyriode@gmail.com')
        ->and($rows)->toHaveCount(1)
        ->and($rows->first()['name'])->toBe('Chidi Okonkwo')
        ->and($rows->first()['rank'])->toBe(1);          // ranks stay contiguous
});

it('matches an excluded address regardless of case', function () {
    config(['accelerator.progress_excluded_emails' => ['TommyRiode@Gmail.com']]);
    config(['curriculum' => ['core' => [['id' => 'module-01', 'title' => 'M1', 'videos' => []]], 'live' => []]]);

    crStudent('AJ Thompson', 3, 'tommyriode@gmail.com');

    expect(Progress::forCohort(3))->toHaveCount(0);
});

it('leaves everyone in when nothing is excluded', function () {
    config(['accelerator.progress_excluded_emails' => []]);
    config(['curriculum' => ['core' => [['id' => 'module-01', 'title' => 'M1', 'videos' => []]], 'live' => []]]);

    crStudent('Chidi Okonkwo', 3);
    crStudent('Ada Nwosu', 3);

    expect(Progress::forCohort(3))->toHaveCount(2);
});
