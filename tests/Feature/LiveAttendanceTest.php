<?php

use App\Models\Enrollment;
use App\Models\LiveAttendance;
use App\Models\User;
use Livewire\Volt\Volt;

function laEnrolledUser(): User
{
    $u = User::factory()->create();
    Enrollment::create([
        'full_name' => $u->name,
        'email' => $u->email,
        'payment_reference' => 'T_' . uniqid(),
        'amount' => 79000,
        'status' => 'paid',
        'cohort' => 2,
    ]);

    return $u;
}

// Controlled curriculum with one Live Archive session carrying a code + playbook.
function laFakeCurriculum(string $code = 'MANGO'): void
{
    config(['curriculum' => [
        'core' => [[
            'id' => 'module-01', 'title' => 'Module 01', 'has_blueprint' => false,
            'videos' => [['id' => 'm1v1', 'title' => 'V1', 'video_id' => 'x', 'duration' => '1:00']],
        ]],
        'live' => [[
            'id' => 'live-05', 'title' => 'Live Session #5',
            'release_at' => '2020-01-01 00:00:00',
            'attendance_code' => $code,
            'playbook_url' => 'https://example.test/playbook',
            'videos' => [['id' => 'live-05-v1', 'title' => 'Recording', 'video_id' => 'y', 'duration' => '1:00']],
        ]],
    ]]);
}

it('marks live attendance with the correct code (case-insensitive)', function () {
    laFakeCurriculum('MANGO');
    $u = laEnrolledUser();
    $this->actingAs($u);

    Volt::test('dashboard.terminal')
        ->set('activeSection', 1)   // Live Archive
        ->set('activeModule', 0)    // live-05
        ->set('attendanceCode', 'mango')
        ->call('markAttendance')
        ->assertHasNoErrors();

    $e = Enrollment::where('email', $u->email)->first();
    expect(LiveAttendance::where('enrollment_id', $e->id)->where('session_key', 'live-05')->exists())->toBeTrue();
});

it('rejects a wrong code and records nothing', function () {
    laFakeCurriculum('MANGO');
    $this->actingAs(laEnrolledUser());

    Volt::test('dashboard.terminal')
        ->set('activeSection', 1)->set('activeModule', 0)
        ->set('attendanceCode', 'WRONG')
        ->call('markAttendance')
        ->assertHasErrors('attendanceCode');

    expect(LiveAttendance::count())->toBe(0);
});

it('never sends the attendance code to the browser', function () {
    laFakeCurriculum('SECRET123');
    $this->actingAs(laEnrolledUser());

    $html = $this->get(route('dashboard'))->assertOk()->getContent();

    expect($html)->not->toContain('SECRET123');
});

it('is idempotent — marking twice keeps a single row', function () {
    laFakeCurriculum('MANGO');
    $this->actingAs(laEnrolledUser());

    $c = Volt::test('dashboard.terminal')->set('activeSection', 1)->set('activeModule', 0);
    $c->set('attendanceCode', 'MANGO')->call('markAttendance');
    $c->set('attendanceCode', 'MANGO')->call('markAttendance');

    expect(LiveAttendance::count())->toBe(1);
});

it('does not record attendance when the session has no code', function () {
    laFakeCurriculum('MANGO');
    // Blank the code → attendance closed.
    config(['curriculum.live.0.attendance_code' => null]);
    $this->actingAs(laEnrolledUser());

    Volt::test('dashboard.terminal')
        ->set('activeSection', 1)->set('activeModule', 0)
        ->set('attendanceCode', 'MANGO')
        ->call('markAttendance');

    expect(LiveAttendance::count())->toBe(0);
});

/*
 * Guards on the SHIPPED config (not the fake curriculum above). These caught a real
 * problem: the guarantee demanded 6 sessions when only 6 were still attendable, so a
 * single absence failed the guarantee for everyone.
 */

it('keeps the completion-guarantee threshold reachable', function () {
    $threshold = (int) config('accelerator.guarantee_min_live_sessions');
    $sessions = count(config('curriculum.live', []));

    expect($threshold)->toBeGreaterThan(0);

    // Attendance can't be earned retroactively, so the threshold must leave margin
    // against the sessions that exist — never demand a perfect record.
    expect($threshold)->toBeLessThan($sessions);
});

it('never ships a live session with a placeholder playbook link', function () {
    $live = config('curriculum.live', []);

    // Baseline so this can never pass vacuously against an empty/renamed config.
    expect($live)->not->toBeEmpty();

    // A non-empty playbook_url renders a real button for students. An empty string
    // or a TODO marker would ship a dead link, so the field must be unset instead.
    foreach ($live as $session) {
        if (! array_key_exists('playbook_url', $session)) {
            continue;
        }

        $url = $session['playbook_url'];

        expect($url)->toBeString()
            ->and(trim($url))->not->toBe('')
            ->and($url)->not->toContain('TODO');
        expect(str_starts_with($url, '/') || filter_var($url, FILTER_VALIDATE_URL) !== false)->toBeTrue();
    }
});
