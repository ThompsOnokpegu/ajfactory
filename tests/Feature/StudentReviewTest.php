<?php

use App\Models\Checkpoint;
use App\Models\Enrollment;
use App\Models\StudentReview;
use App\Models\User;
use Livewire\Volt\Volt;

function makeReviewStudent(int $cohort = 2): User
{
    $user = User::factory()->create(['name' => 'Chidi Okonkwo']);

    Enrollment::create([
        'full_name' => 'Chidi Okonkwo',
        'email' => $user->email,
        'payment_reference' => 'TEST_'.uniqid(),
        'amount' => 79000,
        'plan_type' => 'full',
        'cohort' => $cohort,
        'status' => 'paid',
    ]);

    return $user;
}

function approveModule(User $user, string $moduleId): Enrollment
{
    $enrollment = Enrollment::where('email', $user->email)->firstOrFail();

    Checkpoint::create([
        'enrollment_id' => $enrollment->id,
        'module_id' => $moduleId,
        'status' => 'approved',
        'proof_url' => 'https://loom.com/share/abc',
        'submitted_at' => now(),
        'reviewed_at' => now(),
    ]);

    return $enrollment;
}

beforeEach(function () {
    config(['accelerator.cohort_starts_at' => now()->subMonth()]);
});

it('does not ask for a review before any checkpoint is approved', function () {
    $this->actingAs(makeReviewStudent());

    Volt::test('dashboard.terminal')->assertSet('reviewPrompt', null);
});

it('asks the first-win questions once module 01 is approved', function () {
    $user = makeReviewStudent();
    approveModule($user, 'module-01');
    $this->actingAs($user);

    $prompt = Volt::test('dashboard.terminal')->get('reviewPrompt');

    expect($prompt['key'])->toBe('first-win');
});

it('never asks Cohort 1 — there is no verified ship moment to hang it on', function () {
    $user = makeReviewStudent(1);
    approveModule($user, 'module-01');
    $this->actingAs($user);

    Volt::test('dashboard.terminal')->assertSet('reviewPrompt', null);
});

it('asks the most recent milestone, not a stale earlier one', function () {
    // Read the anchor from config rather than hardcoding a module id — the
    // curriculum gets reordered, and ids are stable keys that no longer match
    // their displayed numbers. CurriculumTest proves the anchor resolves.
    $midpointModule = collect(config('reviews.stages'))->firstWhere('key', 'midpoint')['after_module'];

    $user = makeReviewStudent();
    approveModule($user, 'module-01');
    approveModule($user, $midpointModule);
    $this->actingAs($user);

    $prompt = Volt::test('dashboard.terminal')->get('reviewPrompt');

    expect($prompt['key'])->toBe('midpoint');
});

it('stores a happy review with consent as quotable', function () {
    $user = makeReviewStudent();
    $enrollment = approveModule($user, 'module-01');
    $this->actingAs($user);

    Volt::test('dashboard.terminal')
        ->set('reviewRating', 5)
        ->set('reviewAnswers.before', 'Whether I could do it with no coding background.')
        ->set('reviewAnswers.win', 'A Telegram bot that answers my customers automatically.')
        ->set('reviewConsent', true)
        ->set('reviewCreditAs', 'first')
        ->call('submitReview')
        ->assertHasNoErrors()
        ->assertSet('reviewThanks', true)
        ->assertSet('reviewPrompt', null);

    $review = StudentReview::where('enrollment_id', $enrollment->id)->sole();

    expect($review->status)->toBe('submitted')
        ->and($review->rating)->toBe(5)
        ->and($review->consent_public)->toBeTrue()
        ->and($review->isUsablePublicly())->toBeTrue()
        ->and($review->creditLine())->toBe('Chidi O., Cohort 2')
        ->and($review->answers['win'])->toContain('Telegram bot');
});

