<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\Snippet;

new #[Layout('components.layouts.admin', ['title' => 'Snippets'])] class extends Component {
    public ?int $editingId = null;
    public string $title = '';
    public string $body = '';
    public string $language = 'prompt';
    public string $moduleId = '';   // '' = global (stored as null)
    public int $position = 0;

    public function mount(): void
    {
        abort_unless(auth()->check() && auth()->user()->is_admin, 403);
    }

    protected function rules(): array
    {
        return [
            'title' => 'required|string|max:200',
            'body' => 'required|string|max:20000',
            'language' => 'required|string|in:'.implode(',', array_keys(Snippet::LANGUAGES)),
            'moduleId' => 'nullable|string|max:100',
            'position' => 'integer|min:0|max:999',
        ];
    }

    public function save(): void
    {
        $this->validate();

        Snippet::updateOrCreate(
            ['id' => $this->editingId],
            [
                'title' => $this->title,
                'body' => $this->body,
                'language' => $this->language,
                'module_id' => $this->moduleId !== '' ? $this->moduleId : null,
                'position' => $this->position,
                // New snippets publish immediately; editing never flips the flag,
                // so an unpublished draft stays unpublished.
                'is_published' => $this->editingId
                    ? (Snippet::find($this->editingId)?->is_published ?? true)
                    : true,
            ],
        );

        $this->resetForm();
    }

    public function edit(int $id): void
    {
        $s = Snippet::find($id);
        if (! $s) return;

        $this->editingId = $s->id;
        $this->title = $s->title;
        $this->body = $s->body;
        $this->language = $s->language;
        $this->moduleId = $s->module_id ?? '';
        $this->position = $s->position;
    }

    public function togglePublish(int $id): void
    {
        $s = Snippet::find($id);
        if (! $s) return;

        $s->update(['is_published' => ! $s->is_published]);
    }

    public function delete(int $id): void
    {
        Snippet::find($id)?->delete();

        if ($this->editingId === $id) {
            $this->resetForm();
        }
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'title', 'body', 'position']);
        $this->language = 'prompt';
        $this->moduleId = '';
        $this->resetValidation();
    }

    public function with(): array
    {
        return [
            'snippets' => Snippet::orderBy('position')->orderBy('id')->get(),
            'modules' => collect(config('curriculum.core', []))->pluck('title', 'id')->all(),
            'languages' => Snippet::LANGUAGES,
        ];
    }
}; ?>

