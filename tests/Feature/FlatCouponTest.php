<?php

use App\Support\Accelerator;
use Carbon\Carbon;
use Livewire\Volt\Volt;

afterEach(fn () => Carbon::setTestNow());

/*
 * TAAB50 is a FLAT-RATE coupon: it sets the price rather than subtracting an amount.
 * The distinction matters because the base price moves on its own - early-bird ends
 * on a date OR when the 10th seat sells - and a fixed-amount coupon would silently
 * charge the wrong total the moment it did.
 */

it('charges the flat price no matter what the base price is', function () {
    $coupon = ['type' => 'flat', 'value' => ['NGN' => 50000]];

    // Early-bird base, full base, and a hypothetical future price all land on 50,000.
    foreach ([69000, 79000, 120000] as $base) {
        $paid = $base - Accelerator::couponDiscount($coupon, $base, 'NGN');
        expect($paid)->toBe(50000.0, "base {$base} did not settle at the flat price");
    }
});

it('never turns into a refund when the base is below the flat price', function () {
    $coupon = ['type' => 'flat', 'value' => ['NGN' => 50000]];

    expect(Accelerator::couponDiscount($coupon, 40000, 'NGN'))->toBe(0.0);
});

it('gives no discount in a currency the flat coupon does not price', function () {
    // Better to charge full than to invent an exchange rate.
    $coupon = ['type' => 'flat', 'value' => ['NGN' => 50000]];

    expect(Accelerator::couponDiscount($coupon, 57, 'USD'))->toBe(0.0);
});

it('TAAB50 is live today and prices the full plan at 50,000', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-29 12:00', 'Africa/Lagos'));

    $coupon = Accelerator::coupon('taab50'); // case-insensitive
    expect($coupon)->not->toBeNull();

    $base = Accelerator::fullPrice('NGN');
    $paid = $base - Accelerator::couponDiscount($coupon, $base, 'NGN');

    expect($paid)->toBe(50000.0)
        ->and(Accelerator::couponAppliesToPlan($coupon, 'full'))->toBeTrue()
        ->and(Accelerator::couponAppliesToPlan($coupon, 'installment'))->toBeFalse();
});

it('TAAB50 expires at the end of 29 August', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-29 23:59:00', 'Africa/Lagos'));
    expect(Accelerator::coupon('TAAB50'))->not->toBeNull();

    Carbon::setTestNow(Carbon::parse('2026-08-30 00:30', 'Africa/Lagos'));
    expect(Accelerator::coupon('TAAB50'))->toBeNull();
});

it('charges a TAAB50 buyer exactly 50,000 at checkout', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-29 12:00', 'Africa/Lagos'));

    Volt::test('accelerator-checkout')
        ->set('plan', 'full')
        ->set('couponCode', 'TAAB50')
        ->call('applyCoupon')
        ->assertSet('amountToday', 50000.0)
        ->assertSet('amountTotal', 50000.0);
});

it('does not discount an installment buyer who tries TAAB50', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-29 12:00', 'Africa/Lagos'));

    $c = Volt::test('accelerator-checkout')
        ->set('plan', 'installment')
        ->set('couponCode', 'TAAB50')
        ->call('applyCoupon');

    expect((float) $c->get('discount'))->toBe(0.0);
});
