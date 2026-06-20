<?php

use App\Models\Enrollment;
use App\Models\MasterclassRegistration;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Livewire\Volt\Volt;

uses(\Tests\TestCase::class, \Illuminate\Foundation\Testing\RefreshDatabase::class);

function adminUser(): User
{
    $u = User::factory()->create();
    $u->forceFill(['is_admin' => true])->save();

    return $u;
}

function anEnrollment(array $overrides = []): Enrollment
{
    return Enrollment::create(array_merge([
        'full_name' => 'Ada Builder',
        'email' => 'ada' . uniqid() . '@example.com',
        'payment_reference' => 'R' . uniqid(),
        'amount' => 79000,
        'plan_type' => 'full',
        'cohort' => 1,
        'amount_total' => 79000,
        'balance_due' => 0,
        'second_payment_status' => 'none',
        'currency' => 'NGN',
        'status' => 'paid',
    ], $overrides));
}

it('redirects guests to login from admin', function () {
    $this->get('/admin')->assertRedirect(route('login'));
});

it('forbids non-admins from the admin area', function () {
    $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
});

it('lets admins reach every admin page', function () {
    $this->actingAs(adminUser());
    foreach (['/admin', '/admin/enrollments', '/admin/masterclass', '/admin/leads', '/admin/checkpoints'] as $u) {
        $this->get($u)->assertOk();
    }
});

it('overview reports collected revenue', function () {
    anEnrollment(['amount' => 79000, 'amount_total' => 79000, 'balance_due' => 0]);
    $this->actingAs(adminUser());
    $this->get('/admin')->assertOk()->assertSee('79,000');
});

it('toggles a student suspension', function () {
    $e = anEnrollment();
    $this->actingAs(adminUser());
    Volt::test('admin.enrollments')->call('toggleSuspend', $e->id);
    expect($e->fresh()->access_suspended)->toBeTrue();
});

it('marks an installment balance paid and reinstates access', function () {
    $e = anEnrollment(['plan_type' => 'installment', 'balance_due' => 42000, 'second_payment_status' => 'link_sent', 'access_suspended' => true]);
    $this->actingAs(adminUser());
    Volt::test('admin.enrollments')->call('markBalancePaid', $e->id);
    $e->refresh();
    expect((float) $e->balance_due)->toBe(0.0);
    expect($e->second_payment_status)->toBe('paid');
    expect($e->access_suspended)->toBeFalse();
});

it('sets a student cohort', function () {
    $e = anEnrollment(['cohort' => 1]);
    $this->actingAs(adminUser());
    Volt::test('admin.enrollments')->call('setCohort', $e->id, 2);
    expect($e->fresh()->cohort)->toBe(2);
});

it('re-sends an installment link and fires n8n', function () {
    config(['services.n8n.installment_webhook' => 'https://example.test/inst']);
    Http::fake();
    $e = anEnrollment(['plan_type' => 'installment', 'balance_due' => 42000]);
    $this->actingAs(adminUser());

    Volt::test('admin.enrollments')->call('resendInstallmentLink', $e->id);

    Http::assertSent(fn ($r) => $r['event'] === 'installment_due'
        && str_contains($r['pay_url'], '/installment/' . $e->id . '/pay'));
});

it('manually enrols a student, provisions the account, and fires the welcome', function () {
    config(['services.n8n.enrollment_webhook' => 'https://example.test/enr']);
    Http::fake();
    $this->actingAs(adminUser());

    Volt::test('admin.enrollments')
        ->set('meName', 'Tunde Cash')
        ->set('meEmail', 'tunde@example.com')
        ->set('meAmount', '79000')
        ->set('meCurrency', 'NGN')
        ->set('mePlan', 'full')
        ->set('meCohort', 2)
        ->call('manualEnrol')
        ->assertHasNoErrors();

    expect(User::where('email', 'tunde@example.com')->exists())->toBeTrue();
    expect(Enrollment::where('email', 'tunde@example.com')->where('status', 'paid')->where('cohort', 2)->exists())->toBeTrue();
    Http::assertSent(fn ($r) => $r['event'] === 'enrollment_finalized' && $r['gateway'] === 'manual');
});

it('exports masterclass registrations as csv', function () {
    MasterclassRegistration::create(['first_name' => 'Ada', 'last_name' => 'Okafor', 'email' => 'ada@example.com', 'session_date' => '2026-06-27']);
    $this->actingAs(adminUser());
    $res = $this->get('/admin/masterclass/export');
    $res->assertOk();
    expect($res->headers->get('content-type'))->toContain('text/csv');
    expect($res->streamedContent())->toContain('ada@example.com');
});

it('exports leads as csv', function () {
    Student::create(['name' => 'Ada', 'email' => 'ada@example.com', 'interest' => 'masterclass', 'source' => 'scorecard']);
    $this->actingAs(adminUser());
    expect($this->get('/admin/leads/export')->streamedContent())->toContain('scorecard');
});
