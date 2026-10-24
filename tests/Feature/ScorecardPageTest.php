<?php

/*
 | The readiness scorecard is the top of the TAAB funnel, and its results screen is the
 | only place a lead is told what to do next. Two things about it keep breaking:
 |
 | 1. It used to retype the price and the programme length into its verdict copy, so every
 |    price change left it quoting a dead number (CLAUDE.md: never hardcode pricing in
 |    Blade). It now reads config, and the tests below change config and assert the page
 |    follows - a hardcoded number passes a "does it say 120,000" check but fails these.
 | 2. A "not yet" result must not end in a sales link. The masterclass is clarity-first
 |    and free, so it is offered to every tier while a session is scheduled; once the
 |    session is over the low tier gets no pitch at all.
 |
 | Note the page injects its facts through Blade's @json, which escapes non-ASCII, so the
 | currency symbol lands as ₦ and these assertions match the digits instead.
 */

/** Config for a scheduled, still-open session. */
function scorecardOpenSession(): void
{
    config([
        'taab.masterclass.date' => '2026-06-27',
        'taab.masterclass.timezone' => 'UTC',
        'taab.masterclass.time' => '9:00 AM - 11:00 AM WAT',
        'taab.masterclass.registration_closes' => '2099-12-31',
        'taab.masterclass.starts_at' => '2099-12-31 09:00',
    ]);
}

/** Config for a session that has already run, so registration is shut. */
function scorecardClosedSession(): void
{
    config([
        'taab.masterclass.date' => '2026-06-27',
        'taab.masterclass.timezone' => 'UTC',
        'taab.masterclass.registration_closes' => '2026-06-26',
        'taab.masterclass.starts_at' => '2026-06-27 09:00',
    ]);
}

it('sends every tier to the free masterclass while a session is open', function () {
    scorecardOpenSession();

    $html = $this->get('/taab/scorecard')->assertOk()->getContent();

    // Primary CTA on the results screen.
    expect($html)->toContain('Save my seat at the free masterclass');
    // The bridge step both the ready tier and the softer tiers use.
    expect($html)->toContain('Come to the free masterclass');
    expect($html)->toContain('Come to the free masterclass first');
    // The date comes from config, not from the copy.
    expect($html)->toContain('Saturday 27 June');
});

it('reads the attendee rate and the full price from config rather than the copy', function () {
    scorecardOpenSession();
    config([
        'accelerator.coupons.TAAB100.value.NGN' => 123456,
        'accelerator.price_full' => 998877,
        'accelerator.earlybird_seats' => 0,
    ]);

    $html = $this->get('/taab/scorecard')->assertOk()->getContent();

    expect($html)->toContain('123,456');
    expect($html)->toContain('998,877');
});

it('drops the rate line entirely when TAAB100 has been retired', function () {
    scorecardOpenSession();
    config(['accelerator.coupons' => []]);

    $html = $this->get('/taab/scorecard')->assertOk()->getContent();

    // Still invites them to the session - just makes no claim about a price.
    expect($html)->toContain('Come to the free masterclass');
    expect($html)->toContain('rate: null');
});

it('falls back to the Accelerator and pitches nobody once the session is over', function () {
    scorecardClosedSession();

    $html = $this->get('/taab/scorecard')->assertOk()->getContent();

    expect($html)->toContain('Explore the AI Automation Accelerator');

    // Both branches of enrolStep()/clarityStep() are in the shipped script either way,
    // so the only thing that decides which one a lead sees is this flag. Assert the flag,
    // not the absence of the string - clarityStep() returns null when it is false, which
    // is what keeps a "not yet" result from ending in a pitch.
    expect($html)->toContain('open: false');

    $this->assertStringNotContainsString(
        'Save my seat at the free masterclass',
        $html,
        'The masterclass CTA is server-side and must disappear when registration is closed.'
    );
});

it('quotes the programme length the Accelerator actually runs', function () {
    $html = $this->get('/taab/scorecard')->assertOk()->getContent();

    expect($html)->toContain('for 8 weeks');
    $this->assertStringNotContainsString(
        'for 6 weeks',
        $html,
        'The Accelerator is 8 weeks; the time-commitment question drifted behind it before.'
    );
});

/*
 | The setup/budget questions quote what the course costs beyond tuition, so a wrong
 | number here talks someone out of enrolling over money they never had to spend. Two were
 | wrong at once: voice credits were billed as a cost when Vapi gives every account a free
 | $10 credit, and the domain floor was quoted as a .com when a .site or .online starts
 | around $1 for the first year.
 */
it('does not bill the student for costs they do not carry', function () {
    $html = $this->get('/taab/scorecard')->assertOk()->getContent();

    expect($html)->toContain('free $10 credit Vapi gives you');
    expect($html)->toContain('.site or .online starts from about $1');

    $this->assertStringNotContainsString(
        'of voice credits',
        $html,
        'Vapi includes a free $10 credit - voice credits are not a cost the student carries.'
    );
    $this->assertStringNotContainsString(
        '8k to ',
        $html,
        'The domain floor is a ~$1 .site/.online, not a .com at 8k-15k naira.'
    );
});

it('requires a first name before it will show the results', function () {
    $html = $this->get('/taab/scorecard')->assertOk()->getContent();

    $this->assertStringNotContainsString(
        'First name (optional)',
        $html,
        'The result email opens on the name and a nameless lead cannot be followed up by hand.'
    );
    expect($html)->toContain('Please add your first name');
});

/*
 | The domain price is quoted on five surfaces, and it drifted on all of them at once
 | because each carries its own copy of the sentence. The checkout one matters most: it
 | is inside the no-surprises acknowledgement the buyer ticks, so understating a cost
 | there is the version that ends in a refund request.
 */
it('quotes the same domain floor on every page that names one', function () {
    $pages = [
        '/accelerator' => 'the offer page',
        '/taab/tool-stack' => 'the tool-stack guide',
        '/taab/scorecard' => 'the readiness scorecard',
        '/checkout' => 'the checkout acknowledgement',
    ];

    foreach ($pages as $url => $what) {
        $html = $this->get($url)->assertOk()->getContent();

        $this->assertStringNotContainsString(
            '$10/yr',
            $html,
            "A .com price is quoted as the domain floor on {$what}; a .site/.online starts near \$1."
        );
        $this->assertStringContainsString(
            'first year',
            $html,
            "The domain cost on {$what} must be quoted as a first-year price - these TLDs renew dearer."
        );
    }
});
