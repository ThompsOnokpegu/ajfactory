<?php

use App\Support\Accelerator;
use Livewire\Volt\Volt;

/*
 * Multi-currency checkout. The rule that matters: a currency is sellable only when
 * it is FULLY priced. Half-configured must mean invisible, never cheap.
 */

/** A currency that exists in config but is missing a price. */
function unpricedCurrency(string $code = 'XOF'): string
{
    config(["accelerator.currencies.{$code}" => [
        'symbol' => 'CFA',
        'provider' => 'flutterwave',
        'price_full' => 900,
        'price_earlybird' => 800,
        'installment_each' => null,   // the gap
    ]]);

    return $code;
}

it('offers every currency that is fully priced', function () {
    expect(Accelerator::enabledCurrencies())
        ->toContain('NGN', 'USD', 'GHS', 'KES', 'ZAR');
});

it('keeps a currency hidden when any single price is missing', function () {
    $code = unpricedCurrency();

    expect(Accelerator::isSupportedCurrency($code))->toBeFalse()
        ->and(Accelerator::enabledCurrencies())->not->toContain($code);
});

it('falls back to the base currency rather than charging an unknown one', function () {
    $code = unpricedCurrency();

    expect(Accelerator::safeCurrency($code))->toBe('NGN')
        ->and(Accelerator::safeCurrency('WAT'))->toBe('NGN')
        ->and(Accelerator::safeCurrency(null))->toBe('NGN')
        ->and(Accelerator::safeCurrency('GHS'))->toBe('GHS');
});

it('reads prices, symbol and provider per currency', function () {
    expect(Accelerator::regularFullPrice('GHS'))->toBe(650.0)
        ->and(Accelerator::installmentEach('KES'))->toBe(3900.0)
        ->and(Accelerator::currencySymbol('ZAR'))->toBe('R')
        ->and(Accelerator::paymentProvider('ZAR'))->toBe('flutterwave')
        ->and(Accelerator::paymentProvider('NGN'))->toBe('paystack');
});

it('keeps Naira prices at the top level, not in the currency table', function () {
    // One source of truth - the landing page reads accelerator.price_full directly.
    config(['accelerator.price_full' => 88000, 'accelerator.price_earlybird' => 88000]);

    expect(Accelerator::regularFullPrice('NGN'))->toBe(88000.0);
});

/*
 * Structural guards on the shipped price table. These catch a future edit that
 * breaks the pricing model in a way nothing else would notice - the checkout would
 * happily sell an early-bird price above the full price, or an installment plan
 * cheaper than paying outright.
 */

it('prices every offered currency above zero', function () {
    foreach (Accelerator::enabledCurrencies() as $code) {
        expect(Accelerator::regularFullPrice($code))->toBeGreaterThan(0.0, "{$code} full price")
            ->and(Accelerator::installmentEach($code))->toBeGreaterThan(0.0, "{$code} installment");
    }
});

it('keeps early-bird below the full price everywhere', function () {
    foreach (Accelerator::enabledCurrencies() as $code) {
        $full = Accelerator::regularFullPrice($code);
        $early = (float) ($code === 'NGN'
            ? config('accelerator.price_earlybird')
            : config("accelerator.currencies.{$code}.price_earlybird"));

        expect($early)->toBeLessThan($full, "{$code} early-bird is not a discount");
    }
});

it('keeps the installment plan dearer than paying in full', function () {
    // Paying over time costs a premium in USD; every currency should mirror that,
    // or the installment option quietly becomes the cheapest way to buy.
    foreach (Accelerator::enabledCurrencies() as $code) {
        $installmentTotal = Accelerator::installmentEach($code) * Accelerator::installmentCount();

        expect($installmentTotal)->toBeGreaterThan(
            Accelerator::regularFullPrice($code),
            "{$code} installment total undercuts the full price"
        );
    }
});

it('will not let the checkout sit on an unpriced currency', function () {
    $code = unpricedCurrency();

    $c = Volt::test('accelerator-checkout')->set('currency', $code);

    // Snapped back rather than showing every amount as zero.
    expect($c->get('currency'))->toBe('NGN')
        ->and((float) $c->get('amountToday'))->toBeGreaterThan(0.0);
});

it('charges a Ghanaian buyer in cedis at the configured price', function () {
    $c = Volt::test('accelerator-checkout')->set('plan', 'full')->set('currency', 'GHS');

    expect($c->get('currency'))->toBe('GHS')
        ->and((float) $c->get('amountToday'))->toBe(570.0); // early-bird is active
});

it('gives the TAAB59 discount in every offered currency', function () {
    // A currency missing from a coupon gets NO discount - it would silently sell at
    // full price there, which is worse than the coupon not existing.
    $coupon = config('accelerator.coupons.TAAB59');

    foreach (Accelerator::enabledCurrencies() as $code) {
        expect($coupon['value'][$code] ?? null)
            ->toBeNumeric("TAAB59 has no value for {$code}");
    }
});
