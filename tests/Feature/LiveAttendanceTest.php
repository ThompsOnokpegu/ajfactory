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
