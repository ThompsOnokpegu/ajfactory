<?php

use App\Models\Checkpoint;
use App\Models\Enrollment;
use App\Models\LiveAttendance;
use App\Models\User;
use App\Support\Progress;
use Livewire\Volt\Volt;

/*
 * Checkpoint review notices + per-student progress + the cohort leaderboard.
 *
 * The operational things that matter here:
 *   - an approval actually reaches the student without them asking on Telegram,
 *   - a dismissed notice stays dismissed, but a LATER review raises a fresh one,
 *   - the board ranks on verified work only, and never leaks a non-student.
 */

function plCurriculum(): void
{
    config(['curriculum' => [
        'core' => [
            ['id' => 'module-01', 'title' => 'Module 01: First', 'videos' => [['id' => 'm1v1', 'title' => 'V1', 'video_id' => 'x', 'duration' => '1:00']]],
            ['id' => 'module-02', 'title' => 'Module 02: Second', 'videos' => [['id' => 'm2v1', 'title' => 'V1', 'video_id' => 'x', 'duration' => '1:00']]],
            ['id' => 'module-03', 'title' => 'Module 03: Third', 'videos' => [['id' => 'm3v1', 'title' => 'V1', 'video_id' => 'x', 'duration' => '1:00']]],
        ],
        'live' => [
            ['id' => 'live-01', 'title' => 'Live 1', 'release_at' => '2020-01-01 00:00:00',
             'videos' => [['id' => 'l1v1', 'title' => 'Rec', 'video_id' => 'y', 'duration' => '1:00']]],
        ],
    ]]);
}

function plStudent(string $name, int $cohort = 2, string $status = 'paid'): Enrollment
{
    return Enrollment::create([
        'full_name' => $name,
        'email' => str($name)->slug() . '_' . uniqid() . '@test.dev',
        'payment_reference' => 'P_' . uniqid(),
        'amount' => 79000,
        'status' => $status,
        'cohort' => $cohort,
    ]);
}

function plUserFor(Enrollment $e): User
{
    return User::factory()->create(['email' => $e->email, 'name' => $e->full_name]);
}

function plApprove(Enrollment $e, string $moduleId, ?string $at = null): Checkpoint
{
    return Checkpoint::create([
        'enrollment_id' => $e->id,
        'module_id' => $moduleId,
        'status' => 'approved',
        'proof_url' => 'https://loom.test/x',
        'submitted_at' => now()->subDay(),
        'reviewed_at' => $at ? \Carbon\Carbon::parse($at) : now(),
    ]);
}

/* ---------------------------------------------------------------- notices -- */

it('tells a student on the dashboard that their checkpoint was approved', function () {
    plCurriculum();
    $e = plStudent('Chidi Okonkwo');
    plApprove($e, 'module-01');

    $this->actingAs(plUserFor($e));

    Volt::test('dashboard.terminal')
        ->assertSet('reviewNotices', fn ($n) => count($n) === 1
            && $n[0]['module_id'] === 'module-01'
            && $n[0]['status'] === 'approved')
        ->assertSee('Module 01: First');
});

it('shows a rejection with the reviewer note, so they know to resubmit', function () {
    plCurriculum();
    $e = plStudent('Ada Nwosu');
    Checkpoint::create([
        'enrollment_id' => $e->id,
        'module_id' => 'module-01',
        'status' => 'rejected',
        'note' => 'The Loom link is private.',
        'submitted_at' => now()->subDay(),
        'reviewed_at' => now(),
    ]);

    $this->actingAs(plUserFor($e));

    Volt::test('dashboard.terminal')
        ->assertSee('needs another look')
        ->assertSee('The Loom link is private.');
});

it('does not raise a notice for a checkpoint still awaiting review', function () {
    plCurriculum();
    $e = plStudent('Bola Ade');
    Checkpoint::create([
        'enrollment_id' => $e->id,
        'module_id' => 'module-01',
        'status' => 'submitted',
        'submitted_at' => now(),
        'reviewed_at' => null,
    ]);

    $this->actingAs(plUserFor($e));

    Volt::test('dashboard.terminal')->assertSet('reviewNotices', []);
});

