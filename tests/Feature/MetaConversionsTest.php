<?php

use App\Models\Enrollment;
use App\Support\StudentProvisioner;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

/**
 * Server-side Purchase to Meta's Conversions API.
 *
 * Every test fakes graph.facebook.com explicitly: Http::fake() merges stubs, and an
 * un-faked Meta call would hit the network. META_* are pinned in phpunit.xml, the same
 * way the N8N_* URLs are, so "the send is skipped when unconfigured" can't turn these
 * into CI-only failures.
 */
function metaAccepts(): array
{
    return ['graph.facebook.com/*' => Http::response(['events_received' => 1, 'fbtrace_id' => 'abc'])];
}

function metaRequests(): \Illuminate\Support\Collection
{
    return collect(Http::recorded(fn ($req) => str_contains($req->url(), 'graph.facebook.com')))->map(fn ($pair) => $pair[0]);
}

function pendingCheckoutEnrollment(array $overrides = []): Enrollment
{
    return Enrollment::create(array_merge([
        'full_name' => 'Ada Builder', 'email' => 'ada@example.com', 'whatsapp' => '08012345678',
        'payment_reference' => 'ACC_t1', 'amount' => 120000, 'amount_total' => 120000, 'balance_due' => 0,
        'plan_type' => 'full', 'cohort' => 3, 'currency' => 'NGN', 'status' => 'pending',
        'meta_context' => ['fbp' => 'fb.1.1.123', 'fbc' => 'fb.1.1.abc', 'ip' => '105.112.0.1', 'ua' => 'Mozilla/5.0', 'captured_at' => '2026-09-21T10:00:00+01:00'],
    ], $overrides));
}

function paystackChargeSuccess(string $reference): \Illuminate\Testing\TestResponse
{
    config(['services.paystack.secret_key' => 'sk_test']);
    $body = json_encode(['event' => 'charge.success', 'data' => ['reference' => $reference]]);
    $sig = hash_hmac('sha512', $body, 'sk_test');

    return test()->call('POST', '/api/webhooks/paystack', [], [], [],
        ['HTTP_X-PAYSTACK-SIGNATURE' => $sig, 'CONTENT_TYPE' => 'application/json'], $body);
}

it('sends a Purchase to Meta when paystack verifies a first payment, and stamps it', function () {
    $e = pendingCheckoutEnrollment();
    Http::fake(metaAccepts() + [
        'api.paystack.co/transaction/verify/*' => Http::response(['data' => [
            'status' => 'success', 'amount' => 12000000, 'currency' => 'NGN',
            'ip_address' => '197.210.0.9', 'customer' => ['phone' => '+2348099999999'],
            'authorization' => ['country_code' => 'NG'],
        ]]),
        'n8n.test/*' => Http::response([]),
    ]);

    paystackChargeSuccess('ACC_t1')->assertOk();

    $e->refresh();
    expect($e->status)->toBe('paid')
        ->and($e->meta_purchase_sent_at)->not->toBeNull();

    // n8n still gets the enrolment - Meta is additive, never in the way.
    Http::assertSent(fn ($r) => str_contains($r->url(), 'n8n.test') && $r['event'] === 'enrollment_finalized');

    $req = metaRequests()->sole();
    $ev = $req['data'][0];
    expect($req->url())->toBe('https://graph.facebook.com/v25.0/123456789012345/events')
        ->and($req['access_token'])->toBe('test-meta-token')
        ->and($req)->not->toHaveKey('test_event_code')
        ->and($ev['event_name'])->toBe('Purchase')
        ->and($ev['event_id'])->toBe('ACC_t1')                       // = the browser eventID, so Meta dedups
        ->and($ev['action_source'])->toBe('website')
        ->and($ev['event_source_url'])->toBe('https://ajbuildai.com/thank-you')
        ->and($ev['event_time'])->toBe($e->paid_at->getTimestamp())
        ->and($ev['user_data']['em'])->toBe(hash('sha256', 'ada@example.com'))
        ->and($ev['user_data']['ph'])->toBe(hash('sha256', '2348012345678'))   // the checkout number, not the gateway's
        ->and($ev['user_data']['fn'])->toBe(hash('sha256', 'ada'))
        ->and($ev['user_data']['ln'])->toBe(hash('sha256', 'builder'))
        ->and($ev['user_data']['country'])->toBe(hash('sha256', 'ng'))
        ->and($ev['user_data']['fbp'])->toBe('fb.1.1.123')
        ->and($ev['user_data']['fbc'])->toBe('fb.1.1.abc')
        ->and($ev['user_data']['client_ip_address'])->toBe('105.112.0.1')     // checkout IP wins over the gateway's
        ->and($ev['user_data']['client_user_agent'])->toBe('Mozilla/5.0')
        ->and($ev['user_data']['external_id'])->toBe(hash('sha256', 'enrollment:'.$e->id))
        ->and($ev['custom_data'])->toBe([
            'value' => 120000.0, 'currency' => 'NGN', 'content_name' => 'AI Automation Accelerator',
            'content_ids' => ['accelerator-full'], 'content_type' => 'product', 'num_items' => 1, 'order_id' => 'ACC_t1',
        ]);

    // Nothing that identifies the buyer travels un-hashed.
    expect(json_encode($ev['user_data']))->not->toContain('ada@example.com')->not->toContain('2348012345678');
});

