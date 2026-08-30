<?php

use App\Support\Accelerator;
use Livewire\Volt\Volt;

/*
 * Multi-currency checkout. The rule that matters: a currency is sellable only when
 * it is FULLY priced. Half-configured must mean invisible, never cheap.
 */

function priceCurrency(string $code, array $prices = null): void
{
    $prices ??= ['price_full' => 900, 'price_earlybird' => 800, 'installment_each' => 500];

    config(["accelerator.currencies.{$code}" => array_merge(
        ['symbol' => 'X', 'provider' => 'flutterwave'],
        $prices,
    )]);
}

it('offers only fully priced currencies', function () {
    // Shipped state: GHS/KES/ZAR are declared but unpriced.
    expect(Accelerator::enabledCurrencies())->toContain('NGN', 'USD')
        ->and(Accelerator::enabledCurrencies())->not->toContain('GHS', 'KES', 'ZAR');
});

it('enables a currency as soon as every price is set', function () {
    priceCurrency('GHS');

    expect(Accelerator::enabledCurrencies())->toContain('GHS')
        ->and(Accelerator::isSupportedCurrency('GHS'))->toBeTrue();
});

it('keeps a currency hidden when any single price is missing', function () {
    priceCurrency('KES', ['price_full' => 900, 'price_earlybird' => 800, 'installment_each' => null]);

    expect(Accelerator::isSupportedCurrency('KES'))->toBeFalse();
});

it('falls back to the base currency rather than charging an unknown one', function () {
    expect(Accelerator::safeCurrency('GHS'))->toBe('NGN')
        ->and(Accelerator::safeCurrency('WAT'))->toBe('NGN')
        ->and(Accelerator::safeCurrency(null))->toBe('NGN')
        ->and(Accelerator::safeCurrency('USD'))->toBe('USD');
});

it('reads prices, symbol and provider per currency', function () {
    priceCurrency('ZAR', ['price_full' => 1400, 'price_earlybird' => 1200, 'installment_each' => 750]);
    config(['accelerator.currencies.ZAR.symbol' => 'R', 'accelerator.currencies.ZAR.provider' => 'flutterwave']);

    expect(Accelerator::regularFullPrice('ZAR'))->toBe(1400.0)
        ->and(Accelerator::installmentEach('ZAR'))->toBe(750.0)
        ->and(Accelerator::currencySymbol('ZAR'))->toBe('R')
        ->and(Accelerator::paymentProvider('ZAR'))->toBe('flutterwave')
        ->and(Accelerator::paymentProvider('NGN'))->toBe('paystack');
});

it('keeps Naira prices at the top level, not in the currency table', function () {
    // One source of truth - the landing page reads accelerator.price_full directly.
    config(['accelerator.price_full' => 88000, 'accelerator.price_earlybird' => 88000]);

    expect(Accelerator::regularFullPrice('NGN'))->toBe(88000.0);
});

it('will not let the checkout sit on an unpriced currency', function () {
    $c = Volt::test('accelerator-checkout')->set('currency', 'GHS');

    // Snapped back rather than showing every amount as zero.
    expect($c->get('currency'))->toBe('NGN')
        ->and((float) $c->get('amountToday'))->toBeGreaterThan(0.0);
});

it('lets the checkout use a currency once it is priced', function () {
    priceCurrency('GHS', ['price_full' => 900, 'price_earlybird' => 800, 'installment_each' => 500]);

    $c = Volt::test('accelerator-checkout')->set('plan', 'full')->set('currency', 'GHS');

    expect($c->get('currency'))->toBe('GHS')
        ->and((float) $c->get('amountToday'))->toBe(800.0); // early-bird is active
});