<div class="max-w-5xl mx-auto space-y-8">
    <div>
        <h2 class="text-xl font-black tracking-tighter text-white">Snippets</h2>
        <p class="text-[11px] text-zinc-500 mt-0.5">
            Prompts, code and config students can copy from their dashboard. Pin one to a module,
            or leave the module blank to show it on <span class="text-cyan-400">every</span> module.
        </p>
    </div>

    <!-- FORM -->
    <section class="rounded-2xl border border-zinc-800 bg-zinc-900/40 p-5 space-y-4">
        <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-cyan-500">
            {{ $editingId ? 'Edit snippet' : 'New snippet' }}
        </h3>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-[10px] font-black uppercase tracking-widest text-zinc-500 mb-1.5">Title</label>
                <input type="text" wire:model="title" placeholder="e.g. Lead qualifier system prompt"
                       class="w-full bg-zinc-950 border border-zinc-800 text-white p-2.5 rounded-lg text-xs placeholder:text-zinc-700 focus:border-cyan-500 focus:ring-0">
                @error('title') <p class="text-[10px] text-red-500 uppercase font-bold tracking-widest mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-[10px] font-black uppercase tracking-widest text-zinc-500 mb-1.5">Type</label>
                <select wire:model="language" class="w-full bg-zinc-950 border border-zinc-800 text-white p-2.5 rounded-lg text-xs focus:border-cyan-500 focus:ring-0">
                    @foreach($languages as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-black uppercase tracking-widest text-zinc-500 mb-1.5">Module</label>
                <select wire:model="moduleId" class="w-full bg-zinc-950 border border-zinc-800 text-white p-2.5 rounded-lg text-xs focus:border-cyan-500 focus:ring-0">
                    <option value="">All modules (global)</option>
                    @foreach($modules as $id => $label)
                        <option value="{{ $id }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-black uppercase tracking-widest text-zinc-500 mb-1.5">Order</label>
                <input type="number" wire:model="position" min="0"
                       class="w-full bg-zinc-950 border border-zinc-800 text-white p-2.5 rounded-lg text-xs focus:border-cyan-500 focus:ring-0">
            </div>
        </div>

        <div>
            <label class="block text-[10px] font-black uppercase tracking-widest text-zinc-500 mb-1.5">Body</label>
            <textarea wire:model="body" rows="8" placeholder="Paste the prompt, code or JSON exactly as students should copy it."
                      class="w-full bg-zinc-950 border border-zinc-800 text-white p-3 rounded-lg text-[11px] font-mono leading-relaxed placeholder:text-zinc-700 focus:border-cyan-500 focus:ring-0"></textarea>
            @error('body') <p class="text-[10px] text-red-500 uppercase font-bold tracking-widest mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center gap-3">
            <button wire:click="save" wire:loading.attr="disabled"
                    class="px-5 py-2.5 rounded-lg bg-white text-black text-[10px] font-black uppercase tracking-widest hover:bg-cyan-500 transition disabled:opacity-50">
                {{ $editingId ? 'Save changes' : 'Add snippet' }}
            </button>
            @if($editingId)
                <button wire:click="resetForm" class="text-[10px] font-bold uppercase tracking-widest text-zinc-500 hover:text-white transition">Cancel</button>
            @endif
        </div>
    </section>

    <!-- LIST -->
    <section class="space-y-3">
        <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-zinc-500">All snippets ({{ $snippets->count() }})</h3>

        @forelse($snippets as $s)
            <div wire:key="snip-{{ $s->id }}" class="rounded-2xl border border-zinc-800 bg-zinc-900/40 p-5">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-sm font-bold text-white">{{ $s->title }}</span>
                            <span class="text-[9px] font-black uppercase tracking-widest px-2 py-0.5 rounded bg-zinc-800 text-zinc-400">{{ $s->languageLabel() }}</span>
                            @unless($s->is_published)
                                <span class="text-[9px] font-black uppercase tracking-widest px-2 py-0.5 rounded bg-amber-500/15 text-amber-400">Draft</span>
                            @endunless
                        </div>
                        <p class="text-[11px] text-zinc-500 mt-1">{{ $s->moduleLabel() }} &middot; order {{ $s->position }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button wire:click="togglePublish({{ $s->id }})"
                                class="px-3 py-2 rounded-lg border text-[10px] font-black uppercase tracking-widest transition
                                {{ $s->is_published ? 'border-green-500/40 bg-green-500/10 text-green-400' : 'border-zinc-700 text-zinc-400 hover:text-white' }}">
                            {{ $s->is_published ? 'Published' : 'Publish' }}
                        </button>
                        <button wire:click="edit({{ $s->id }})"
                                class="px-3 py-2 rounded-lg bg-zinc-800 border border-zinc-700 text-zinc-300 text-[10px] font-black uppercase tracking-widest hover:border-cyan-500/50 hover:text-cyan-400 transition">
                            Edit
                        </button>
                        <button wire:click="delete({{ $s->id }})" wire:confirm="Delete this snippet permanently?"
                                class="px-3 py-2 rounded-lg bg-zinc-800 border border-zinc-700 text-zinc-500 text-[10px] font-black uppercase tracking-widest hover:border-red-500/50 hover:text-red-400 transition">
                            Delete
                        </button>
                    </div>
                </div>
                <pre class="mt-3 p-3 rounded-lg bg-zinc-950 border border-zinc-800 text-[10px] font-mono text-zinc-400 overflow-x-auto max-h-32 whitespace-pre-wrap break-words">{{ \Illuminate\Support\Str::limit($s->body, 400) }}</pre>
            </div>
        @empty
            <div class="rounded-2xl border border-dashed border-zinc-800 bg-zinc-950/40 p-10 text-center">
                <p class="text-sm text-zinc-500">No snippets yet. Add one above and it appears on the student dashboard.</p>
            </div>
        @endforelse
    </section>
</div>
