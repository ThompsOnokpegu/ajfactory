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
        $leads = Student::query()
            ->when($this->source, fn ($q) => $q->where('source', $this->source))
            ->when($this->search, fn ($q) => $q->where(fn ($w) =>
                $w->where('name', 'like', "%{$this->search}%")->orWhere('email', 'like', "%{$this->search}%")))
            ->orderByDesc('created_at')
            ->paginate(20);

        return [
            'sources' => Student::query()->whereNotNull('source')->select('source')->distinct()->orderBy('source')->pluck('source'),
            'leads' => $leads,
            'total' => $leads->total(),
        ];
    }
}; ?>

<div class="max-w-6xl mx-auto space-y-5">

    <!-- Header -->
    <div class="flex items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-black tracking-tighter text-white">Leads &amp; waitlist</h2>
            <p class="text-[11px] text-zinc-500 mt-0.5">{{ number_format($total) }} {{ \Illuminate\Support\Str::plural('lead', $total) }}{{ $source ? ' · '.$source : '' }}</p>
        </div>
        <a href="{{ route('admin.leads.export') }}"
           class="inline-flex items-center gap-2 text-[10px] font-black uppercase tracking-widest px-4 py-2.5 rounded-lg bg-white text-black hover:bg-cyan-500 transition">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Export CSV
        </a>
    </div>

    <!-- Filters -->
    <div class="flex flex-wrap items-center gap-2">
        <div class="relative flex-1 min-w-[220px]">
            <svg class="w-4 h-4 text-zinc-600 absolute left-3 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input wire:model.live.debounce.300ms="search" placeholder="Search name or email…" class="w-full bg-zinc-900 border border-zinc-800 text-white pl-9 pr-3 py-2.5 rounded-lg text-sm focus:border-cyan-500 focus:ring-0 placeholder:text-zinc-600">
        </div>
        <select wire:model.live="source" class="bg-zinc-900 border border-zinc-800 text-zinc-300 px-3 py-2.5 rounded-lg text-sm focus:border-cyan-500 focus:ring-0">
            <option value="">All sources</option>
            @foreach($sources as $s)<option value="{{ $s }}">{{ $s }}</option>@endforeach
        </select>
    </div>

    <!-- Table -->
    <div class="rounded-2xl border border-zinc-800 overflow-hidden">
        <div class="hidden md:grid grid-cols-12 gap-3 px-5 py-3 bg-zinc-900/60 border-b border-zinc-800 text-[9px] font-black uppercase tracking-widest text-zinc-600">
            <div class="col-span-5">Lead</div>
            <div class="col-span-3">WhatsApp</div>
            <div class="col-span-2">Source</div>
            <div class="col-span-2 text-right">Captured</div>
        </div>
        <div class="divide-y divide-zinc-900">
            @forelse($leads as $l)
                <div wire:key="lead-{{ $l->id }}" class="px-4 sm:px-5 py-3.5 md:grid md:grid-cols-12 md:gap-3 md:items-center bg-zinc-900/30">
                    <div class="md:col-span-5 flex items-center gap-3 min-w-0">
                        <x-admin.avatar :name="$l->name" />
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-sm font-bold text-white truncate">{{ $l->name }}</span>
                                <span class="md:hidden text-[9px] font-black uppercase tracking-widest px-2 py-0.5 rounded bg-zinc-800 text-zinc-400">{{ $l->source ?: $l->interest }}</span>
                            </div>
                            <div class="text-[11px] text-zinc-500 truncate">{{ $l->email }}</div>
                        </div>
                    </div>
                    <div class="md:col-span-3 text-xs font-mono text-zinc-500 mt-2 md:mt-0"><span class="md:hidden text-zinc-600 text-[10px] uppercase tracking-widest mr-1">WhatsApp</span>{{ $l->whatsapp ?: '—' }}</div>
                    <div class="hidden md:block md:col-span-2">
                        <span class="text-[9px] font-black uppercase tracking-widest px-2 py-0.5 rounded bg-zinc-800 text-zinc-400">{{ $l->source ?: $l->interest }}</span>
                    </div>
                    <div class="md:col-span-2 md:text-right text-[10px] font-mono text-zinc-600 mt-1 md:mt-0">{{ optional($l->created_at)->format('M j, Y') }}</div>
                </div>
            @empty
                <div class="px-5 py-14 text-center bg-zinc-900/30 text-sm text-zinc-500">No leads yet.</div>
            @endforelse
        </div>
    </div>

    <div>{{ $leads->links() }}</div>
</div>
