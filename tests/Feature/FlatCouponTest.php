<?php

use App\Support\Accelerator;
use Carbon\Carbon;
use Livewire\Volt\Volt;

afterEach(fn () => Carbon::setTestNow());

/**
 * A moment inside TAAB50's configured validity window.
 *
 * Derived from config rather than hardcoded: these tests used to pin 29 Aug, and
 * moving the expiry would have failed them for a reason that looked like a coupon
 * bug rather than a stale date in a test.
 */
function whileTaab50IsLive(): Carbon
{
    return Carbon::parse(config('accelerator.coupons.TAAB50.expires_at'), 'Africa/Lagos')->subHour();
}


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

it('TAAB50 prices the full plan at 50,000 while it is live', function () {
    Carbon::setTestNow(whileTaab50IsLive());

    $coupon = Accelerator::coupon('taab50'); // case-insensitive
    expect($coupon)->not->toBeNull();

    $base = Accelerator::fullPrice('NGN');
    $paid = $base - Accelerator::couponDiscount($coupon, $base, 'NGN');

    expect($paid)->toBe(50000.0)
        ->and(Accelerator::couponAppliesToPlan($coupon, 'full'))->toBeTrue()
        ->and(Accelerator::couponAppliesToPlan($coupon, 'installment'))->toBeFalse();
});

it('TAAB50 stops working the moment its expiry passes', function () {
    $expiry = Carbon::parse(config('accelerator.coupons.TAAB50.expires_at'), 'Africa/Lagos');

    Carbon::setTestNow($expiry->copy()->subMinute());
    expect(Accelerator::coupon('TAAB50'))->not->toBeNull();

    Carbon::setTestNow($expiry->copy()->addMinute());
    expect(Accelerator::coupon('TAAB50'))->toBeNull();
});

it('charges a TAAB50 buyer exactly 50,000 at checkout', function () {
    Carbon::setTestNow(whileTaab50IsLive());

    Volt::test('accelerator-checkout')
        ->set('plan', 'full')
        ->set('couponCode', 'TAAB50')
        ->call('applyCoupon')
        ->assertSet('amountToday', 50000.0)
        ->assertSet('amountTotal', 50000.0);
});

it('does not discount an installment buyer who tries TAAB50', function () {
    Carbon::setTestNow(whileTaab50IsLive());

    $c = Volt::test('accelerator-checkout')
        ->set('plan', 'installment')
        ->set('couponCode', 'TAAB50')
        ->call('applyCoupon');

    expect((float) $c->get('discount'))->toBe(0.0);
});

it('prices TAAB50 in every currency the checkout offers', function () {
    // A flat coupon with no entry for a currency gives NO discount, so the code is
    // accepted and the buyer pays full price. Worse than the coupon not existing.
    $value = config('accelerator.coupons.TAAB50.value');

    foreach (Accelerator::enabledCurrencies() as $code) {
        expect($value[$code] ?? null)->toBeNumeric("TAAB50 has no price for {$code}");
    }
});

it('keeps the TAAB50 price below the full price in every currency', function () {
    // If the flat price ever landed above the base, couponDiscount clamps to zero and
    // the offer quietly becomes no offer at all.
    Carbon::setTestNow(whileTaab50IsLive());

    $coupon = Accelerator::coupon('TAAB50');

    foreach (Accelerator::enabledCurrencies() as $code) {
        $base = Accelerator::fullPrice($code);
        $paid = $base - Accelerator::couponDiscount($coupon, $base, $code);

        expect($paid)->toBeLessThan($base, "TAAB50 discounts nothing in {$code}");
    }
});
