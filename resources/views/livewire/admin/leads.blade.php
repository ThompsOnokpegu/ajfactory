<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use App\Models\Student;

new #[Layout('components.layouts.admin', ['title' => 'Leads & Waitlist'])] class extends Component {
    use WithPagination;

    #[Url] public string $source = '';
    #[Url] public string $search = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->is_admin, 403);
    }

    public function updating($name): void
    {
        if (in_array($name, ['source', 'search'])) $this->resetPage();
    }

    public function with(): array
    {
        return [
            'sources' => Student::query()->whereNotNull('source')->select('source')->distinct()->orderBy('source')->pluck('source'),
            'leads' => Student::query()
                ->when($this->source, fn ($q) => $q->where('source', $this->source))
                ->when($this->search, fn ($q) => $q->where(fn ($w) =>
                    $w->where('name', 'like', "%{$this->search}%")->orWhere('email', 'like', "%{$this->search}%")))
                ->orderByDesc('created_at')
                ->paginate(20),
        ];
    }
}; ?>

<div class="max-w-6xl mx-auto space-y-6">
    <div class="flex flex-wrap items-center gap-3">
        <input wire:model.live.debounce.300ms="search" placeholder="Search name or email…" class="flex-1 min-w-[200px] bg-zinc-900 border border-zinc-800 text-white p-3 rounded-lg text-sm focus:border-cyan-500 focus:ring-0 placeholder:text-zinc-600">
        <select wire:model.live="source" class="bg-zinc-900 border border-zinc-800 text-zinc-300 p-3 rounded-lg text-sm focus:border-cyan-500 focus:ring-0">
            <option value="">All sources</option>
            @foreach($sources as $s)<option value="{{ $s }}">{{ $s }}</option>@endforeach
        </select>
        <a href="{{ route('admin.leads.export') }}" class="text-[10px] font-black uppercase tracking-widest px-4 py-3 rounded-lg bg-white text-black hover:bg-cyan-500 transition">Export CSV</a>
    </div>

    <div class="rounded-2xl border border-zinc-800 bg-zinc-900/30 overflow-hidden">
        <div class="hidden sm:grid grid-cols-12 gap-2 px-4 py-3 border-b border-zinc-800 text-[9px] font-black uppercase tracking-widest text-zinc-600">
            <div class="col-span-3">Name</div><div class="col-span-4">Email</div><div class="col-span-2">WhatsApp</div><div class="col-span-2">Source</div><div class="col-span-1">Date</div>
        </div>
        @forelse($leads as $l)
            <div wire:key="lead-{{ $l->id }}" class="grid sm:grid-cols-12 gap-2 px-4 py-3 border-b border-zinc-900 text-xs items-center">
                <div class="sm:col-span-3 font-bold text-white">{{ $l->name }}</div>
                <div class="sm:col-span-4 text-zinc-400 truncate">{{ $l->email }}</div>
                <div class="hidden sm:block col-span-2 text-zinc-500 font-mono text-[11px]">{{ $l->whatsapp }}</div>
                <div class="sm:col-span-2"><span class="text-[9px] font-black uppercase tracking-widest px-2 py-0.5 rounded bg-zinc-800 text-zinc-400">{{ $l->source ?: $l->interest }}</span></div>
                <div class="hidden sm:block col-span-1 text-[10px] font-mono text-zinc-600">{{ optional($l->created_at)->format('M j') }}</div>
            </div>
        @empty
            <div class="p-10 text-center text-zinc-500 text-sm">No leads yet.</div>
        @endforelse
    </div>

    <div>{{ $leads->links() }}</div>
</div>
