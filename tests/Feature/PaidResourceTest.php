<?php

use App\Models\Resource;
use App\Models\ResourcePurchase;
use Illuminate\Support\Facades\Http;
use Livewire\Volt\Volt;

function paidResource(array $overrides = []): Resource
{
    return Resource::create(array_merge([
        'title' => 'Guided n8n Setup on Google Cloud',
        'url' => 'https://cal.com/aj/n8n-setup',
        'price' => 20000,
        'is_published' => true,
    ], $overrides));
}

it('knows a resource is paid and prices per currency', function () {
    $r = paidResource(['price' => 20000, 'price_usd' => 14]);
    expect($r->isPaid())->toBeTrue()
        ->and($r->priceFor('NGN'))->toBe(20000.0)
        ->and($r->priceFor('USD'))->toBe(14.0);

    $free = Resource::create(['title' => 'Free thing', 'url' => 'https://x.test', 'is_published' => true]);
    expect($free->isPaid())->toBeFalse()
        ->and($free->priceFor('USD'))->toBeNull();
});

it('buy() creates a pending RES_ purchase and charges the server-side price', function () {
    $r = paidResource();

    Volt::test('resource-checkout', ['resource' => $r])
        ->set('name', 'Ada Builder')->set('email', 'ADA@example.com')
        ->call('buy')
        ->assertDispatched('launch-paystack', fn ($name, $params) =>
            $params[0]['amount'] === 2000000 && str_starts_with($params[0]['reference'], 'RES_'));

    $p = ResourcePurchase::first();
    expect($p->status)->toBe('pending')
        ->and((float) $p->amount)->toBe(20000.0)
        ->and($p->email)->toBe('ada@example.com')       // normalised
        ->and($p->access_token)->not->toBeNull();
});

it('marks the purchase paid and delivers on a verified paystack webhook', function () {
    config(['services.paystack.secret_key' => 'sk_test', 'services.n8n.student_webhook_url' => 'https://n8n.test/hook']);
    $r = paidResource();
    $p = ResourcePurchase::create([
        'resource_id' => $r->id, 'name' => 'Ada', 'email' => 'ada@example.com',
        'payment_reference' => 'RES_abc123', 'access_token' => 'tok123',
        'amount' => 20000, 'currency' => 'NGN', 'status' => 'pending',
    ]);

    Http::fake([
        'api.paystack.co/transaction/verify/*' => Http::response(['data' => ['status' => 'success', 'amount' => 2000000]]),
        'n8n.test/*' => Http::response([]),
    ]);

    $body = json_encode(['event' => 'charge.success', 'data' => ['reference' => 'RES_abc123']]);
    $sig = hash_hmac('sha512', $body, 'sk_test');

    $this->call('POST', '/api/webhooks/paystack', [], [], [],
        ['HTTP_X-PAYSTACK-SIGNATURE' => $sig, 'CONTENT_TYPE' => 'application/json'], $body)->assertOk();

    expect($p->fresh()->status)->toBe('paid')
        ->and($p->fresh()->paid_at)->not->toBeNull();
    Http::assertSent(fn ($req) => str_contains($req->url(), 'n8n.test')
        && $req['type'] === 'resource_purchased'
        && $req['resource_url'] === 'https://cal.com/aj/n8n-setup');
});

it('rejects an amount mismatch and does not deliver', function () {
    config(['services.paystack.secret_key' => 'sk_test', 'services.n8n.student_webhook_url' => 'https://n8n.test/hook']);
    $r = paidResource();
    $p = ResourcePurchase::create([
        'resource_id' => $r->id, 'name' => 'Ada', 'email' => 'ada@example.com',
        'payment_reference' => 'RES_mismatch', 'access_token' => 'tokX',
        'amount' => 20000, 'currency' => 'NGN', 'status' => 'pending',
    ]);
    Http::fake([
        'api.paystack.co/transaction/verify/*' => Http::response(['data' => ['status' => 'success', 'amount' => 500000]]), // ₦5k, not ₦20k
    ]);
    $body = json_encode(['event' => 'charge.success', 'data' => ['reference' => 'RES_mismatch']]);
    $sig = hash_hmac('sha512', $body, 'sk_test');
    $this->call('POST', '/api/webhooks/paystack', [], [], [],
        ['HTTP_X-PAYSTACK-SIGNATURE' => $sig, 'CONTENT_TYPE' => 'application/json'], $body)->assertOk();

    expect($p->fresh()->status)->toBe('amount_mismatch');
    Http::assertNotSent(fn ($req) => str_contains($req->url(), 'n8n.test'));
});

it('gates the access page: link shown only once paid', function () {
    $r = paidResource();
    $p = ResourcePurchase::create([
        'resource_id' => $r->id, 'name' => 'Ada', 'email' => 'ada@example.com',
        'payment_reference' => 'RES_x', 'access_token' => 'sekret-token',
        'amount' => 20000, 'currency' => 'NGN', 'status' => 'pending',
    ]);

    $this->get('/resources/access/sekret-token')->assertOk()
        ->assertDontSee('cal.com/aj/n8n-setup')->assertSee('Confirming');

    $p->update(['status' => 'paid']);
    $this->get('/resources/access/sekret-token')->assertOk()->assertSee('cal.com/aj/n8n-setup');
});

it('never leaks a paid url through /r and 404s the buy page for free resources', function () {
    $paid = paidResource();
    $free = Resource::create(['title' => 'Free', 'url' => 'https://free.test', 'is_published' => true]);

    // /r on a paid resource redirects to checkout, not the gated url.
    $this->get('/r/' . $paid->id)->assertRedirect(route('resource.buy', $paid));
    // The buy page only exists for paid, published resources.
    $this->get('/resources/' . $free->id . '/buy')->assertNotFound();
});
