<?php

use App\Models\Enrollment;
use App\Models\User;
use App\Support\StudentProvisioner;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Livewire\Volt\Volt;

beforeEach(function () {
    config(['services.n8n.enrollment_webhook' => 'https://example.test/enrol']);
});

function seedEnrolledStudent(string $email = 'ada@example.com'): Enrollment
{
    User::factory()->create(['email' => $email, 'password' => Hash::make('original-secret')]);

    return Enrollment::create([
        'full_name' => 'Ada Builder', 'email' => $email, 'whatsapp' => '+2348000000000',
        'payment_reference' => 'MAN_TEST123', 'amount' => 79000, 'amount_total' => 79000,
        'balance_due' => 0, 'plan_type' => 'full', 'second_payment_status' => 'none',
        'cohort' => 2, 'currency' => 'NGN', 'status' => 'paid', 'paid_at' => now(),
    ]);
}

it('re-fires the welcome webhook with a fresh temp password', function () {
    Http::fake();
    $enrollment = seedEnrolledStudent();
    $before = User::where('email', 'ada@example.com')->value('password');

    $result = app(StudentProvisioner::class)->resendWelcome($enrollment);

    expect($result['ok'])->toBeTrue()
        ->and($result['temp_password'])->not->toBeNull();

    // The original plaintext is unrecoverable, so a NEW password must be set.
    $after = User::where('email', 'ada@example.com')->value('password');
    expect($after)->not->toBe($before)
        ->and(Hash::check($result['temp_password'], $after))->toBeTrue();

    Http::assertSent(fn ($req) => $req['event'] === 'enrollment_finalized'
        && $req['email'] === 'ada@example.com'
        && $req['resent'] === true
        && $req['temp_password'] === $result['temp_password']);
});

it('reports failure when n8n rejects the resend', function () {
    Http::fake(['*' => Http::response('boom', 500)]);
    $enrollment = seedEnrolledStudent();

    $result = app(StudentProvisioner::class)->resendWelcome($enrollment);

    // Must not claim success — that was the bug that hid the masterclass drops.
    expect($result['ok'])->toBeFalse()
        ->and($result['temp_password'])->not->toBeNull(); // still surfaced so admin can hand it over
});

it('can re-send without touching the password', function () {
    Http::fake();
    $enrollment = seedEnrolledStudent();
    $before = User::where('email', 'ada@example.com')->value('password');

    $result = app(StudentProvisioner::class)->resendWelcome($enrollment, issueNewPassword: false);

    expect($result['ok'])->toBeTrue()
        ->and($result['temp_password'])->toBeNull()
        ->and(User::where('email', 'ada@example.com')->value('password'))->toBe($before);
});

it('lets an admin re-send the welcome from the enrollments screen', function () {
    Http::fake();
    $enrollment = seedEnrolledStudent();
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin);
    Volt::test('admin.enrollments')
        ->call('resendWelcome', $enrollment->id)
        ->assertSee('Welcome re-sent', false);

    Http::assertSent(fn ($req) => $req['email'] === 'ada@example.com' && $req['resent'] === true);
});

it('blocks non-admins from the enrollments screen', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)->get('/admin/enrollments')->assertForbidden();
});
