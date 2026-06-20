<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use App\Models\MasterclassRegistration;

new #[Layout('components.layouts.admin', ['title' => 'Masterclass'])] class extends Component {
    use WithPagination;

    #[Url] public string $session = '';
    #[Url] public string $search = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->is_admin, 403);
        $this->session = $this->session ?: (config('taab.masterclass.date') ?? '');
    }

    public function updating($name): void
    {
        if (in_array($name, ['session', 'search'])) $this->resetPage();
    }

    public function with(): array
    {
        $regs = MasterclassRegistration::query()
            ->when($this->session, fn ($q) => $q->where('session_date', $this->session))
            ->when($this->search, fn ($q) => $q->where(fn ($w) =>
                $w->where('first_name', 'like', "%{$this->search}%")->orWhere('last_name', 'like', "%{$this->search}%")->orWhere('email', 'like', "%{$this->search}%")))
            ->orderByDesc('created_at')
            ->paginate(20);

        return [
            'sessions' => MasterclassRegistration::query()->select('session_date')->distinct()->orderByDesc('session_date')->pluck('session_date'),
            'regs' => $regs,
            'total' => $regs->total(),
        ];
    }
}; ?>

<div class="max-w-6xl mx-auto space-y-5">

    <!-- Header -->
    <div class="flex items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-black tracking-tighter text-white">Masterclass registrations</h2>
            <p class="text-[11px] text-zinc-500 mt-0.5">{{ number_format($total) }} {{ \Illuminate\Support\Str::plural('registrant', $total) }}{{ $session ? ' · '.$session : '' }}</p>
        </div>
        <a href="{{ route('admin.masterclass.export') }}"
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
        <select wire:model.live="session" class="bg-zinc-900 border border-zinc-800 text-zinc-300 px-3 py-2.5 rounded-lg text-sm focus:border-cyan-500 focus:ring-0">
            <option value="">All sessions</option>
            @foreach($sessions as $s)<option value="{{ $s }}">{{ $s }}</option>@endforeach
        </select>
    </div>

    <!-- Table -->
    <div class="rounded-2xl border border-zinc-800 overflow-hidden">
        <div class="hidden md:grid grid-cols-12 gap-3 px-5 py-3 bg-zinc-900/60 border-b border-zinc-800 text-[9px] font-black uppercase tracking-widest text-zinc-600">
            <div class="col-span-4">Registrant</div>
            <div class="col-span-3">Background</div>
            <div class="col-span-3">Goal</div>
            <div class="col-span-2">Session</div>
        </div>
        <div class="divide-y divide-zinc-900">
            @forelse($regs as $r)
                <div wire:key="mc-{{ $r->id }}" class="px-4 sm:px-5 py-3.5 md:grid md:grid-cols-12 md:gap-3 md:items-center bg-zinc-900/30">
                    <div class="md:col-span-4 flex items-center gap-3 min-w-0">
                        <x-admin.avatar :name="trim($r->first_name.' '.$r->last_name)" />
                        <div class="min-w-0">
                            <div class="text-sm font-bold text-white truncate">{{ $r->first_name }} {{ $r->last_name }}</div>
                            <div class="text-[11px] text-zinc-500 truncate">{{ $r->email }}</div>
                        </div>
                    </div>
                    <div class="md:col-span-3 text-xs text-zinc-400 md:truncate mt-2 md:mt-0"><span class="md:hidden text-zinc-600 font-mono text-[10px] uppercase tracking-widest mr-1">Background</span>{{ $r->background ?: '—' }}</div>
                    <div class="md:col-span-3 text-xs text-zinc-400 md:truncate mt-1 md:mt-0"><span class="md:hidden text-zinc-600 font-mono text-[10px] uppercase tracking-widest mr-1">Goal</span>{{ $r->goal ?: '—' }}</div>
                    <div class="md:col-span-2 text-[10px] font-mono text-zinc-500 mt-2 md:mt-0">{{ $r->session_date }}</div>
                </div>
            @empty
                <div class="px-5 py-14 text-center bg-zinc-900/30 text-sm text-zinc-500">No registrations for this filter.</div>
            @endforelse
        </div>
    </div>

    <div>{{ $regs->links() }}</div>
</div>
