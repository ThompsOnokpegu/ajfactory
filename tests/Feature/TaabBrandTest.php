<?php

/*
 | TAAB ran its own lime #c8f064 / Syne brand until 24 Oct 2026, so the funnel looked
 | like two companies: a registrant left a lime page, got a lime email, and landed on a
 | cyan checkout. The palette now comes from one place per surface - the :root in
 | x-layouts.taab for the pages, and the inline styles in docs/emails/ - which is
 | exactly the kind of thing that drifts back one hardcoded hex at a time.
 |
 | Nothing about a stray lime value is visible in a test run otherwise: the page is a
 | perfect 200 and the email renders fine. It just looks like a different company.
 */

it('serves every TAAB page on the Accelerator accent', function () {
    $urls = ['/taab', '/taab/scorecard', '/taab/roi-calculator', '/taab/tool-stack'];

    foreach ($urls as $url) {
        $html = $this->get($url)->assertOk()->getContent();

        $this->assertStringContainsString(
            '#06b6d4',
            $html,
            "{$url} renders without the brand cyan, so it is not on the shared palette."
        );

        // assertStringNotContainsString, never expect()->not->toContain() with a message:
        // that helper is variadic, so the message becomes a second needle and the
        // negated check passes the moment either one is absent (CLAUDE.md section 3).
        $this->assertStringNotContainsString(
            '#c8f064',
            $html,
            "{$url} still carries the retired lime accent."
        );
        $this->assertStringNotContainsString(
            'Syne',
            $html,
            "{$url} still loads the retired Syne display face."
        );
    }
});

it('leaves no lime in the TAAB views or the email templates', function () {
    // Checked on disk, not through the renderer: a token can sit in a style block that
    // only a branch of the page reaches (the closed-registration waitlist card, a
    // scorecard verdict bucket), so rendering one state proves nothing about the rest.
    $retired = ['#c8f064', '200,240,100', '#f0ede8', '#888096', '#0a0a10', 'DM Sans'];

    $files = array_merge(
        glob(resource_path('views/taab/*.blade.php')),
        [resource_path('views/components/layouts/taab.blade.php')],
        glob(base_path('docs/emails/*.html')),
    );

    foreach ($files as $file) {
        $body = file_get_contents($file);
        $name = basename($file);

        foreach ($retired as $needle) {
            $this->assertStringNotContainsString(
                $needle,
                $body,
                "{$name} still carries the retired TAAB brand value '{$needle}'."
            );
        }
    }
});

it('keeps the hero readiness strip in step with the scorecard dimensions', function () {
    // The strip on /taab claims to show what the session resolves, and names the
    // scorecard's five dimensions to do it. Rename a dimension on one page only and the
    // landing page quietly advertises an assessment that no longer exists.
    $strip = $this->get('/taab')->assertOk()->getContent();
    $card  = $this->get('/taab/scorecard')->assertOk()->getContent();

    preg_match_all('#<div class="r-label">([^<]+)</div>#', $strip, $m);
    $labels = array_values(array_diff(array_map('trim', $m[1]), ['Your verdict']));

    $this->assertCount(5, $labels, 'The readiness strip no longer shows five dimensions.');

    preg_match_all('#<div class="dim-name">([^<]+)</div>#', $card, $d);
    $dimensions = array_map(fn ($x) => html_entity_decode(trim($x)), $d[1]);

    $this->assertCount(5, $dimensions, 'The scorecard no longer scores five dimensions.');

    foreach ($labels as $i => $label) {
        $this->assertStringStartsWith(
            $label,
            $dimensions[$i],
            "Readiness strip position {$i} says '{$label}', but the scorecard scores '{$dimensions[$i]}'."
        );
    }
});