it('keeps a dismissed notice dismissed across a reload', function () {
    plCurriculum();
    $e = plStudent('Chidi Okonkwo');
    plApprove($e, 'module-01');
    $this->actingAs(plUserFor($e));

    Volt::test('dashboard.terminal')
        ->call('dismissReviewNotices')
        ->assertSet('reviewNotices', []);

    expect(Checkpoint::where('enrollment_id', $e->id)->first()->student_seen_at)->not->toBeNull();

    // A fresh page load must not resurrect it.
    Volt::test('dashboard.terminal')->assertSet('reviewNotices', []);
});

it('raises a fresh notice when a resubmission is reviewed again', function () {
    plCurriculum();
    $e = plStudent('Chidi Okonkwo');

    $cp = Checkpoint::create([
        'enrollment_id' => $e->id,
        'module_id' => 'module-01',
        'status' => 'rejected',
        'note' => 'Link is private.',
        'submitted_at' => now()->subDays(2),
        'reviewed_at' => now()->subDays(2),
    ]);

    $this->actingAs(plUserFor($e));

    // Pin the clock so the dismissal and the re-review can't land in the same second -
    // student_seen_at has to be strictly older than reviewed_at for the notice to fire.
    \Carbon\Carbon::setTestNow('2026-09-20 09:00:00');
    Volt::test('dashboard.terminal')->call('dismissReviewNotices');

    // They fix it, and it gets approved the next day.
    \Carbon\Carbon::setTestNow('2026-09-21 09:00:00');
    $cp->update(['status' => 'approved', 'note' => null, 'reviewed_at' => now()]);

    Volt::test('dashboard.terminal')
        ->assertSet('reviewNotices', fn ($n) => count($n) === 1 && $n[0]['status'] === 'approved');

    \Carbon\Carbon::setTestNow();
});

/* ------------------------------------------------------------ leaderboard -- */

it('ranks by approved checkpoints, then live sessions, then who got there first', function () {
    plCurriculum();

    $two = plStudent('Two Shipped');
    plApprove($two, 'module-01', '2026-09-01 10:00:00');
    plApprove($two, 'module-02', '2026-09-02 10:00:00');

    // Same count as $two, but got there later - so ranks below.
    $late = plStudent('Late Two');
    plApprove($late, 'module-01', '2026-09-05 10:00:00');
    plApprove($late, 'module-02', '2026-09-06 10:00:00');

    $one = plStudent('One Shipped');
    plApprove($one, 'module-01', '2026-08-01 10:00:00');

    $none = plStudent('Zero Shipped');

    $rows = Progress::forCohort(2);

    expect($rows->pluck('name')->all())->toBe(['Two Shipped', 'Late Two', 'One Shipped', 'Zero Shipped']);
    expect($rows->firstWhere('name', 'Two Shipped')['rank'])->toBe(1);
});

it('breaks a tie on live attendance before falling back to timing', function () {
    plCurriculum();

    $quiet = plStudent('Quiet One');
    plApprove($quiet, 'module-01', '2026-08-01 10:00:00');

    $present = plStudent('Present One');
    plApprove($present, 'module-01', '2026-09-01 10:00:00');
    LiveAttendance::create(['enrollment_id' => $present->id, 'session_key' => 'live-01', 'attended_at' => now()]);

    // Same approved count; the one who showed up live outranks the earlier finisher.
    expect(Progress::forCohort(2)->pluck('name')->all())->toBe(['Present One', 'Quiet One']);
});

it('never ranks a student above one who has shipped just because they shipped nothing', function () {
    plCurriculum();

    plStudent('No Proof A');
    plStudent('No Proof B');
    $shipper = plStudent('Shipper');
    plApprove($shipper, 'module-01', '2026-09-10 10:00:00');

    expect(Progress::forCohort(2)->first()['name'])->toBe('Shipper');
});

