<?php

use App\Models\Enrollment;
use App\Support\Accelerator;
use Livewire\Volt\Volt;

beforeEach(function () {
    config(['accelerator.coupons' => [
        'TAAB25'   => ['type' => 'percent', 'value' => 25, 'plans' => ['full', 'installment'], 'label' => 'TAAB 25% off'],
        'FULLONLY' => ['type' => 'fixed', 'value' => ['NGN' => 10000], 'plans' => ['full'], 'label' => '₦10k off'],
        'EXPIRED'  => ['type' => 'percent', 'value' => 50, 'expires_at' => '2020-01-01 00:00:00'],
    ]]);
});

it('validates coupons server-side (unknown + expired rejected)', function () {
    expect(Accelerator::coupon('taab25'))->not->toBeNull()   // case-insensitive
        ->and(Accelerator::coupon('nope'))->toBeNull()
        ->and(Accelerator::coupon('EXPIRED'))->toBeNull();
});

it('computes percent and per-currency fixed discounts', function () {
    expect(Accelerator::couponDiscount(Accelerator::coupon('TAAB25'), 80000, 'NGN'))->toBe(20000.0)
        ->and(Accelerator::couponDiscount(Accelerator::coupon('FULLONLY'), 79000, 'NGN'))->toBe(10000.0)
        ->and(Accelerator::couponDiscount(Accelerator::coupon('FULLONLY'), 79000, 'USD'))->toBe(0.0); // no USD value set
});

it('applies a coupon at checkout and discounts the charge', function () {
    $full = Accelerator::fullPrice('NGN');

    $c = Volt::test('accelerator-checkout')
        ->set('plan', 'full')
        ->set('couponCode', 'taab25')
        ->call('applyCoupon');

    expect($c->get('amountToday'))->toBe(round($full * 0.75, 2))
        ->and($c->get('discount'))->toBe(round($full * 0.25, 2));
});

it('stores the coupon + discounted amount on the enrollment (webhook verifies this amount)', function () {
    $full = Accelerator::fullPrice('NGN');

    Volt::test('accelerator-checkout')
        ->set('full_name', 'Ada B')->set('email', 'ada@example.com')->set('whatsapp', '+2348000000000')
        ->set('plan', 'full')
        ->set('couponCode', 'TAAB25')->call('applyCoupon')
        ->set('acknowledged', true)
        ->call('initiatePayment');

    $e = Enrollment::where('email', 'ada@example.com')->first();
    expect($e)->not->toBeNull()
        ->and((float) $e->amount)->toBe(round($full * 0.75, 2))
        ->and($e->coupon_code)->toBe('TAAB25')
        ->and((float) $e->discount_amount)->toBe(round($full * 0.25, 2));
});

it('will not apply a coupon to a plan it does not cover', function () {
    $c = Volt::test('accelerator-checkout')
        ->set('plan', 'installment')
        ->set('couponCode', 'FULLONLY')
        ->call('applyCoupon');

    expect($c->get('coupon'))->toBeNull()
        ->and($c->get('discount'))->toBe(0.0);
});

it('drops the coupon when switching to an ineligible plan', function () {
    $c = Volt::test('accelerator-checkout')
        ->set('plan', 'full')
        ->set('couponCode', 'FULLONLY')->call('applyCoupon');
    expect($c->get('coupon'))->not->toBeNull();

    $c->set('plan', 'installment'); // triggers updatedPlan
    expect($c->get('coupon'))->toBeNull()
        ->and($c->get('discount'))->toBe(0.0);
});
