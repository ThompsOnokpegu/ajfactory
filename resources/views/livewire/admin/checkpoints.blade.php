<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use App\Models\Checkpoint;

/**
 * Proof checkpoint review.
 *
 * Two lists: everything awaiting review (the work queue, never truncated), and the
 * full reviewed history - searchable, filterable and paginated. The history used to
 * be a hard `limit(15)`, which made anything older than the last fifteen decisions
 * unreachable from the admin.
 *
 * A reviewed checkpoint can be re-decided here. That is deliberate: reversing a
 * mistake re-stamps `reviewed_at`, which raises a fresh notice on the student's
 * dashboard (see Checkpoint::scopeUnseenReview), so they find out the same way they
 * found out the first time.
 */
new #[Layout('components.layouts.admin', ['title' => 'Checkpoints'])] class extends Component {
    use WithPagination;

    #[Url] public string $search = '';
    #[Url] public string $status = '';     // '' = approved + rejected
    #[Url] public string $module = '';
    #[Url] public string $cohort = '';

    public function mount(): void
    {
        abort_unless(auth()->check() && auth()->user()->is_admin, 403);
    }

    public function updating($name): void
    {
        if (in_array($name, ['search', 'status', 'module', 'cohort'], true)) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'status', 'module', 'cohort']);
        $this->resetPage();
    }

    public function approve(int $id): void
    {
        $cp = Checkpoint::find($id);
        if (! $cp) return;

        // student_seen_at is deliberately left alone: reviewed_at moving past it is
        // what tells the student something changed.
        $cp->update(['status' => 'approved', 'note' => null, 'reviewed_at' => now()]);
    }

    public function reject(int $id, ?string $note = null): void
    {
        $cp = Checkpoint::find($id);
        if (! $cp) return;

        $cp->update(['status' => 'rejected', 'note' => $note ?: null, 'reviewed_at' => now()]);
    }

    public function with(): array
    {
        $titles = collect(config('curriculum.core', []))
            ->merge(config('curriculum.live', []))
            ->mapWithKeys(fn ($m) => [$m['id'] => $m['title']])
            ->all();

        $reviewed = Checkpoint::with('enrollment')
            ->when($this->status !== '',
                fn ($q) => $q->where('status', $this->status),
                fn ($q) => $q->whereIn('status', ['approved', 'rejected']))
            ->when($this->module !== '', fn ($q) => $q->where('module_id', $this->module))
            ->when($this->search !== '', fn ($q) => $q->whereHas('enrollment', fn ($e) =>
                $e->where('full_name', 'like', "%{$this->search}%")
                  ->orWhere('email', 'like', "%{$this->search}%")))
            ->when($this->cohort !== '', fn ($q) => $q->whereHas('enrollment', fn ($e) =>
                $e->where('cohort', (int) $this->cohort)))
            ->latest('reviewed_at')
            ->paginate(25);

        return [
            'moduleTitles' => $titles,
            'pending' => Checkpoint::with('enrollment')
                ->where('status', 'submitted')
                ->orderBy('submitted_at')
                ->get(),
            'reviewed' => $reviewed,
            'cohorts' => \App\Models\Enrollment::where('status', 'paid')->distinct()->orderBy('cohort')->pluck('cohort'),
            'hasFilters' => $this->search !== '' || $this->status !== '' || $this->module !== '' || $this->cohort !== '',
        ];
    }
}; ?>

