<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\Resource;

new #[Layout('components.layouts.admin', ['title' => 'Resources'])] class extends Component {
    public ?int $editingId = null;
    public string $title = '';
    public string $description = '';
    public string $category = '';
    public string $url = '';
    public string $emoji = '';
    public int $sortOrder = 0;
    public bool $isPublished = true;

    public function mount(): void
    {
        abort_unless(auth()->user()?->is_admin, 403);
    }

    protected function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'url' => 'required|url|max:2048',
            'description' => 'nullable|string|max:1000',
            'category' => 'nullable|string|max:80',
            'emoji' => 'nullable|string|max:16',
            'sortOrder' => 'integer',
        ];
    }

    public function save(): void
    {
        abort_unless(auth()->user()?->is_admin, 403);
        $this->validate();

        Resource::updateOrCreate(['id' => $this->editingId], [
            'title' => $this->title,
            'description' => $this->description ?: null,
            'category' => $this->category ?: null,
            'url' => $this->url,
            'emoji' => $this->emoji ?: null,
            'sort_order' => $this->sortOrder,
            'is_published' => $this->isPublished,
        ]);

        $this->resetForm();
    }

    public function edit(int $id): void
    {
        $r = Resource::findOrFail($id);
        $this->editingId = $r->id;
        $this->title = $r->title;
        $this->description = (string) $r->description;
        $this->category = (string) $r->category;
        $this->url = $r->url;
        $this->emoji = (string) $r->emoji;
        $this->sortOrder = $r->sort_order;
        $this->isPublished = $r->is_published;
    }

    public function togglePublish(int $id): void
    {
        abort_unless(auth()->user()?->is_admin, 403);
        $r = Resource::find($id);
        $r?->update(['is_published' => ! $r->is_published]);
    }

    public function delete(int $id): void
    {
        abort_unless(auth()->user()?->is_admin, 403);
        Resource::whereKey($id)->delete();
        if ($this->editingId === $id) {
            $this->resetForm();
        }
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'title', 'description', 'category', 'url', 'emoji']);
        $this->sortOrder = 0;
        $this->isPublished = true;
        $this->resetValidation();
    }

    public function with(): array
    {
        return ['resources' => Resource::orderBy('sort_order')->orderByDesc('id')->get()];
    }
}; ?>

