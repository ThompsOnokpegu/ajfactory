<?php

use App\Models\Checkpoint;
use App\Models\Enrollment;
use Livewire\Volt\Volt;

/*
 * The dashboard card shown in place of the player for a written-guide lesson.
 *
 * Its wording used to be hardcoded and described a follow-along guide, which read as
 * nonsense on the capstone brief ("copy-paste, nothing skipped" for a graded 7-day
 * build). Each lesson can now describe itself, with the old wording as the default.
 */

/** Locate a lesson in the Core Training section, returning [moduleIndex, videoIndex]. */
function lessonPosition(string $lessonId): array
{
    foreach (config('curriculum.core') as $mi => $module) {
        foreach ($module['videos'] ?? [] as $vi => $video) {
            if (($video['id'] ?? null) === $lessonId) {
                return [$mi, $vi, $module['id']];
            }
        }
    }

    throw new RuntimeException("lesson {$lessonId} not found in the curriculum");
}

/** A student with every checkpoint approved, so nothing is locked. */
function studentWithOpenCurriculum(): App\Models\User
{
    config(['accelerator.cohort_starts_at' => now()->subMonth()->toDateString()]);

    $user = anEnrolledStudent();
    $enrollment = Enrollment::where('email', $user->email)->sole();

    foreach (config('curriculum.core') as $module) {
        Checkpoint::create([
            'enrollment_id' => $enrollment->id,
            'module_id' => $module['id'],
            'status' => 'approved',
            'proof_url' => 'https://loom.com/share/x',
            'submitted_at' => now(),
            'reviewed_at' => now(),
        ]);
    }

    return $user;
}

it('describes the capstone as a project brief, not a follow-along guide', function () {
    [$mi, $vi] = lessonPosition('module-03-capstone');

    $this->actingAs(studentWithOpenCurriculum());

    Volt::test('dashboard.terminal')
        ->call('selectVideo', 0, $mi, $vi)
        ->assertSee('Capstone project brief')
        ->assertSee('graded out of 100')
        ->assertSee('Open the brief')
        ->assertDontSee('copy-paste, nothing skipped');
});

it('falls back to the follow-along wording for an ordinary guide lesson', function () {
    [$mi, $vi] = lessonPosition('module-02-guide');

    $this->actingAs(studentWithOpenCurriculum());

    Volt::test('dashboard.terminal')
        ->call('selectVideo', 0, $mi, $vi)
        ->assertSee('Written step-by-step guide')
        ->assertSee('copy-paste, nothing skipped')
        ->assertSee('Open the guide');
});
