<?php

/*
 * Integrity guards on the shipped curriculum config.
 *
 * Module ids are STABLE KEYS, not positions: they're stored on Checkpoint rows and
 * inside Enrollment.completed_lessons, and they key accelerator.telegram_threads and
 * reviews.stages[].after_module. Reordering modules therefore changes titles and array
 * order only — never ids. These tests catch the failures that reordering can cause,
 * none of which throw anything at runtime.
 */

function coreModules(): array
{
    return config('curriculum.core', []);
}

it('ships nine core modules — the number the offer sells', function () {
    // The landing page, checkout and emails all promise "9 real automations".
    // If this fails, either the promise moved or a module was merged/dropped by
    // accident — decide deliberately, don't just update the number.
    expect(coreModules())->toHaveCount(9);
});

it('keeps every module id unique', function () {
    $ids = collect(coreModules())->pluck('id');

    expect($ids->all())->toEqual($ids->unique()->values()->all());
});

it('keeps every lesson id unique across the whole curriculum', function () {
    // A duplicate would make completed_lessons ambiguous — ticking one lesson
    // would silently tick another.
    $ids = collect(coreModules())
        ->merge(config('curriculum.live', []))
        ->flatMap(fn ($m) => collect($m['videos'] ?? [])->pluck('id'));

    expect($ids)->not->toBeEmpty();
    expect($ids->all())->toEqual($ids->unique()->values()->all());
});

it('anchors every review stage to a module that still exists', function () {
    // The trap: an after_module pointing at a removed/renamed id throws nothing —
    // that stage simply never fires again, and nobody notices until the reviews
    // don't arrive. This is exactly what folding the hosting module into Deploy
    // would have done to the 'finish' stage.
    $moduleIds = collect(coreModules())->pluck('id')->all();

    expect($moduleIds)->not->toBeEmpty();

    foreach (config('reviews.stages', []) as $stage) {
        expect($moduleIds)->toContain($stage['after_module']);
    }
});

it('points every configured Telegram thread at a real module', function () {
    $moduleIds = collect(coreModules())->pluck('id')->all();

    foreach (array_keys(config('accelerator.telegram_threads', [])) as $moduleId) {
        expect($moduleIds)->toContain($moduleId);
    }
});

it('gives every lesson either a video or a guide', function () {
    foreach (coreModules() as $module) {
        foreach ($module['videos'] ?? [] as $video) {
            $hasVideo = trim((string) ($video['video_id'] ?? '')) !== '';
            $hasGuide = trim((string) ($video['guide_url'] ?? '')) !== '';

            expect($hasVideo || $hasGuide)->toBeTrue(
                "Lesson {$video['id']} has neither a video_id nor a guide_url"
            );
        }
    }
});
