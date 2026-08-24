<?php

use Illuminate\Support\Carbon;

/*
 * /links is the bio-link page pasted into TikTok and Instagram, so it's often the
 * first thing a stranger sees. Two things about it are deliberate and easy to undo
 * by accident, which is why they're pinned here:
 *
 *   1. Every card carries a 12-15 word description.
 *   2. The 1-on-1 coaching card is NOT a link - enquiries come by DM. It looks like
 *      an oversight, so someone will eventually "fix" it into an anchor.
 */

/** The card blurbs, as rendered. */
function linkDescriptions(string $html): array
{
    preg_match_all('#<p class="text-sm text-zinc-400[^"]*">\s*(.+?)\s*</p>#s', $html, $m);

    return array_map(
        fn ($d) => trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($d)))),
        $m[1]
    );
}

it('renders the links page', function () {
    $this->get('/links')->assertOk()->assertSee('AJ Thompson', false);
});

it('gives every link a 12-15 word description', function () {
    $descriptions = linkDescriptions($this->get('/links')->getContent());

    // Guards against the regex silently matching nothing and the test passing empty.
    expect($descriptions)->toHaveCount(5);

    foreach ($descriptions as $d) {
        $words = count(preg_split('/\s+/', $d, -1, PREG_SPLIT_NO_EMPTY));

        expect($words)->toBeGreaterThanOrEqual(12, "Too short ({$words} words): {$d}")
            ->and($words)->toBeLessThanOrEqual(15, "Too long ({$words} words): {$d}");
    }
});

it('never turns the coaching card into a link', function () {
    $html = $this->get('/links')->getContent();

    expect($html)->toContain('1-on-1 Coaching');

    // No anchor anywhere on the page may wrap the coaching card.
    $wrappedInAnchor = preg_match('#<a[^>]*>(?:(?!</a>).)*1-on-1 Coaching#s', $html);

    expect($wrappedInAnchor)->toBe(0, 'The coaching card must stay un-linked - enquiries come by DM');
});

it('shows the coaching price in both currencies', function () {
    $this->get('/links')
        ->assertSee('$300', false)
        ->assertSee('₦400,000', false);
});

it('takes the masterclass date from config, not the markup', function () {
    config([
        'taab.masterclass.date' => '2026-11-07',
        'taab.masterclass.starts_at' => '2026-11-07 14:00',
        'taab.masterclass.registration_closes' => '2026-11-06',
    ]);
    Carbon::setTestNow('2026-10-01 09:00');

    $this->get('/links')->assertSee('Sat 7 Nov', false)->assertSee('2:00 PM WAT', false);

    Carbon::setTestNow();
});

it('takes the cohort and its start date from config', function () {
    config([
        'accelerator.cohort_number' => 9,
        'accelerator.cohort_starts_at' => '2027-01-15',
    ]);

    $this->get('/links')->assertSee('Cohort 9 starts Fri 15 Jan', false);
});

it('says so plainly when a date has not been set', function () {
    // Better an honest "to be announced" than a blank, or worse, a stale date left
    // behind from the previous edition.
    config([
        'taab.masterclass.date' => null,
        'taab.masterclass.starts_at' => null,
        'accelerator.cohort_starts_at' => null,
    ]);

    $this->get('/links')
        ->assertSee('Next date to be announced', false)
        ->assertSee('start date to be announced', false);
});
