<?php

use App\Models\Enrollment;
use App\Models\Snippet;
use App\Models\User;
use Livewire\Volt\Volt;

function snippetStudent(): User
{
    $user = User::factory()->create();

    Enrollment::create([
        'full_name' => $user->name,
        'email' => $user->email,
        'payment_reference' => 'TEST_'.uniqid(),
        'amount' => 79000,
        'plan_type' => 'full',
        'cohort' => 2,
        'status' => 'paid',
    ]);

    return $user;
}

beforeEach(fn () => config(['accelerator.cohort_starts_at' => now()->subMonth()]));

it('shows a module snippet only on its own module', function () {
    Snippet::create([
        'title' => 'Qualifier system prompt',
        'body' => 'You are a lead qualifier.',
        'language' => 'prompt',
        'module_id' => 'module-lead-qualifier',
    ]);

    expect(Snippet::visibleFor('module-lead-qualifier'))->toHaveCount(1)
        ->and(Snippet::visibleFor('module-01'))->toHaveCount(0);
});

it('shows a global snippet on every module', function () {
    Snippet::create(['title' => 'House style', 'body' => 'Always...', 'language' => 'text', 'module_id' => null]);

    expect(Snippet::visibleFor('module-01'))->toHaveCount(1)
        ->and(Snippet::visibleFor('module-08'))->toHaveCount(1);
});

it('never shows an unpublished snippet to students', function () {
    Snippet::create([
        'title' => 'Draft prompt',
        'body' => 'not ready',
        'language' => 'prompt',
        'module_id' => 'module-01',
        'is_published' => false,
    ]);

    expect(Snippet::visibleFor('module-01'))->toHaveCount(0);
});

it('orders snippets by position', function () {
    Snippet::create(['title' => 'Second', 'body' => 'b', 'language' => 'text', 'position' => 5]);
    Snippet::create(['title' => 'First', 'body' => 'a', 'language' => 'text', 'position' => 1]);

    expect(Snippet::visibleFor('module-01')->pluck('title')->all())->toBe(['First', 'Second']);
});

it('renders a published snippet on the student dashboard', function () {
    Snippet::create([
        'title' => 'Welcome prompt',
        'body' => 'Copy me into your agent.',
        'language' => 'prompt',
        'module_id' => 'module-01',
    ]);

    $this->actingAs(snippetStudent());

    Volt::test('dashboard.terminal')
        ->assertSee('Welcome prompt')
        ->assertSee('Copy me into your agent.');
});

it('labels a snippet whose module no longer exists instead of showing a raw id', function () {
    $s = Snippet::create(['title' => 'Orphan', 'body' => 'x', 'language' => 'text', 'module_id' => 'module-retired']);

    expect($s->moduleLabel())->toContain('Unknown module');
});

it('lets an admin create, edit, unpublish and delete a snippet', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));

    $component = Volt::test('admin.snippets')
        ->set('title', 'Qualifier prompt')
        ->set('body', 'You are a lead qualifier.')
        ->set('language', 'prompt')
        ->set('moduleId', 'module-lead-qualifier')
        ->call('save')
        ->assertHasNoErrors();

    $snippet = Snippet::sole();
    expect($snippet->title)->toBe('Qualifier prompt')
        ->and($snippet->module_id)->toBe('module-lead-qualifier')
        ->and($snippet->is_published)->toBeTrue();

    // Editing must not silently republish a draft.
    $component->call('togglePublish', $snippet->id);
    $component->call('edit', $snippet->id)->set('title', 'Renamed')->call('save');

    $snippet->refresh();
    expect($snippet->title)->toBe('Renamed')
        ->and($snippet->is_published)->toBeFalse();

    $component->call('delete', $snippet->id);
    expect(Snippet::count())->toBe(0);
});

it('keeps non-admins out of the snippets screen', function () {
    $this->actingAs(snippetStudent());

    $this->get('/admin/snippets')->assertForbidden();
});

it('requires a title and a body', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true]));

    Volt::test('admin.snippets')->call('save')->assertHasErrors(['title', 'body']);
});