it('never marks an unhappy review quotable, even if consent was posted', function () {
    $user = makeReviewStudent();
    $enrollment = approveModule($user, 'module-01');
    $this->actingAs($user);

    Volt::test('dashboard.terminal')
        ->set('reviewRating', 2)
        ->set('reviewAnswers.before', 'Not sure it was worth it.')
        ->set('reviewAnswers.win', 'Not much yet.')
        ->set('reviewAnswers.improve', 'The live sessions clash with my work hours.')
        ->set('reviewConsent', true)   // client says yes; server must still refuse
        ->call('submitReview')
        ->assertHasNoErrors();

    $review = StudentReview::where('enrollment_id', $enrollment->id)->sole();

    expect($review->consent_public)->toBeFalse()
        ->and($review->isUsablePublicly())->toBeFalse()
        ->and($review->credit_as)->toBeNull()
        ->and($review->answers['improve'])->toContain('work hours');
});

it('requires an unhappy student to say what to fix', function () {
    $user = makeReviewStudent();
    approveModule($user, 'module-01');
    $this->actingAs($user);

    Volt::test('dashboard.terminal')
        ->set('reviewRating', 1)
        ->set('reviewAnswers.before', 'x')
        ->set('reviewAnswers.win', 'y')
        ->call('submitReview')
        ->assertHasErrors('reviewAnswers.improve');
});

it('snoozes the ask when dismissed, then stops asking after the limit', function () {
    $user = makeReviewStudent();
    $enrollment = approveModule($user, 'module-01');
    $this->actingAs($user);

    Volt::test('dashboard.terminal')->call('dismissReview')->assertSet('reviewPrompt', null);

    // Still snoozed on the next visit.
    Volt::test('dashboard.terminal')->assertSet('reviewPrompt', null);

    // Snooze window passes — we ask once more.
    $this->travel(config('reviews.snooze_days') + 1)->days();
    Volt::test('dashboard.terminal')
        ->assertSet('reviewPrompt.key', 'first-win')
        ->call('dismissReview');

    // That was the last decline we allow.
    $this->travel(config('reviews.snooze_days') + 1)->days();
    Volt::test('dashboard.terminal')->assertSet('reviewPrompt', null);

    expect(StudentReview::where('enrollment_id', $enrollment->id)->sole()->dismiss_count)
        ->toBe(config('reviews.max_dismissals'));
});

it('does not re-ask a stage the student already answered', function () {
    $user = makeReviewStudent();
    approveModule($user, 'module-01');
    $this->actingAs($user);

    Volt::test('dashboard.terminal')
        ->set('reviewRating', 5)
        ->set('reviewAnswers.before', 'a')
        ->set('reviewAnswers.win', 'b')
        ->call('submitReview');

    // Fresh visit — the panel is gone for good.
    Volt::test('dashboard.terminal')->assertSet('reviewPrompt', null);
});

it('shows admins only consented, happy responses under the quotable filter', function () {
    $happy = makeReviewStudent();
    $sad = makeReviewStudent();

    $happyEnrollment = Enrollment::where('email', $happy->email)->sole();
    $sadEnrollment = Enrollment::where('email', $sad->email)->sole();

    StudentReview::create([
        'enrollment_id' => $happyEnrollment->id,
        'stage' => 'first-win',
        'status' => 'submitted',
        'rating' => 5,
        'answers' => ['win' => 'Shipped a WhatsApp bot in week two.'],
        'consent_public' => true,
        'credit_as' => 'full',
        'submitted_at' => now(),
    ]);

    StudentReview::create([
        'enrollment_id' => $sadEnrollment->id,
        'stage' => 'first-win',
        'status' => 'submitted',
        'rating' => 2,
        'answers' => ['improve' => 'Too fast for me.'],
        'consent_public' => false,
        'submitted_at' => now(),
    ]);

    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    Volt::test('admin.reviews')
        ->assertSee('Shipped a WhatsApp bot')
        ->assertDontSee('Too fast for me')
        ->call('setFilter', 'unhappy')
        ->assertSee('Too fast for me')
        ->assertDontSee('Shipped a WhatsApp bot');
});
