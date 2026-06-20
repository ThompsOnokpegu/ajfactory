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
        return [
            'sessions' => MasterclassRegistration::query()->select('session_date')->distinct()->orderByDesc('session_date')->pluck('session_date'),
            'regs' => MasterclassRegistration::query()
                ->when($this->session, fn ($q) => $q->where('session_date', $this->session))
                ->when($this->search, fn ($q) => $q->where(fn ($w) =>
                    $w->where('first_name', 'like', "%{$this->search}%")->orWhere('last_name', 'like', "%{$this->search}%")->orWhere('email', 'like', "%{$this->search}%")))
                ->orderByDesc('created_at')
                ->paginate(20),
        ];
    }
}; ?>

<div class="max-w-6xl mx-auto space-y-6">
    <div class="flex flex-wrap items-center gap-3">
        <input wire:model.live.debounce.300ms="search" placeholder="Search name or email…" class="flex-1 min-w-[200px] bg-zinc-900 border border-zinc-800 text-white p-3 rounded-lg text-sm focus:border-cyan-500 focus:ring-0 placeholder:text-zinc-600">
        <select wire:model.live="session" class="bg-zinc-900 border border-zinc-800 text-zinc-300 p-3 rounded-lg text-sm focus:border-cyan-500 focus:ring-0">
            <option value="">All sessions</option>
            @foreach($sessions as $s)<option value="{{ $s }}">{{ $s }}</option>@endforeach
        </select>
        <a href="{{ route('admin.masterclass.export') }}" class="text-[10px] font-black uppercase tracking-widest px-4 py-3 rounded-lg bg-white text-black hover:bg-cyan-500 transition">Export CSV</a>
    </div>

    <div class="rounded-2xl border border-zinc-800 bg-zinc-900/30 overflow-hidden">
        <div class="hidden sm:grid grid-cols-12 gap-2 px-4 py-3 border-b border-zinc-800 text-[9px] font-black uppercase tracking-widest text-zinc-600">
            <div class="col-span-3">Name</div><div class="col-span-3">Email</div><div class="col-span-3">Background</div><div class="col-span-2">Goal</div><div class="col-span-1">Session</div>
        </div>
        @forelse($regs as $r)
            <div wire:key="mc-{{ $r->id }}" class="grid sm:grid-cols-12 gap-2 px-4 py-3 border-b border-zinc-900 text-xs items-center">
                <div class="sm:col-span-3 font-bold text-white">{{ $r->first_name }} {{ $r->last_name }}<div class="sm:hidden text-[11px] text-zinc-500">{{ $r->email }}</div></div>
                <div class="hidden sm:block col-span-3 text-zinc-400 truncate">{{ $r->email }}</div>
                <div class="hidden sm:block col-span-3 text-zinc-500 truncate">{{ $r->background }}</div>
                <div class="hidden sm:block col-span-2 text-zinc-500 truncate">{{ $r->goal }}</div>
                <div class="hidden sm:block col-span-1 text-[10px] font-mono text-zinc-600">{{ $r->session_date }}</div>
            </div>
        @empty
            <div class="p-10 text-center text-zinc-500 text-sm">No registrations.</div>
        @endforelse
    </div>

    <div>{{ $regs->links() }}</div>
</div>
