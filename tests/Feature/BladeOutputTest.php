<?php

/*
 * No uncompiled Blade on a public page.
 *
 * A directive whose @ sits straight after a word character - "open@if($x)" - does not
 * compile: Blade matches statements with /\B@\w+/, and the boundary between a word
 * character and the @ makes \B fail, so the directive is emitted as literal text. The
 * page still returns 200, so nothing errors; a visitor just reads
 * "Enrolment is open@if($hasStarted) - the cohort is live..." on the sales page, which
 * is exactly how it shipped.
 *
 * Asserted on rendered output, not on the template: the template looks perfectly
 * reasonable, and the whole trap is that it compiles to itself.
 *
 * NOTE ON THE ASSERTION STYLE. These use assertStringNotContainsString rather than
 * expect()->not->toContain(). Pest's toContain is variadic, so a "helpful" failure
 * message passed as a second argument becomes a second NEEDLE, and negating a
 * multi-needle check passes as soon as any one of them is absent. The first version of
 * this file did exactly that and passed against a page that was visibly broken. If you
 * add a case here, verify it fails before you trust it.
 */

dataset('public pages', [
    'landing'   => '/accelerator',
    'checkout'  => '/checkout',
    'links'     => '/links',
    'taab hub'  => '/taab',
    'resources' => '/free',
]);

it('renders no raw Blade directive', function (string $url) {
    $html = $this->get($url)->assertOk()->getContent();

    foreach (['@if', '@else', '@endif', '@foreach', '@endforeach', '@unless', '@isset', '@php'] as $directive) {
        $this->assertStringNotContainsString(
            $directive,
            $html,
            "{$url} leaked the {$directive} directive to the page - a Blade directive did not compile."
        );
    }
})->with('public pages');

it('renders no unparsed echo braces', function (string $url) {
    // The same class of bug one layer down: a visitor should never read {{ $var }}.
    $html = $this->get($url)->assertOk()->getContent();

    $this->assertDoesNotMatchRegularExpression('/\{\{\s*\$/', $html, "{$url} leaked an unparsed echo.");
})->with('public pages');

it('leaks no TODO placeholder to a visitor', function (string $url) {
    // Placeholders are deliberate in config (better an empty state than invented proof),
    // but they must never reach the page.
    $this->assertStringNotContainsString(
        '{{TODO',
        $this->get($url)->assertOk()->getContent(),
        "{$url} leaked a TODO placeholder."
    );
})->with('public pages');
