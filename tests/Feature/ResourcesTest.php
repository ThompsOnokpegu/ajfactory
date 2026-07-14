<?php

use App\Models\Resource;
use App\Models\User;
use Livewire\Volt\Volt;

it('shows only published resources on the public page', function () {
    Resource::create(['title' => 'Live Workflow', 'url' => 'https://example.com/live', 'is_published' => true]);
    Resource::create(['title' => 'Hidden Draft', 'url' => 'https://example.com/hidden', 'is_published' => false]);

    $this->get('/free')
        ->assertOk()
        ->assertSee('Live Workflow')
        ->assertDontSee('Hidden Draft');
});

it('redirects a published resource and increments its clicks', function () {
    $r = Resource::create(['title' => 'Cheatsheet', 'url' => 'https://example.com/file', 'is_published' => true]);

    $this->get(route('resources.go', $r))
        ->assertRedirect('https://example.com/file');

    expect($r->fresh()->clicks)->toBe(1);
});

it('404s the redirect for an unpublished resource', function () {
    $r = Resource::create(['title' => 'Hidden', 'url' => 'https://example.com/x', 'is_published' => false]);

    $this->get(route('resources.go', $r))->assertNotFound();
    expect($r->fresh()->clicks)->toBe(0);
});

it('blocks non-admins from the admin resources page', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)->get('/admin/resources')->assertForbidden();
});

it('lets an admin create, edit, toggle and delete a resource', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    Volt::test('admin.resources')
        ->set('title', 'Intake Funnel')
        ->set('url', 'https://example.com/intake')
        ->set('category', 'n8n Workflow')
        ->call('save');

    $r = Resource::first();
    expect($r->title)->toBe('Intake Funnel')
        ->and($r->is_published)->toBeTrue();

    Volt::test('admin.resources')->call('togglePublish', $r->id);
    expect($r->fresh()->is_published)->toBeFalse();

    Volt::test('admin.resources')->call('delete', $r->id);
    expect(Resource::count())->toBe(0);
});

it('validates that title and a valid url are required', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    Volt::test('admin.resources')
        ->set('title', '')
        ->set('url', 'not-a-url')
        ->call('save')
        ->assertHasErrors(['title', 'url']);

    expect(Resource::count())->toBe(0);
});
