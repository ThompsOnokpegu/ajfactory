<?php

use App\Support\Accelerator;
use Carbon\Carbon;
use Livewire\Volt\Volt;

afterEach(fn () => Carbon::setTestNow());

/** The live flat coupon, by name, so renaming it is a one-line change here. */
const FLAT_CODE = 'TAAB100';

/**
 * A moment at which the flat coupon is live.
 *
 * It currently has NO `expires_at`, so any time works. Kept as a helper (rather than
 * dropping the clock entirely) because the previous codes were all dated, and giving
 * this one an expiry should not mean rewriting every test below - only this function.
 */
function whileFlatCouponIsLive(): Carbon
{
    $expiry = config('accelerator.coupons.' . FLAT_CODE . '.expires_at');

    return $expiry
        ? Carbon::parse($expiry, 'Africa/Lagos')->subHour()
        : Carbon::now();
}


/*
 * The flat-rate coupon sets the price rather than subtracting an amount.
 * The distinction matters because the base price moves on its own - early-bird ends
 * on a date OR when the 10th seat sells - and a fixed-amount coupon would silently
 * charge the wrong total the moment it did.
 */

it('charges the flat price no matter what the base price is', function () {
    $coupon = ['type' => 'flat', 'value' => ['NGN' => 100000]];

    // Early-bird base, full base, and a hypothetical future price all land on 100,000.
    foreach ([110000, 120000, 150000] as $base) {
        $paid = $base - Accelerator::couponDiscount($coupon, $base, 'NGN');
        expect($paid)->toBe(100000.0, "base {$base} did not settle at the flat price");
    }
});

it('never turns into a refund when the base is below the flat price', function () {
    $coupon = ['type' => 'flat', 'value' => ['NGN' => 100000]];

    expect(Accelerator::couponDiscount($coupon, 80000, 'NGN'))->toBe(0.0);
});

it('gives no discount in a currency the flat coupon does not price', function () {
    // Better to charge full than to invent an exchange rate.
    $coupon = ['type' => 'flat', 'value' => ['NGN' => 100000]];

    expect(Accelerator::couponDiscount($coupon, 87, 'USD'))->toBe(0.0);
});

it('prices the full plan at the flat rate while it is live', function () {
    Carbon::setTestNow(whileFlatCouponIsLive());

    $coupon = Accelerator::coupon('taab100'); // case-insensitive
    expect($coupon)->not->toBeNull();

    $base = Accelerator::fullPrice('NGN');
    $paid = $base - Accelerator::couponDiscount($coupon, $base, 'NGN');

    expect($paid)->toBe(100000.0)
        ->and(Accelerator::couponAppliesToPlan($coupon, 'full'))->toBeTrue()
        ->and(Accelerator::couponAppliesToPlan($coupon, 'installment'))->toBeFalse();
});

it('honours an expiry the moment one is configured', function () {
    $expiry = config('accelerator.coupons.' . FLAT_CODE . '.expires_at');

    if (! $expiry) {
        // No cutoff is set, so the code is open-ended. Asserted rather than skipped:
        // this is a live discount with no end date, and that should be a deliberate
        // choice someone sees in a diff, not something nobody notices.
        Carbon::setTestNow(Carbon::now()->addYears(5));
        expect(Accelerator::coupon(FLAT_CODE))->not->toBeNull();

        return;
    }

    $at = Carbon::parse($expiry, 'Africa/Lagos');

    Carbon::setTestNow($at->copy()->subMinute());
    expect(Accelerator::coupon(FLAT_CODE))->not->toBeNull();

    Carbon::setTestNow($at->copy()->addMinute());
    expect(Accelerator::coupon(FLAT_CODE))->toBeNull();
});

it('charges a flat-coupon buyer exactly the flat rate at checkout', function () {
    Carbon::setTestNow(whileFlatCouponIsLive());

    Volt::test('accelerator-checkout')
        ->set('plan', 'full')
        ->set('couponCode', FLAT_CODE)
        ->call('applyCoupon')
        ->assertSet('amountToday', 100000.0)
        ->assertSet('amountTotal', 100000.0);
});

it('does not discount an installment buyer who tries the flat coupon', function () {
    Carbon::setTestNow(whileFlatCouponIsLive());

    $c = Volt::test('accelerator-checkout')
        ->set('plan', 'installment')
        ->set('couponCode', FLAT_CODE)
        ->call('applyCoupon');

    expect((float) $c->get('discount'))->toBe(0.0);
});

it('prices the flat coupon in every currency the checkout offers', function () {
    // A flat coupon with no entry for a currency gives NO discount, so the code is
    // accepted and the buyer pays full price. Worse than the coupon not existing.
    $value = config('accelerator.coupons.' . FLAT_CODE . '.value');

    foreach (Accelerator::enabledCurrencies() as $code) {
        expect($value[$code] ?? null)->toBeNumeric(FLAT_CODE . " has no price for {$code}");
    }
});

it('keeps the flat price below the full price in every currency', function () {
    // If the flat price ever landed above the base, couponDiscount clamps to zero and
    // the offer quietly becomes no offer at all.
    Carbon::setTestNow(whileFlatCouponIsLive());

    $coupon = Accelerator::coupon(FLAT_CODE);

    foreach (Accelerator::enabledCurrencies() as $code) {
        $base = Accelerator::fullPrice($code);
        $paid = $base - Accelerator::couponDiscount($coupon, $base, $code);

        expect($paid)->toBeLessThan($base, FLAT_CODE . " discounts nothing in {$code}");
    }
});
