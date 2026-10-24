<?php

/*
 | Missing brand assets fail completely silently. The page is a perfect 200, nothing
 | is logged, and the only symptom is somewhere you have to go and look: the framework's
 | logo in the browser tab, or a blank card when someone pastes the link into WhatsApp.
 |
 | /img/og-preview.jpg was referenced as og:image AND twitter:image by four pages and had
 | never existed at all, so every share of this site since launch rendered without a
 | preview. Nobody noticed because there is nothing to notice. Likewise every public page
 | carries its own <head>, so adding one is the moment the icon gets forgotten - the
 | browser then falls back to its implicit /favicon.ico request and serves whatever the
 | framework shipped.
 |
 | These tests check the files are genuinely on disk, not merely referenced.
 */

it('ships a brand icon set rather than the framework default', function () {
    foreach (['favicon.ico', 'favicon.svg', 'apple-touch-icon.png'] as $file) {
        $this->assertFileExists(public_path($file), "public/{$file} is missing.");
    }

    $this->assertStringContainsString(
        '#06b6d4',
        file_get_contents(public_path('favicon.svg')),
        'favicon.svg carries no cyan, so it is not the brand mark - most likely the framework default came back.'
    );
});

it('points every share image at a file that exists', function () {
    // The TAAB pages are in this list because they were NOT, and that is exactly
    // how /img/taab-og.jpg stayed missing: x-layouts.taab referenced it from the day
    // it was written, behind a {{TODO}} comment nobody circled back to, so every
    // share of /taab, the scorecard, the ROI calculator and the tool stack guide
    // previewed blank. Any new public page belongs here.
    foreach (['/', '/accelerator', '/builders', '/earn',
              '/taab', '/taab/scorecard', '/taab/roi-calculator', '/taab/tool-stack'] as $url) {
        $html = $this->get($url)->assertOk()->getContent();

        preg_match_all('#<meta\s+property="(?:og|twitter):image"\s+content="([^"]+)"#', $html, $m);
        $this->assertNotEmpty($m[1], "{$url} declares no share image at all.");

        foreach (array_unique($m[1]) as $src) {
            $rel = ltrim((string) parse_url($src, PHP_URL_PATH), '/');
            $this->assertFileExists(
                public_path($rel),
                "{$url} points a share image at /{$rel}, which does not exist - the link will preview blank."
            );
        }
    }
});

it('declares the icon on every public page', function () {
    $urls = ['/', '/accelerator', '/checkout', '/taab', '/taab/scorecard', '/taab/tool-stack',
             '/taab/roi-calculator', '/links', '/free', '/terms', '/builders', '/earn', '/verify'];

    foreach ($urls as $url) {
        $this->assertStringContainsString(
            'favicon.svg',
            $this->get($url)->assertOk()->getContent(),
            "{$url} declares no icon, so the browser falls back to its implicit /favicon.ico request."
        );
    }
});