<div class="max-w-5xl mx-auto space-y-6">
    <div>
        <h2 class="text-xl font-black tracking-tighter text-white">Free resources</h2>
        <p class="text-[11px] text-zinc-500 mt-0.5">Manage what shows on <a href="/free" target="_blank" class="text-cyan-500 hover:underline">/free</a> — each is a link you paste (Drive, GitHub, Notion…).</p>
    </div>

    <!-- Add / edit form -->
    <div class="rounded-2xl border border-zinc-800 bg-zinc-900/40 p-5">
        <div class="text-[10px] font-black uppercase tracking-widest text-cyan-500 mb-4">{{ $editingId ? 'Edit resource' : 'Add a resource' }}</div>
        <div class="grid sm:grid-cols-2 gap-3">
            <div class="sm:col-span-2">
                <label class="text-[10px] font-black uppercase text-zinc-600 tracking-widest">Title</label>
                <input wire:model="title" type="text" placeholder="Lead Qualifier n8n Workflow" class="mt-1 w-full bg-zinc-950 border border-zinc-800 text-white p-3 rounded-lg text-sm focus:border-cyan-500 focus:ring-0">
                @error('title') <span class="text-[10px] text-red-500 uppercase font-bold">{{ $message }}</span> @enderror
            </div>
            <div class="sm:col-span-2">
                <label class="text-[10px] font-black uppercase text-zinc-600 tracking-widest">Link (URL)</label>
                <input wire:model="url" type="url" placeholder="https://drive.google.com/…" class="mt-1 w-full bg-zinc-950 border border-zinc-800 text-white p-3 rounded-lg text-sm focus:border-cyan-500 focus:ring-0">
                @error('url') <span class="text-[10px] text-red-500 uppercase font-bold">{{ $message }}</span> @enderror
            </div>
            <div class="sm:col-span-2">
                <label class="text-[10px] font-black uppercase text-zinc-600 tracking-widest">Description</label>
                <textarea wire:model="description" rows="2" placeholder="One line on what it is / does." class="mt-1 w-full bg-zinc-950 border border-zinc-800 text-white p-3 rounded-lg text-sm focus:border-cyan-500 focus:ring-0"></textarea>
            </div>
            <div>
                <label class="text-[10px] font-black uppercase text-zinc-600 tracking-widest">Category</label>
                <input wire:model="category" type="text" placeholder="n8n Workflow" class="mt-1 w-full bg-zinc-950 border border-zinc-800 text-white p-3 rounded-lg text-sm focus:border-cyan-500 focus:ring-0">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-[10px] font-black uppercase text-zinc-600 tracking-widest">Emoji</label>
                    <input wire:model="emoji" type="text" placeholder="🧩" class="mt-1 w-full bg-zinc-950 border border-zinc-800 text-white p-3 rounded-lg text-sm focus:border-cyan-500 focus:ring-0">
                </div>
                <div>
                    <label class="text-[10px] font-black uppercase text-zinc-600 tracking-widest">Order</label>
                    <input wire:model="sortOrder" type="number" class="mt-1 w-full bg-zinc-950 border border-zinc-800 text-white p-3 rounded-lg text-sm focus:border-cyan-500 focus:ring-0">
                </div>
            </div>
        </div>
        <div class="flex items-center gap-4 mt-4">
            <label class="flex items-center gap-2 text-xs text-zinc-400 cursor-pointer">
                <input type="checkbox" wire:model="isPublished" class="h-4 w-4 rounded border-zinc-700 bg-zinc-950 text-cyan-500 focus:ring-0"> Published
            </label>
            <div class="flex-1"></div>
            @if($editingId)
                <button wire:click="resetForm" class="text-[10px] font-black uppercase tracking-widest text-zinc-500 hover:text-white">Cancel</button>
            @endif
            <button wire:click="save" wire:loading.attr="disabled" class="px-6 py-2.5 rounded-lg bg-cyan-500 text-black font-black uppercase tracking-widest text-xs hover:bg-white transition">{{ $editingId ? 'Update' : 'Add resource' }}</button>
        </div>
    </div>

    <!-- List -->
    <div class="rounded-2xl border border-zinc-800 divide-y divide-zinc-900 overflow-hidden">
        @forelse($resources as $r)
            <div wire:key="res-{{ $r->id }}" class="flex items-center gap-3 px-4 sm:px-5 py-3.5 bg-zinc-900/30">
                <div class="h-9 w-9 rounded-lg bg-zinc-950 border border-zinc-800 flex items-center justify-center text-lg shrink-0">{{ $r->emoji ?: '📦' }}</div>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-sm font-bold text-white truncate">{{ $r->title }}</span>
                        @if($r->category)<span class="text-[9px] font-black uppercase tracking-widest px-2 py-0.5 rounded bg-zinc-800 text-zinc-400">{{ $r->category }}</span>@endif
                        @unless($r->is_published)<span class="text-[9px] font-black uppercase tracking-widest px-2 py-0.5 rounded bg-amber-500/10 text-amber-400">Hidden</span>@endunless
                    </div>
                    <a href="{{ $r->url }}" target="_blank" rel="noopener" class="text-[11px] text-zinc-500 truncate hover:text-cyan-400 block mt-0.5">{{ $r->url }}</a>
                </div>
                <div class="text-right shrink-0 hidden sm:block">
                    <div class="text-xs font-mono text-zinc-300">{{ $r->clicks }}</div>
                    <div class="text-[9px] uppercase tracking-widest text-zinc-600">clicks</div>
                </div>
                <div class="flex items-center gap-1 shrink-0">
                    <button wire:click="togglePublish({{ $r->id }})" class="p-2 rounded-md text-zinc-500 hover:text-white hover:bg-zinc-800 transition" title="{{ $r->is_published ? 'Unpublish' : 'Publish' }}">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </button>
                    <button wire:click="edit({{ $r->id }})" class="p-2 rounded-md text-zinc-500 hover:text-cyan-400 hover:bg-zinc-800 transition" title="Edit">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                    <button wire:click="delete({{ $r->id }})" wire:confirm="Delete this resource?" class="p-2 rounded-md text-zinc-500 hover:text-red-400 hover:bg-zinc-800 transition" title="Delete">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>
            </div>
        @empty
            <div class="px-5 py-14 text-center bg-zinc-900/30 text-sm text-zinc-500">No resources yet — add your first above.</div>
        @endforelse
    </div>
</div>
