<?php

use App\Models\Resource;
use App\Models\User;
use Livewire\Volt\Volt;

it('floats a pinned resource above the sort order in published()', function () {
    Resource::create(['title' => 'Normal', 'url' => 'https://a.test', 'is_published' => true, 'sort_order' => 1]);
    $pinned = Resource::create(['title' => 'Pinned', 'url' => 'https://b.test', 'is_published' => true, 'sort_order' => 9, 'is_pinned' => true]);

    expect(Resource::published()->first()->id)->toBe($pinned->id); // pinned wins despite higher sort_order
});

it('lets an admin pin and unpin a resource', function () {
    $u = User::factory()->create();
    $u->forceFill(['is_admin' => true])->save();
    $r = Resource::create(['title' => 'X', 'url' => 'https://x.test', 'is_published' => true]);
    $this->actingAs($u);

    Volt::test('admin.resources')->call('togglePin', $r->id);
    expect($r->fresh()->is_pinned)->toBeTrue();

    Volt::test('admin.resources')->call('togglePin', $r->id);
    expect($r->fresh()->is_pinned)->toBeFalse();
});
