<?php

use App\Models\Enrollment;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Livewire\Volt\Volt;

function makeInstallment(array $overrides = []): Enrollment
{
    return Enrollment::create(array_merge([
        'full_name' => 'Ada Builder',
        'email' => 'ada@example.com',
        'whatsapp' => '+2348000000000',
        'payment_reference' => 'ACC_' . uniqid(),
        'amount' => 42000,
        'plan_type' => 'installment',
        'amount_total' => 84000,
        'balance_due' => 42000,
        'second_payment_status' => 'pending',
        'currency' => 'NGN',
        'status' => 'paid',
    ], $overrides));
}

it('rejects an unsigned link', function () {
    $e = makeInstallment();
    $this->get(route('installment.pay', $e))->assertForbidden();
});

it('renders the balance on a valid signed link', function () {
    $e = makeInstallment();
    $this->get(URL::signedRoute('installment.pay', ['enrollment' => $e->id]))
        ->assertOk()
        ->assertSee('Clear your balance')
        ->assertSee('42,000');
});

it('generates a fresh reference and launches Paystack on pay', function () {
    $e = makeInstallment(['currency' => 'NGN']);

    Volt::test('installment-pay', ['enrollment' => $e])
        ->call('pay')
        ->assertDispatched('launch-paystack');

    expect($e->fresh()->second_payment_reference)->toStartWith('INST2_');
});

it('launches Flutterwave for a USD installment', function () {
    $e = makeInstallment(['currency' => 'USD', 'balance_due' => 30]);

    Volt::test('installment-pay', ['enrollment' => $e])
        ->call('pay')
        ->assertDispatched('launch-flutterwave');
});

it('shows a cleared state and does not charge when nothing is due', function () {
    $e = makeInstallment(['balance_due' => 0, 'second_payment_status' => 'paid']);

    Volt::test('installment-pay', ['enrollment' => $e])
        ->assertSee('all paid up')
        ->call('pay')
        ->assertNotDispatched('launch-paystack');

    expect($e->fresh()->second_payment_reference)->toBeNull();
});

it('scheduler sends a signed pay link, not a pre-made reference', function () {
    config(['services.n8n.installment_webhook' => 'https://example.test/inst']);
    Http::fake();

    $e = makeInstallment([
        'second_payment_status' => 'pending',
        'second_payment_due_at' => now()->subDay(),
    ]);

    Artisan::call('installments:process');

    expect($e->fresh()->second_payment_status)->toBe('link_sent');
    expect($e->fresh()->second_payment_reference)->toBeNull(); // not pre-generated anymore

    Http::assertSent(fn ($req) => $req['event'] === 'installment_due'
        && str_contains($req['pay_url'], '/installment/' . $e->id . '/pay')
        && str_contains($req['pay_url'], 'signature='));
});