it('fills gaps from the gateway payload when the checkout captured nothing', function () {
    $e = pendingCheckoutEnrollment(['meta_context' => null, 'whatsapp' => null, 'currency' => 'USD', 'payment_reference' => 'INT_t2', 'amount' => 87]);
    config(['services.flutterwave.secret_hash' => 'flw-hash', 'services.flutterwave.secret_key' => 'flw_sk']);
    Http::fake(metaAccepts() + [
        'api.flutterwave.com/v3/transactions/*/verify' => Http::response(['data' => [
            'status' => 'successful', 'amount' => 87, 'currency' => 'USD', 'ip' => '41.58.0.7',
            'customer' => ['phone_number' => '+233201234567'], 'card' => ['country' => 'GH'],
        ]]),
        'n8n.test/*' => Http::response([]),
    ]);

    $this->postJson('/api/webhooks/flutterwave', [
        'event' => 'charge.completed', 'data' => ['status' => 'successful', 'tx_ref' => 'INT_t2', 'id' => 555],
    ], ['verif-hash' => 'flw-hash'])->assertOk();

    expect($e->fresh()->meta_purchase_sent_at)->not->toBeNull();

    $ev = metaRequests()->sole()['data'][0];
    expect($ev['event_id'])->toBe('INT_t2')
        ->and($ev['action_source'])->toBe('other')            // no browser context to claim
        ->and($ev)->not->toHaveKey('event_source_url')
        ->and($ev['user_data']['ph'])->toBe(hash('sha256', '233201234567'))
        ->and($ev['user_data']['country'])->toBe(hash('sha256', 'gh'))
        ->and($ev['user_data']['client_ip_address'])->toBe('41.58.0.7')
        ->and($ev['user_data'])->not->toHaveKeys(['fbp', 'fbc', 'client_user_agent'])
        ->and($ev['custom_data']['currency'])->toBe('USD')
        ->and($ev['custom_data']['value'])->toBe(87.0);
});

it('leaves the enrollment paid but unstamped when Meta rejects the event', function () {
    $e = pendingCheckoutEnrollment();
    Http::fake([
        'graph.facebook.com/*' => Http::response(['error' => ['message' => 'Invalid parameter', 'code' => 100, 'error_subcode' => 2804003]], 400),
        'api.paystack.co/transaction/verify/*' => Http::response(['data' => ['status' => 'success', 'amount' => 12000000, 'currency' => 'NGN']]),
        'n8n.test/*' => Http::response([]),
    ]);

    paystackChargeSuccess('ACC_t1')->assertOk();   // the webhook itself never fails over ad tracking

    $e->refresh();
    expect($e->status)->toBe('paid')
        ->and($e->meta_purchase_sent_at)->toBeNull();
});

it('treats a 200 without events_received as a failure', function () {
    $e = pendingCheckoutEnrollment(['status' => 'paid', 'paid_at' => now()]);
    Http::fake(['graph.facebook.com/*' => Http::response(['events_received' => 0])]);

    expect(app(\App\Support\MetaConversions::class)->purchase($e))->toBeFalse()
        ->and($e->fresh()->meta_purchase_sent_at)->toBeNull();
});

it('skips with no HTTP call and no stamp when Meta is not configured', function () {
    config(['services.meta.access_token' => null]);
    $e = pendingCheckoutEnrollment();
    Http::fake([
        'api.paystack.co/transaction/verify/*' => Http::response(['data' => ['status' => 'success', 'amount' => 12000000, 'currency' => 'NGN']]),
        'n8n.test/*' => Http::response([]),
    ]);

    paystackChargeSuccess('ACC_t1')->assertOk();

    expect($e->fresh()->status)->toBe('paid')
        ->and($e->fresh()->meta_purchase_sent_at)->toBeNull();
    Http::assertNotSent(fn ($r) => str_contains($r->url(), 'graph.facebook.com'));
});

it('does not resend an already-stamped purchase', function () {
    $e = pendingCheckoutEnrollment(['status' => 'paid', 'paid_at' => now(), 'meta_purchase_sent_at' => now()->subHour()]);
    Http::fake(metaAccepts());

    expect(app(\App\Support\MetaConversions::class)->purchase($e))->toBeTrue();
    Http::assertNothingSent();
});

