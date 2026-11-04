<?php

/*
 * The free commands page: the Google Cloud guide's steps and commands, with no video and
 * no teaching. It is the one piece of guide content that is public on purpose, so these
 * tests run signed out, and assert on content because a 200 proves nothing here.
 *
 * Its commands are retyped from the paid guide, and a guide's two halves have drifted
 * before. A viewer pasting a stale command gets a broken server, so the blocks are
 * compared in both directions.
 */

function commandsPageSource(): string
{
    return file_get_contents(resource_path('views/guides/n8n-google-cloud-commands.blade.php'));
}

/** @return string[] Every copyable block on the page, exactly as written in the view. */
function commandBlocks(string $source): array
{
    preg_match_all('#class="console cmd".*?<pre><code>(.*?)</code></pre>#s', $source, $matches);

    return $matches[1];
}

/** The one command the paid guide doesn't have: the video tells non-Lagos viewers to use it. */
function timezoneCommand(): string
{
    return "sed -i 's#Africa/Lagos#America/New_York#' docker-compose.yml";
}

it('shows the commands to a signed-out visitor', function () {
    $this->get('/free/n8n-google-cloud')
        ->assertOk()
        ->assertSee('docker-compose.yml', false)
        ->assertSee('n8n_n8n_data:/data', false)
        ->assertDontSee('See the Accelerator', false);   // the sales page's CTA
});

it('renders the shared guide chrome', function () {
    // A bad include path would still return 200 but ship an unstyled page.
    $this->get('/free/n8n-google-cloud')
        ->assertSee('themetoggle', false)              // chrome-css
        ->assertSee('IntersectionObserver', false);    // chrome-js
});

it('never mentions the Accelerator, the video or another way to host', function () {
    $html = strtolower($this->get('/free/n8n-google-cloud')->assertOk()->getContent());

    foreach (['accelerator', 'module 02', 'mediadelivery', '<iframe', 'n8n-on-hostinger', 'one-click'] as $banned) {
        $this->assertStringNotContainsString($banned, $html, "the free commands page mentions '{$banned}'");
    }
});

it('has every paid guide command, unchanged', function () {
    $free = commandsPageSource();
    $paid = commandBlocks(file_get_contents(resource_path('views/guides/n8n-google-cloud.blade.php')));

    expect($paid)->not->toBeEmpty();

    foreach ($paid as $block) {
        $this->assertStringContainsString($block, $free, 'a paid guide command is missing or changed on the free page');
    }
});

it('adds no command the paid guide does not have, except the timezone line', function () {
    $paid = file_get_contents(resource_path('views/guides/n8n-google-cloud.blade.php'));
    $blocks = array_diff(commandBlocks(commandsPageSource()), [timezoneCommand()]);

    expect($blocks)->not->toBeEmpty();

    foreach ($blocks as $block) {
        $this->assertStringContainsString($block, $paid, 'the free page has a command the paid guide does not');
    }
});

it('runs the timezone line after the compose file exists and before n8n starts', function () {
    $blocks = commandBlocks(commandsPageSource());

    $compose = array_search(true, array_map(fn ($b) => str_contains($b, 'docker-compose.yml &lt;&lt;'), $blocks), true);
    $timezone = array_search(timezoneCommand(), $blocks, true);
    $start = array_search('sudo docker compose up -d', $blocks, true);

    expect($compose)->toBeInt()
        ->and($timezone)->toBe($compose + 1)
        ->and($start)->toBeGreaterThan($timezone);
});

it('keeps the markdown half in step with the page', function () {
    $markdown = file_get_contents(base_path('docs/guides/n8n-google-cloud-commands.md'));

    foreach (commandBlocks(commandsPageSource()) as $block) {
        $this->assertStringContainsString(html_entity_decode(strip_tags($block)), $markdown, 'the markdown half has drifted from the page');
    }
});

it('uses hyphens only', function () {
    $markdown = file_get_contents(base_path('docs/guides/n8n-google-cloud-commands.md'));

    foreach ([commandsPageSource(), $markdown] as $source) {
        $this->assertStringNotContainsString('—', $source, 'em dash in the free commands page');
        $this->assertStringNotContainsString('–', $source, 'en dash in the free commands page');
    }
});