<div class="max-w-5xl mx-auto space-y-8">
    <div>
        <h2 class="text-xl font-black tracking-tighter text-white">Proof checkpoints</h2>
        <p class="text-[11px] text-zinc-500 mt-0.5">Review student build proofs — approving unlocks the next module, and tells the student on their dashboard.</p>
    </div>

    <!-- PENDING -->
    <section class="space-y-3">
        <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-cyan-500">Pending review ({{ $pending->count() }})</h3>

        @forelse($pending as $cp)
            <div wire:key="cp-{{ $cp->id }}" x-data="{ rejecting: false, note: '' }"
                 class="rounded-2xl border border-zinc-800 bg-zinc-900/40 p-5">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="flex items-start gap-3 min-w-0">
                        <x-admin.avatar :name="$cp->enrollment->full_name ?? '?'" />
                        <div class="min-w-0">
                            <p class="text-sm font-bold text-white truncate">{{ $cp->enrollment->full_name ?? 'Unknown' }}</p>
                            <p class="text-[11px] text-zinc-500 truncate">{{ $cp->enrollment->email ?? '' }}</p>
                            <div class="flex items-center gap-2 mt-1.5">
                                <span class="text-[9px] font-black uppercase tracking-widest px-2 py-0.5 rounded bg-cyan-500/10 text-cyan-400">{{ $moduleTitles[$cp->module_id] ?? $cp->module_id }}</span>
                                <span class="text-[10px] text-zinc-600">{{ optional($cp->submitted_at)->diffForHumans() }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-col items-end gap-2">
                        @if($cp->proof_url)
                            <a href="{{ $cp->proof_url }}" target="_blank" rel="noopener"
                               class="inline-flex items-center gap-1 text-[11px] font-bold text-cyan-500 hover:underline break-all max-w-[220px] text-right">
                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                View proof
                            </a>
                        @endif
                        <div class="flex items-center gap-2">
                            <button wire:click="approve({{ $cp->id }})" wire:loading.attr="disabled"
                                    class="px-4 py-2 rounded-lg bg-green-500/15 border border-green-500/40 text-green-400 text-[10px] font-black uppercase tracking-widest hover:bg-green-500/25 transition">
                                Approve
                            </button>
                            <button @click="rejecting = !rejecting"
                                    class="px-4 py-2 rounded-lg bg-zinc-800 border border-zinc-700 text-zinc-300 text-[10px] font-black uppercase tracking-widest hover:border-amber-500/50 hover:text-amber-400 transition">
                                Reject
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Reject note -->
                <div x-show="rejecting" x-cloak class="mt-4 flex flex-col sm:flex-row gap-2">
                    <input type="text" x-model="note" placeholder="Reason — the student reads this on their dashboard"
                           class="flex-1 bg-zinc-950 border border-zinc-800 text-white p-2.5 rounded-lg text-xs placeholder:text-zinc-700 focus:border-amber-500 focus:ring-0">
                    <button wire:click="reject({{ $cp->id }}, note)" x-on:click="rejecting = false"
                            class="px-4 py-2.5 rounded-lg bg-amber-500/15 border border-amber-500/40 text-amber-400 text-[10px] font-black uppercase tracking-widest hover:bg-amber-500/25 transition">
                        Confirm reject
                    </button>
                </div>
            </div>
        @empty
            <div class="rounded-2xl border border-dashed border-zinc-800 bg-zinc-950/40 p-10 text-center">
                <p class="text-sm text-zinc-500">No checkpoints awaiting review. 🎉</p>
            </div>
        @endforelse
    </section>

    <!-- REVIEWED HISTORY -->
    <section class="space-y-3">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-zinc-500">
                Reviewed ({{ number_format($reviewed->total()) }})
            </h3>
            <div class="flex flex-wrap items-center gap-2">
                <input type="search" wire:model.live.debounce.300ms="search" placeholder="Name or email"
                       class="bg-zinc-950 border border-zinc-800 text-white text-xs rounded-lg px-3 py-2 w-44 placeholder:text-zinc-700 focus:border-cyan-500 focus:ring-0">
                <select wire:model.live="status" class="bg-zinc-950 border border-zinc-800 text-white text-xs rounded-lg px-3 py-2 focus:border-cyan-500 focus:ring-0">
                    <option value="">All decisions</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
                <select wire:model.live="module" class="bg-zinc-950 border border-zinc-800 text-white text-xs rounded-lg px-3 py-2 focus:border-cyan-500 focus:ring-0">
                    <option value="">All modules</option>
                    @foreach($moduleTitles as $id => $title)
                        <option value="{{ $id }}">{{ $title }}</option>
                    @endforeach
                </select>
                <select wire:model.live="cohort" class="bg-zinc-950 border border-zinc-800 text-white text-xs rounded-lg px-3 py-2 focus:border-cyan-500 focus:ring-0">
                    <option value="">All cohorts</option>
                    @foreach($cohorts as $c)
                        <option value="{{ $c }}">Cohort {{ $c }}</option>
                    @endforeach
                </select>
                @if($hasFilters)
                    <button wire:click="clearFilters" class="text-[10px] font-black uppercase tracking-widest text-zinc-500 hover:text-cyan-400 px-2 py-2 transition">Clear</button>
                @endif
            </div>
        </div>

        <div class="rounded-2xl border border-zinc-800 bg-zinc-900/20 divide-y divide-zinc-900">
            @forelse($reviewed as $cp)
                <div wire:key="reviewed-{{ $cp->id }}" x-data="{ rejecting: false, note: '' }" class="px-5 py-3">
                    <div class="flex flex-wrap items-center gap-3">
                        <x-admin.avatar :name="$cp->enrollment->full_name ?? '?'" class="h-7 w-7 text-[10px]" />
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-bold text-white truncate">
                                {{ $cp->enrollment->full_name ?? 'Unknown' }}
                                @if($cp->enrollment?->cohort)
                                    <span class="text-[9px] font-mono text-zinc-600">· C{{ $cp->enrollment->cohort }}</span>
                                @endif
                            </p>
                            <p class="text-[10px] font-mono text-zinc-600 truncate">{{ $moduleTitles[$cp->module_id] ?? $cp->module_id }}</p>
                        </div>

                        <span class="text-[10px] text-zinc-600 whitespace-nowrap">{{ optional($cp->reviewed_at)->diffForHumans() }}</span>

                        @if($cp->proof_url)
                            <a href="{{ $cp->proof_url }}" target="_blank" rel="noopener"
                               class="text-[11px] font-bold text-cyan-500 hover:underline whitespace-nowrap">Proof</a>
                        @endif

                        <span class="text-[9px] font-black uppercase tracking-widest px-2 py-0.5 rounded whitespace-nowrap
                            {{ $cp->status === 'approved' ? 'bg-green-500/10 text-green-500' : 'bg-amber-500/10 text-amber-400' }}">
                            {{ $cp->status }}
                        </span>

                        {{-- Correcting a decision re-stamps reviewed_at, which re-notifies the student. --}}
                        @if($cp->status === 'approved')
                            <button @click="rejecting = !rejecting"
                                    class="text-[9px] font-black uppercase tracking-widest text-zinc-600 hover:text-amber-400 transition whitespace-nowrap">Reject</button>
                        @else
                            <button wire:click="approve({{ $cp->id }})"
                                    class="text-[9px] font-black uppercase tracking-widest text-zinc-600 hover:text-green-500 transition whitespace-nowrap">Approve</button>
                        @endif
                    </div>

                    @if($cp->status === 'rejected' && $cp->note)
                        <p class="text-[11px] text-amber-400/80 mt-1.5 pl-10">{{ $cp->note }}</p>
                    @endif

                    <div x-show="rejecting" x-cloak class="mt-3 pl-10 flex flex-col sm:flex-row gap-2">
                        <input type="text" x-model="note" placeholder="Reason — the student reads this on their dashboard"
                               class="flex-1 bg-zinc-950 border border-zinc-800 text-white p-2 rounded-lg text-xs placeholder:text-zinc-700 focus:border-amber-500 focus:ring-0">
                        <button wire:click="reject({{ $cp->id }}, note)" x-on:click="rejecting = false"
                                class="px-4 py-2 rounded-lg bg-amber-500/15 border border-amber-500/40 text-amber-400 text-[10px] font-black uppercase tracking-widest hover:bg-amber-500/25 transition">
                            Confirm reject
                        </button>
                    </div>
                </div>
            @empty
                <div class="px-5 py-12 text-center">
                    <p class="text-sm text-zinc-500">{{ $hasFilters ? 'No checkpoints match those filters.' : 'Nothing reviewed yet.' }}</p>
                </div>
            @endforelse
        </div>

        <div>{{ $reviewed->links() }}</div>
    </section>
</div>