it('includes the test event code only while one is configured', function () {
    config(['services.meta.test_event_code' => 'TEST123']);
    $e = pendingCheckoutEnrollment(['status' => 'paid', 'paid_at' => now()]);
    Http::fake(metaAccepts());

    app(\App\Support\MetaConversions::class)->purchase($e);

    expect(metaRequests()->sole()['test_event_code'])->toBe('TEST123');
});

it('reports a manual enrolment as an offline purchase and an approved checkout as a website one', function () {
    Http::fake(metaAccepts() + ['n8n.test/*' => Http::response([])]);

    app(StudentProvisioner::class)->manualEnrol(['name' => 'Tunde Cash', 'email' => 'tunde@example.com', 'amount' => 120000]);

    $pending = pendingCheckoutEnrollment(['email' => 'offline@example.com', 'payment_reference' => 'ACC_off']);
    app(StudentProvisioner::class)->approve($pending);

    $events = metaRequests()->map(fn ($r) => $r['data'][0]);
    expect($events)->toHaveCount(2);

    $manual = $events->firstWhere('user_data.em', hash('sha256', 'tunde@example.com'));
    $approved = $events->firstWhere('event_id', 'ACC_off');
    expect($manual['action_source'])->toBe('other')
        ->and($manual['event_id'])->toStartWith('MAN_')
        ->and($approved['action_source'])->toBe('website')
        ->and($approved['user_data']['fbp'])->toBe('fb.1.1.123');

    expect(Enrollment::where('email', 'tunde@example.com')->value('meta_purchase_sent_at'))->not->toBeNull()
        ->and($pending->fresh()->meta_purchase_sent_at)->not->toBeNull();
});

// ---- meta:retry-purchases ------------------------------------------------------

it('resends only unstamped purchases from the last 7 days', function () {
    Carbon::setTestNow('2026-09-21 12:00:00');
    $recent = pendingCheckoutEnrollment(['payment_reference' => 'ACC_recent', 'email' => 'recent@example.com', 'status' => 'paid', 'paid_at' => now()->subDays(2)]);
    $old = pendingCheckoutEnrollment(['payment_reference' => 'ACC_old', 'email' => 'old@example.com', 'status' => 'paid', 'paid_at' => now()->subDays(9)]);
    $done = pendingCheckoutEnrollment(['payment_reference' => 'ACC_done', 'email' => 'done@example.com', 'status' => 'paid', 'paid_at' => now()->subDay(), 'meta_purchase_sent_at' => now()]);
    pendingCheckoutEnrollment(['payment_reference' => 'ACC_pending', 'email' => 'pending@example.com']);   // never paid
    Http::fake(metaAccepts());

    $this->artisan('meta:retry-purchases')
        ->expectsOutputToContain('1 sent, 0 failed, 1 skipped')
        ->assertSuccessful();

    expect(metaRequests()->map(fn ($r) => $r['data'][0]['event_id'])->all())->toBe(['ACC_recent'])
        ->and($recent->fresh()->meta_purchase_sent_at)->not->toBeNull()
        ->and($old->fresh()->meta_purchase_sent_at)->toBeNull()
        ->and($done->fresh()->meta_purchase_sent_at->toDateTimeString())->toBe('2026-09-21 12:00:00');
});

it('dry-run lists the pending purchases and sends nothing', function () {
    pendingCheckoutEnrollment(['status' => 'paid', 'paid_at' => now()->subDay()]);
    Http::fake(metaAccepts());

    $this->artisan('meta:retry-purchases', ['--dry-run' => true])
        ->expectsOutputToContain('ACC_t1')
        ->expectsOutputToContain('Dry run')
        ->assertSuccessful();

    Http::assertNothingSent();
    expect(Enrollment::first()->meta_purchase_sent_at)->toBeNull();
});

it('fails the run and keeps the row unstamped when Meta is down', function () {
    pendingCheckoutEnrollment(['status' => 'paid', 'paid_at' => now()->subDay()]);
    Http::fake(['graph.facebook.com/*' => Http::response('overloaded', 503)]);

    $this->artisan('meta:retry-purchases')->assertFailed();

    expect(Enrollment::first()->meta_purchase_sent_at)->toBeNull();
});

it('does nothing when Meta is not configured', function () {
    config(['services.meta.pixel_id' => null]);
    pendingCheckoutEnrollment(['status' => 'paid', 'paid_at' => now()->subDay()]);
    Http::fake();

    $this->artisan('meta:retry-purchases')
        ->expectsOutputToContain('not configured')
        ->assertSuccessful();

    Http::assertNothingSent();
});
