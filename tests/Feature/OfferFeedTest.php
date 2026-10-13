<?php

use App\Support\Accelerator;
use Carbon\Carbon;

/*
 * GET /api/offer — the live offer Veta's WhatsApp AI quotes from. It must say
 * exactly what the site says, and must never leak a coupon code.
 */

beforeEach(fn () => config(['services.veta.feed_token' => 'feed-secret']));
afterEach(fn () => Carbon::setTestNow());

function offerFeed(?string $token = 'feed-secret')
{
    return test()->withToken((string) $token)->getJson('/api/offer');
}

it('rejects a missing or wrong token', function () {
    test()->getJson('/api/offer')->assertUnauthorized();
    offerFeed('wrong')->assertUnauthorized();
});

it('stays closed when no token is configured, even to an empty bearer', function () {
    config(['services.veta.feed_token' => null]);

    offerFeed('')->assertUnauthorized();
});

it('quotes the same prices the checkout charges', function () {
    $facts = offerFeed()->assertOk()->json();

    expect($facts['accelerator.price_full.NGN'])
        ->toBe('₦'.number_format(Accelerator::fullPrice('NGN')))
        ->and($facts['accelerator.price_regular.NGN'])->toBe('₦'.number_format(Accelerator::regularFullPrice('NGN')))
        ->and($facts['accelerator.installment_each.USD'])->toBe('$'.number_format(Accelerator::installmentEach('USD')))
        ->and($facts['accelerator.cohort_number'])->toBe(Accelerator::cohortNumber())
        ->and($facts['accelerator.installment_count'])->toBe(Accelerator::installmentCount());
});

it('only lists currencies the checkout can actually sell', function () {
    config(['accelerator.currencies.XOF' => [
        'symbol' => 'CFA', 'provider' => 'flutterwave',
        'price_full' => 900, 'price_earlybird' => 800, 'installment_each' => null,
    ]]);

    expect(offerFeed()->json())->not->toHaveKey('accelerator.price_full.XOF');
});

it('says enrolment is closed when the admin switch is off', function () {
    Accelerator::setRegistrationOpen(false);

    expect(offerFeed()->json()['accelerator.enrolment_open'])->toBeFalse();
});

it('says enrolment is closed once the cart deadline has passed', function () {
    config(['accelerator.cart_closes_at' => '2026-10-01 23:59:59']);
    Carbon::setTestNow(Carbon::parse('2026-10-05 12:00', 'Africa/Lagos'));

    expect(offerFeed()->json()['accelerator.enrolment_open'])->toBeFalse();
});

it('marks a masterclass in the past as not upcoming', function () {
    config(['taab.masterclass.starts_at' => '2026-10-10 09:00', 'taab.masterclass.timezone' => 'Africa/Lagos']);

    Carbon::setTestNow(Carbon::parse('2026-10-09 12:00', 'Africa/Lagos'));
    expect(offerFeed()->json()['masterclass.is_upcoming'])->toBeTrue();

    Carbon::setTestNow(Carbon::parse('2026-10-11 12:00', 'Africa/Lagos'));
    expect(offerFeed()->json()['masterclass.is_upcoming'])->toBeFalse();
});

it('carries only published testimonials, verbatim', function () {
    config(['accelerator.testimonials' => [
        ['name' => 'Ada', 'quote' => 'It works.', 'is_published' => true],
        ['name' => 'Hidden', 'quote' => 'Not consented.', 'is_published' => false],
    ]]);

    $facts = offerFeed()->json();

    expect($facts['accelerator.testimonials_count'])->toBe(1)
        ->and($facts['accelerator.testimonials'])->toBe('- Ada: "It works."');
});

it('reports no testimonials as null rather than an empty string', function () {
    config(['accelerator.testimonials' => []]);

    expect(offerFeed()->json()['accelerator.testimonials'])->toBeNull();
});

it('never exposes coupons or community links', function () {
    $body = offerFeed()->getContent();

    foreach (array_keys(config('accelerator.coupons', [])) as $code) {
        expect($body)->not->toContain($code);
    }

    expect($body)->not->toContain('t.me')->not->toContain('coupon');
});
