<?php

use App\Models\Enrollment;
use App\Models\User;
use App\Support\Accelerator;
use Livewire\Volt\Volt;

/*
 * Currency symbols on every screen that shows money.
 *
 * These all used to read `$currency === 'NGN' ? 'N' : '$'`, so a buyer paying in cedis,
 * shillings or rand had their amount rendered with a DOLLAR sign - a GH 820 sale shown
 * as "$820". Wrong by a factor of the exchange rate, on the admin screens the owner
 * reconciles payments from and on the student's own balance notice.
 *
 * Accelerator::currencySymbol() reads the configured table, so adding a currency now
 * renders correctly everywhere without touching a view.
 */

function cdEnrollment(string $currency, array $extra = []): Enrollment
{
    return Enrollment::create(array_merge([
        'full_name' => 'Kwame Mensah',
        'email' => 'kwame_' . uniqid() . '@test.dev',
        'payment_reference' => 'P_' . uniqid(),
        'amount' => 820,
        'amount_total' => 820,
        'currency' => $currency,
        'status' => 'paid',
        'cohort' => 3,
        'paid_at' => now(),
    ], $extra));
}

it('renders the right symbol for every offered currency', function () {
    // The table is the source of truth - these are the symbols students are charged under.
    expect(Accelerator::currencySymbol('NGN'))->toBe('₦')
        ->and(Accelerator::currencySymbol('USD'))->toBe('$')
        ->and(Accelerator::currencySymbol('GHS'))->toBe('GH₵')
        ->and(Accelerator::currencySymbol('KES'))->toBe('KSh')
        ->and(Accelerator::currencySymbol('ZAR'))->toBe('R');
});

it('never shows a non-dollar currency with a dollar sign on the admin list', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));

    cdEnrollment('GHS');

    $html = Volt::test('admin.enrollments')->html();

    expect($html)->toContain('GH₵')
        ->and($html)->not->toContain('$820');
});

it('shows each currency correctly on the admin overview', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));

    cdEnrollment('KES', ['amount' => 9500, 'amount_total' => 9500]);

    expect(Volt::test('admin.overview')->html())->toContain('KSh');
});

it('shows a student their balance in their own currency', function () {
    $e = cdEnrollment('ZAR', [
        'plan_type' => 'installment',
        'amount' => 750,
        'amount_total' => 1500,
        'balance_due' => 750,
        'second_payment_status' => 'pending',
        'second_payment_due_at' => now()->addDay(),
    ]);
    User::factory()->create(['email' => $e->email, 'name' => $e->full_name]);
    $this->actingAs(User::where('email', $e->email)->first());

    Volt::test('dashboard.terminal')
        ->assertSet('balanceNotice.symbol', 'R');
});

it('offers every enabled currency on the manual enrolment form, not just NGN and USD', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));

    $html = Volt::test('admin.enrollments')->set('showEnrol', true)->html();

    foreach (Accelerator::enabledCurrencies() as $code) {
        expect($html)->toContain('value="' . $code . '"');
    }
});

it('accepts a manual enrolment in a non-NGN, non-USD currency', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));

    Volt::test('admin.enrollments')
        ->set('showEnrol', true)
        ->set('meName', 'Kwame Mensah')
        ->set('meEmail', 'kwame@test.dev')
        ->set('meAmount', '980')
        ->set('meCurrency', 'GHS')
        ->set('mePlan', 'full')
        ->set('meCohort', 3)
        ->call('manualEnrol')
        ->assertHasNoErrors();

    expect(Enrollment::where('email', 'kwame@test.dev')->first()->currency)->toBe('GHS');
});

it('rejects a currency the checkout cannot actually charge', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));

    Volt::test('admin.enrollments')
        ->set('showEnrol', true)
        ->set('meName', 'Someone')
        ->set('meEmail', 'someone@test.dev')
        ->set('meAmount', '100')
        ->set('meCurrency', 'EUR')     // not in the currency table
        ->set('mePlan', 'full')
        ->set('meCohort', 3)
        ->call('manualEnrol')
        ->assertHasErrors('meCurrency');
});

it('names the gateway that actually collects the currency', function () {
    // NGN settles through Paystack, everything else through Flutterwave - read from
    // config rather than assumed, so a provider change does not leave a stale label.
    expect(Accelerator::paymentProvider('NGN'))->toBe('paystack')
        ->and(Accelerator::paymentProvider('GHS'))->toBe('flutterwave');
});
