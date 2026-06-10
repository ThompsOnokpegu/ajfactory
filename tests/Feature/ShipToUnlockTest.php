<?php

use App\Models\Checkpoint;
use App\Models\Enrollment;
use App\Models\User;
use Livewire\Volt\Volt;

uses(\Tests\TestCase::class, \Illuminate\Foundation\Testing\RefreshDatabase::class);

function makeStudent(int $cohort): User
{
    $user = User::factory()->create();

    Enrollment::create([
        'full_name' => $user->name,
        'email' => $user->email,
        'payment_reference' => 'TEST_'.uniqid(),
        'amount' => 79000,
        'plan_type' => 'full',
        'cohort' => $cohort,
        'status' => 'paid',
    ]);

    return $user;
}

it('keeps Cohort 1 fully open — no checkpoint gating', function () {
    $this->actingAs(makeStudent(1));

    $component = Volt::test('dashboard.terminal')->assertSet('shipToUnlock', false);

    $locks = collect($component->get('lockMap'));
    expect($locks)->not->toBeEmpty();
    expect($locks->every(fn ($l) => $l['locked'] === false))->toBeTrue();
});

it('gates Cohort 2 core modules behind the previous checkpoint', function () {
    config(['accelerator.cohort_starts_at' => now()->subDay()]); // pass the 6 July start floor

    $this->actingAs(makeStudent(2));

    $locks = Volt::test('dashboard.terminal')
        ->assertSet('shipToUnlock', true)
        ->get('lockMap');

    expect($locks['0-0']['locked'])->toBeFalse();           // module 1 open (floor passed)
    expect($locks['0-1']['locked'])->toBeTrue();            // module 2 locked
    expect($locks['0-1']['reason'])->toBe('checkpoint');
});

it('still locks Cohort 2 module 1 before the start floor', function () {
    config(['accelerator.cohort_starts_at' => now()->addWeek()]); // floor in the future

    $this->actingAs(makeStudent(2));

    $locks = Volt::test('dashboard.terminal')->get('lockMap');
    expect($locks['0-0']['locked'])->toBeTrue();
    expect($locks['0-0']['reason'])->toBe('date');
});

it('lets a Cohort 2 student submit a proof checkpoint', function () {
    config(['accelerator.cohort_starts_at' => now()->subDay()]);

    $this->actingAs(makeStudent(2));

    Volt::test('dashboard.terminal')
        ->set('proofUrl', 'https://loom.com/share/abc123')
        ->call('submitCheckpoint')
        ->assertHasNoErrors();

    $cp = Checkpoint::first();
    expect($cp->status)->toBe('submitted');
    expect($cp->module_id)->toBe('module-01');
    expect($cp->proof_url)->toBe('https://loom.com/share/abc123');
});

it('rejects an invalid proof URL', function () {
    config(['accelerator.cohort_starts_at' => now()->subDay()]);

    $this->actingAs(makeStudent(2));

    Volt::test('dashboard.terminal')
        ->set('proofUrl', 'not-a-url')
        ->call('submitCheckpoint')
        ->assertHasErrors('proofUrl');

    expect(Checkpoint::count())->toBe(0);
});

function makeAdmin(): User
{
    $admin = User::factory()->create();
    $admin->forceFill(['is_admin' => true])->save();

    return $admin;
}

function submittedCheckpoint(string $moduleId = 'module-01'): Checkpoint
{
    $student = makeStudent(2);
    $enrollment = Enrollment::where('email', $student->email)->first();

    return Checkpoint::create([
        'enrollment_id' => $enrollment->id,
        'module_id' => $moduleId,
        'status' => 'submitted',
        'proof_url' => 'https://loom.com/share/abc123',
        'submitted_at' => now(),
    ]);
}

it('lets an admin approve a checkpoint', function () {
    $cp = submittedCheckpoint();
    $this->actingAs(makeAdmin());

    Volt::test('admin.checkpoints')->call('approve', $cp->id);

    expect($cp->fresh()->status)->toBe('approved');
    expect($cp->fresh()->reviewed_at)->not->toBeNull();
});

it('lets an admin reject a checkpoint with a note', function () {
    $cp = submittedCheckpoint();
    $this->actingAs(makeAdmin());

    Volt::test('admin.checkpoints')->call('reject', $cp->id, 'Show the workflow actually running.');

    expect($cp->fresh()->status)->toBe('rejected');
    expect($cp->fresh()->note)->toBe('Show the workflow actually running.');
});

it('renders the review screen for an admin', function () {
    $this->actingAs(makeAdmin());
    $this->get('/admin/checkpoints')->assertOk()->assertSee('Proof Checkpoints');
});

it('forbids non-admins from the review screen', function () {
    $this->actingAs(makeStudent(2)); // paid student, not an admin
    $this->get('/admin/checkpoints')->assertForbidden();
});

it('unlocks the next module once the previous checkpoint is approved', function () {
    config(['accelerator.cohort_starts_at' => now()->subDay()]);

    $user = makeStudent(2);
    $enrollment = Enrollment::where('email', $user->email)->first();

    Checkpoint::create([
        'enrollment_id' => $enrollment->id,
        'module_id' => 'module-01',
        'status' => 'approved',
        'proof_url' => 'https://loom.com/share/abc123',
        'submitted_at' => now(),
        'reviewed_at' => now(),
    ]);

    $this->actingAs($user);

    $locks = Volt::test('dashboard.terminal')->get('lockMap');
    expect($locks['0-1']['locked'])->toBeFalse(); // module 2 now unlocked
});
