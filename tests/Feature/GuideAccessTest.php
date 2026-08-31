<?php

use App\Models\Resource;
use App\Models\ResourcePurchase;
use App\Models\User;

/*
 * The written guides are a PAID resource that Accelerator students read free. They
 * shipped fully public until 24 Aug 2026 by mistake, so these tests exist to make the
 * gate's failure mode loud: a regression here gives away a paid product silently, with
 * every page still returning 200.
 *
 * Assertions therefore check for CONTENT, never just the status code - the locked page
 * is deliberately a 200 sales page, so assertOk() passes either way and proves nothing.
 */

const GUIDE = '/guides/n8n-on-google-cloud';
const ALT_GUIDE = '/guides/n8n-on-hostinger';

/** A phrase that only ever appears in the real guide body, never on the locked page. */
function guideBodyMarker(): string
{
    return 'Before you start';
}

function makeGuideResource(float $price = 15000): Resource
{
    return Resource::create([
        'title' => 'Self-host n8n',
        'url' => GUIDE,
        'price' => $price,
        'is_published' => true,
    ]);
}

function makeGuidePurchase(Resource $resource, string $status = 'paid'): ResourcePurchase
{
    return ResourcePurchase::create([
        'resource_id' => $resource->id,
        'name' => 'Ada Buyer',
        'email' => 'ada'.uniqid().'@example.com',
        'payment_reference' => 'RES_'.strtoupper(uniqid()),
        'access_token' => bin2hex(random_bytes(16)),
        'amount' => 15000,
        'currency' => 'NGN',
        'status' => $status,
        'paid_at' => $status === 'paid' ? now() : null,
    ]);
}

it('does not serve the guides to the public', function () {
    foreach ([GUIDE, ALT_GUIDE] as $url) {
        $this->get($url)
            ->assertOk()                          // a sales page, not a 404
            ->assertDontSee(guideBodyMarker(), false)
            ->assertSee('this guide is for members', false);
    }
});

it('lets an enrolled student read the guides free', function () {
    $this->actingAs(anEnrolledStudent());

    foreach ([GUIDE, ALT_GUIDE] as $url) {
        $this->get($url)->assertOk()->assertSee(guideBodyMarker(), false);
    }
});

it('locks out a logged-in user with no enrolment', function () {
    $this->actingAs(User::factory()->create());

    $this->get(GUIDE)->assertDontSee(guideBodyMarker(), false);
});

it('locks out a student suspended over an unpaid balance', function () {
    // Same rule as the terminal: an overdue balance pauses everything, guides included,
    // and clearing it restores them.
    $this->actingAs(anEnrolledStudent(['access_suspended' => true]));

    $this->get(GUIDE)->assertDontSee(guideBodyMarker(), false);
});

it('lets an admin in without an enrolment', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));

    $this->get(GUIDE)->assertOk()->assertSee(guideBodyMarker(), false);
});

it('unlocks the guide for a buyer arriving with their access token', function () {
    $purchase = makeGuidePurchase(makeGuideResource());

    // The token is accepted once, then stripped from the URL so it can't be shared
    // around by copy-paste or leak through a Referer header.
    $this->get(GUIDE.'?t='.$purchase->access_token)->assertRedirect(GUIDE);

    // ...and the session carries it from then on.
    $this->get(GUIDE)->assertOk()->assertSee(guideBodyMarker(), false);
});

it('unlocks BOTH guides from one purchase', function () {
    // One product, two routes - a student who can't get Google to verify them must not
    // have to buy the Hostinger fallback separately.
    $purchase = makeGuidePurchase(makeGuideResource());

    $this->get(GUIDE.'?t='.$purchase->access_token);

    $this->get(ALT_GUIDE)->assertOk()->assertSee(guideBodyMarker(), false);
});

it('refuses an unpaid purchase token', function () {
    $purchase = makeGuidePurchase(makeGuideResource(), status: 'pending');

    $this->get(GUIDE.'?t='.$purchase->access_token)->assertRedirect(GUIDE);
    $this->get(GUIDE)->assertDontSee(guideBodyMarker(), false);
});

it('refuses a made-up token', function () {
    $this->get(GUIDE.'?t='.bin2hex(random_bytes(16)))->assertRedirect(GUIDE);
    $this->get(GUIDE)->assertDontSee(guideBodyMarker(), false);
});

it('re-checks the purchase on every request, so a refund takes effect at once', function () {
    $purchase = makeGuidePurchase($resource = makeGuideResource());

    $this->get(GUIDE.'?t='.$purchase->access_token);
    $this->get(GUIDE)->assertSee(guideBodyMarker(), false);

    // Status is never trusted from the session.
    $purchase->update(['status' => 'refunded']);

    $this->get(GUIDE)->assertDontSee(guideBodyMarker(), false);
});

it('sells the guide on the locked page only when a priced resource exists', function () {
    // Never invent a price: with no Resource row the page points at the Accelerator
    // instead of showing a Buy button.
    $this->get(GUIDE)->assertDontSee('Buy the guide', false);

    $resource = makeGuideResource(15000);

    $this->get(GUIDE)
        ->assertSee('Buy the guide', false)
        ->assertSee('15,000', false)
        ->assertSee(route('resource.buy', $resource), false);
});

it('hands the buyer a tokenised guide link from their access page', function () {
    $purchase = makeGuidePurchase(makeGuideResource());

    // Without the token the button would drop them straight onto the locked page,
    // having just paid.
    $this->get(route('resources.access', $purchase))
        ->assertOk()
        ->assertSee(GUIDE.'?t='.$purchase->access_token, false);
});

it('keeps every gated path listed in config', function () {
    // A guide route added without its path in config/guides.php ships wide open, and
    // nothing else notices.
    $paths = config('guides.gated_paths');

    expect($paths)->toContain(GUIDE)->toContain(ALT_GUIDE);

    foreach (\Illuminate\Support\Facades\Route::getRoutes() as $route) {
        if (! str_starts_with($route->uri(), 'guides/')) {
            continue;
        }

        // in_array + toBeTrue, not toContain: Pest reads a second argument to
        // toContain as another expected VALUE, so the message would silently become
        // part of the assertion and the test would fail for the wrong reason.
        expect(in_array('/'.$route->uri(), $paths, true))->toBeTrue(
            "Route /{$route->uri()} is not listed in config/guides.php, so it ships ungated"
        );
    }
});

/*
 * The capstone brief is course content rather than a sellable guide - there is no
 * Resource row for it - so the only thing standing between it and the public is the
 * middleware plus its entry in config/guides.php. Miss either and it ships wide open
 * at a perfectly normal 200, which is exactly how the other two guides leaked.
 */

const CAPSTONE = '/guides/capstone-part1-quote-engine';

/** Appears only in the real brief, never on the locked page. */
function capstoneBodyMarker(): string
{
    return 'Needs Manual Pricing';
}

it('does not serve the capstone brief to the public', function () {
    $this->get(CAPSTONE)->assertDontSee(capstoneBodyMarker(), false);
});

it('serves the capstone brief to an enrolled student', function () {
    $this->actingAs(anEnrolledStudent());

    $this->get(CAPSTONE)
        ->assertOk()
        ->assertSee(capstoneBodyMarker(), false);
});

it('lists the capstone path as gated', function () {
    // The middleware only protects what config/guides.php names. A route can carry the
    // middleware and still be public if someone forgets this line.
    expect(config('guides.gated_paths'))->toContain(CAPSTONE);
});
