<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Bind the Laravel TestCase (and a fresh database) to every Feature test so
| function-style Pest tests get the HTTP/auth helpers and a migrated schema.
|
*/

uses(
    Tests\TestCase::class,
    Illuminate\Foundation\Testing\RefreshDatabase::class,
)->in('Feature');

/**
 * A signed-in Accelerator student, for anything behind an enrolment gate.
 *
 * Lives here rather than in one test file because the written guides became a gated
 * paid resource: GuidesTest needs a student to reach the guide body at all, and
 * GuideAccessTest needs one to prove the gate opens for them.
 */
function anEnrolledStudent(array $overrides = []): App\Models\User
{
    $user = App\Models\User::factory()->create();

    App\Models\Enrollment::create(array_merge([
        'full_name' => $user->name,
        'email' => $user->email,
        'payment_reference' => 'TEST_'.uniqid(),
        'amount' => 79000,
        'plan_type' => 'full',
        'cohort' => (int) config('accelerator.cohort_number', 3),
        'status' => 'paid',
    ], $overrides));

    return $user;
}
