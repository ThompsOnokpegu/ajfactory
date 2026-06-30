<?php

use App\Models\Enrollment;
use App\Models\User;
use App\Support\Accelerator;
use Livewire\Volt\Volt;

it('defaults registration to open and toggles off/on', function () {
    expect(Accelerator::registrationOpen())->toBeTrue();

    Accelerator::setRegistrationOpen(false);
    expect(Accelerator::registrationOpen())->toBeFalse();

    Accelerator::setRegistrationOpen(true);
    expect(Accelerator::registrationOpen())->toBeTrue();
});

it('shows the paused state on checkout when registration is closed', function () {
    Accelerator::setRegistrationOpen(false);

    Volt::test('accelerator-checkout')->assertSee('Registration is paused');
});

it('blocks checkout payment while registration is paused', function () {
    Accelerator::setRegistrationOpen(false);

    Volt::test('accelerator-checkout')
        ->set('full_name', 'Ada Builder')
        ->set('email', 'ada@example.com')
        ->set('acknowledged', true)
        ->call('initiatePayment')
        ->assertSet('statusMessage', 'Registration is currently closed. Please join the waitlist.');

    expect(Enrollment::count())->toBe(0);
});

it('lets an admin pause registration from the overview', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    expect(Accelerator::registrationOpen())->toBeTrue();

    $this->actingAs($admin);
    Volt::test('admin.overview')->call('toggleRegistration');

    expect(Accelerator::registrationOpen())->toBeFalse();
});
