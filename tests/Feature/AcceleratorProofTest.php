<?php

/**
 * Guards the Proof section on /accelerator.
 *
 * The section sat commented out in the Blade from launch until 25 Aug 2026 while
 * `testimonials` was empty. That means populating the config array alone changes
 * NOTHING on the page — the two halves have to agree, and neither errors when they
 * don't. These tests assert the rendered page, not the config, so re-commenting the
 * block (or emptying the array) fails loudly instead of quietly shipping a sales page
 * with no proof on it during a launch.
 */

use App\Support\Accelerator;

it('renders published testimonials on the accelerator page', function () {
    $testimonials = Accelerator::publishedTestimonials();

    expect($testimonials)->not->toBeEmpty(); // the config half

    $res = $this->get('/accelerator')->assertOk();

    // the Blade half — the section must actually be uncommented
    $res->assertSee('Builders, not bystanders.', false);

    foreach ($testimonials as $t) {
        $res->assertSee($t['name'], false);
        $res->assertSee(e($t['quote']), false);
    }
});

it('never leaks the empty-state TODO to visitors', function () {
    // The placeholder copy sat as bare text inside the empty-state div, not in a
    // Blade comment — so it would have rendered on the live page the moment the
    // section was enabled with an empty array.
    $this->get('/accelerator')->assertOk()->assertDontSee('TODO');
});

it('falls back to the CTA empty state rather than fabricating proof', function () {
    config(['accelerator.testimonials' => []]);

    $this->get('/accelerator')
        ->assertOk()
        ->assertSee('results land here as builders ship', false)
        ->assertDontSee('Cohort 2', false); // no orphaned credit lines
});
