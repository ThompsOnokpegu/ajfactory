<?php

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

it('resolves every guide_url referenced by the curriculum', function () {
    $guideUrls = collect(config('curriculum.core', []))
        ->merge(config('curriculum.live', []))
        ->flatMap(fn ($m) => collect($m['videos'] ?? [])->pluck('guide_url'))
        ->filter()
        ->unique()
        ->values();

    expect($guideUrls)->not->toBeEmpty();

    // A typo'd guide_url doesn't error anywhere — it just 404s for the student who
    // clicks "Open the guide". This is the only place that catches it.
    foreach ($guideUrls as $url) {
        $this->get($url)->assertOk();
    }
});
