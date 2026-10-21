<?php

/*
 * The guides are gated (see GuideAccessTest) - a signed-out request gets the sales
 * page, not the guide. Everything here asserts on guide CONTENT, so it runs as an
 * enrolled student.
 */
beforeEach(fn () => test()->actingAs(anEnrolledStudent()));

it('serves both self-hosting guides', function () {
    $this->get('/guides/n8n-on-google-cloud')
        ->assertOk()
        ->assertSee('Google Cloud', false);

    $this->get('/guides/n8n-on-hostinger')
        ->assertOk()
        ->assertSee('Hostinger', false);
});

it('renders the shared guide chrome on both pages', function () {
    // The CSS/JS partials are @included — a bad include path would still return 200
    // but ship an unstyled page, so assert something from each partial is present.
    foreach (['/guides/n8n-on-google-cloud', '/guides/n8n-on-hostinger'] as $url) {
        $this->get($url)
            ->assertSee('themetoggle', false)      // chrome-css
            ->assertSee('IntersectionObserver', false); // chrome-js
    }
});

it('keeps the two guides cross-linked so a stuck student finds the other route', function () {
    $this->get('/guides/n8n-on-google-cloud')->assertSee('/guides/n8n-on-hostinger', false);
    $this->get('/guides/n8n-on-hostinger')->assertSee('/guides/n8n-on-google-cloud', false);
});

it('embeds the walkthrough video on the Google Cloud guide', function () {
    // The guide resolves the video from config/curriculum.php by lesson id rather than
    // hardcoding it, so a re-record flows through automatically. The flip side is that
    // renaming or removing lesson `module-02-v5` makes the player silently vanish —
    // the page still returns 200, just with no video. Nothing else catches that.
    $lesson = collect(config('curriculum.core', []))
        ->flatMap(fn ($m) => $m['videos'] ?? [])
        ->firstWhere('id', 'module-02-v5');

    expect($lesson)->not->toBeNull('lesson module-02-v5 is gone — the guide embed silently drops with it');
    expect($lesson['video_id'])->not->toBeEmpty();

    $this->get('/guides/n8n-on-google-cloud')
        ->assertOk()
        ->assertSee('iframe.mediadelivery.net', false)
        ->assertSee($lesson['video_id'], false);
});

it('resolves every guide_url referenced by the curriculum', function () {
    $guideUrls = collect(config('curriculum.core', []))
        ->merge(config('curriculum.live', []))
        ->flatMap(fn ($m) => collect($m['videos'] ?? [])->pluck('guide_url'))
        ->filter()
        ->unique()
        ->values();

    expect($guideUrls)->not->toBeEmpty();

    // A typo'd guide_url doesn't error anywhere - it just 404s for the student who
    // clicks "Open the guide". This is the only place that catches it.
    //
    // Only IN-APP paths can be resolved here. A guide_url may also point at something
    // hosted elsewhere (the Module 09 client playbooks live in a Google Doc), and a
    // test must not reach the network to check it - so those are shape-checked instead.
    [$external, $internal] = $guideUrls->partition(fn ($url) => str_starts_with($url, 'http'));

    foreach ($internal as $url) {
        $this->get($url)->assertOk();
    }

    foreach ($external as $url) {
        expect($url)->toStartWith('https://');   // never ship a student an http link
    }
});

it('renders the capstone brief with the shared guide chrome', function () {
    $this->get('/guides/capstone-part1-quote-engine')
        ->assertOk()
        ->assertSee('The Quote Engine', false)
        ->assertSee('themetoggle', false)          // chrome-css
        ->assertSee('IntersectionObserver', false); // chrome-js
});

it('reaches the capstone from the curriculum', function () {
    // The lesson is what students actually click; a renamed route would strand it.
    $lesson = collect(config('curriculum.core', []))
        ->flatMap(fn ($m) => $m['videos'] ?? [])
        ->firstWhere('id', 'module-03-capstone');

    expect($lesson)->not->toBeNull('the capstone lesson is gone from the curriculum');

    $this->get($lesson['guide_url'])->assertOk()->assertSee('Build order', false);
});
