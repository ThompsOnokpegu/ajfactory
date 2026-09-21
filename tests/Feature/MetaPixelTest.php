<?php

use App\Models\Enrollment;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

/**
 * The browser half of Meta tracking. META_PIXEL_ID is pinned in phpunit.xml because
 * the partial renders nothing when it is unset - the same CI trap as BUNNY_LIBRARY_ID.
 */
it('renders nothing anywhere when the pixel id is unset', function () {
    config(['services.meta.pixel_id' => null]);

    $this->get('/accelerator')->assertOk()->assertDontSee('fbevents.js')->assertDontSee('fbq(');
    $this->get('/taab')->assertOk()->assertDontSee('fbevents.js');
});

it('fires PageView and ViewContent on the sales page with the sticker price', function () {
    $html = $this->get('/accelerator')->assertOk()->getContent();

    expect($html)->toContain("fbq('init', \"123456789012345\")")
        ->toContain("fbq('track', 'PageView')")
        ->toContain("fbq('track', \"ViewContent\"")
        ->toContain('"value":'.(int) config('accelerator.price_full'))
        ->toContain('"currency":"NGN"')
        ->toContain('facebook.com/tr?id=123456789012345');   // noscript fallback
});

it('fires InitiateCheckout on the checkout page', function () {
    $this->get('/checkout?plan=full')->assertOk()->assertSee('"InitiateCheckout"', false);
});

it('puts the base pixel on every public page group', function (string $path) {
    $this->get($path)->assertOk()->assertSee('fbevents.js', false);
})->with([
    '/', '/builders', '/earn', '/free', '/terms', '/links',
    '/taab', '/taab/scorecard', '/taab/roi-calculator', '/taab/tool-stack',
    '/guides/n8n-on-google-cloud',   // the public locked/sales page for a gated guide
]);

it('never renders the pixel for logged-in students or on personal pages', function () {
    $this->get('/resume')->assertOk()->assertDontSee('fbevents.js');

    $this->actingAs(anEnrolledStudent())->get('/dashboard')->assertOk()->assertDontSee('fbevents.js');

    // A student inside a paid guide is not a prospect either.
    $this->actingAs(anEnrolledStudent())->get('/guides/n8n-on-google-cloud')->assertOk()->assertDontSee('fbevents.js');
});

it('fires the browser Purchase on /thank-you only once the webhook has confirmed the payment', function () {
    $e = Enrollment::create([
        'full_name' => 'Ada Builder', 'email' => 'ada@example.com', 'payment_reference' => 'ACC_t1',
        'amount' => 120000, 'plan_type' => 'full', 'cohort' => 3, 'currency' => 'NGN', 'status' => 'pending',
    ]);

    // Still verifying: base pixel yes, Purchase no.
    $this->get('/thank-you?reference=ACC_t1')->assertOk()
        ->assertSee("fbq('track', 'PageView')", false)
        ->assertDontSee('"Purchase"');

    $e->update(['status' => 'paid', 'paid_at' => now()]);

    $html = $this->get('/thank-you?reference=ACC_t1')->assertOk()->getContent();
    expect($html)->toContain("fbq('track', \"Purchase\"")
        ->toContain('{ eventID: "ACC_t1" }')                // = the Conversions API event_id
        ->toContain('"value":120000')
        ->toContain('"content_ids":["accelerator-full"]')
        ->toContain("'meta_' + \"Purchase\" + '_' + \"ACC_t1\"");   // localStorage refresh guard
});

it('lets the Meta cookies through cookie encryption', function () {
    // EncryptCookies nulls any cookie it cannot decrypt. _fbp/_fbc are written by Meta's
    // JS, so without the exception in bootstrap/app.php the checkout stores null for
    // both - silently, on every enrollment.
    Route::middleware('web')->get('/_meta-probe', fn () => request()->cookie('_fbp').'|'.request()->cookie('_fbc'));

    $this->withUnencryptedCookies(['_fbp' => 'fb.1.1.123', '_fbc' => 'fb.1.1.abc'])
        ->get('/_meta-probe')
        ->assertSee('fb.1.1.123|fb.1.1.abc');
});

it('stores the browser match signals on the pending enrollment at checkout', function () {
    Volt::test('accelerator-checkout')
        ->set('full_name', 'Ada B')->set('email', 'ada@example.com')->set('whatsapp', '+2348000000000')
        ->set('plan', 'full')
        ->set('acknowledged', true)
        ->call('initiatePayment');

    $context = Enrollment::where('email', 'ada@example.com')->firstOrFail()->meta_context;

    expect($context)->toBeArray()
        ->toHaveKeys(['fbp', 'fbc', 'ip', 'ua', 'captured_at'])
        ->and($context['ip'])->toBe('127.0.0.1');
});