it('ignores self-marked lessons when ranking', function () {
    plCurriculum();

    $clicker = plStudent('Fast Clicker');
    $clicker->update(['completed_lessons' => ['m1v1', 'm2v1', 'm3v1', 'l1v1']]);

    $builder = plStudent('Real Builder');
    plApprove($builder, 'module-01');

    // The clicker ticked the whole course; the builder shipped one module and still wins.
    expect(Progress::forCohort(2)->first()['name'])->toBe('Real Builder');
});

it('counts only approved CORE checkpoints, not live-archive rows', function () {
    plCurriculum();

    $e = plStudent('Core Only');
    plApprove($e, 'module-01');
    plApprove($e, 'live-01');     // not a core module - must not count

    expect(Progress::forCohort(2)->first()['approved'])->toBe(1);
});

it('leaves unpaid checkouts off the board entirely', function () {
    plCurriculum();

    plStudent('Paid Student');
    $pending = plStudent('Abandoned Cart', 2, 'pending');
    plApprove($pending, 'module-01');   // even with an approval, they are not a student

    expect(Progress::forCohort(2)->pluck('name')->all())->toBe(['Paid Student']);
});

it('keeps cohorts separate', function () {
    plCurriculum();

    plStudent('Cohort Two', 2);
    plStudent('Cohort Three', 3);

    expect(Progress::forCohort(2)->pluck('name')->all())->toBe(['Cohort Two']);
    expect(Progress::forCohort(3)->pluck('name')->all())->toBe(['Cohort Three']);
});

it('shows a surname as an initial only', function () {
    expect(Progress::displayName('Chidi Okonkwo'))->toBe('Chidi O.')
        ->and(Progress::displayName('Ada Chi Nwosu'))->toBe('Ada N.')
        ->and(Progress::displayName('Prince'))->toBe('Prince')
        ->and(Progress::displayName('  '))->toBe('Student')
        ->and(Progress::displayName(null))->toBe('Student');
});

it('gives a student outside the top N their own rank', function () {
    plCurriculum();

    // 11 students, each shipping a decreasing amount so the order is deterministic.
    $mine = null;
    foreach (range(1, 11) as $i) {
        $s = plStudent("Student {$i}");
        if ($i < 11) {
            plApprove($s, 'module-01', '2026-09-' . str_pad((string) $i, 2, '0', STR_PAD_LEFT) . ' 10:00:00');
        } else {
            $mine = $s;   // ships nothing, so lands last
        }
    }

    $board = Progress::leaderboardFor(2, $mine->id, 10);

    expect($board['top'])->toHaveCount(10)
        ->and($board['total'])->toBe(11)
        ->and($board['you']['rank'])->toBe(11)
        ->and($board['top']->pluck('enrollment_id'))->not->toContain($mine->id);
});

it('never puts an email address on the student leaderboard', function () {
    plCurriculum();

    $e = plStudent('Chidi Okonkwo');
    plApprove($e, 'module-01');

    $this->actingAs(plUserFor($e));

    Volt::test('dashboard.terminal')
        ->assertSee('Chidi O.')
        ->assertDontSee($e->email);
});

/* ------------------------------------------------------------------ admin -- */

it('shows the admin per-student progress, ranked', function () {
    plCurriculum();

    $ahead = plStudent('Ahead Student');
    plApprove($ahead, 'module-01', '2026-09-01 10:00:00');
    plApprove($ahead, 'module-02', '2026-09-02 10:00:00');

    plStudent('Behind Student');

    $this->actingAs(User::factory()->create(['is_admin' => true]));

    Volt::test('admin.progress')
        ->set('cohort', '2')
        ->assertSee('Ahead Student')
        ->assertSee('Behind Student')
        ->assertSee('2/3');     // approved / core total
})->group('admin');

it('keeps the admin progress screen behind the admin gate', function () {
    plCurriculum();
    $e = plStudent('Nosy Student');

    $this->actingAs(plUserFor($e))
        ->get('/admin/progress')
        ->assertForbidden();
});
