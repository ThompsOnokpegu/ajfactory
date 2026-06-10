<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\Checkpoint;

new #[Layout('components.layouts.app')] class extends Component {
    public function mount(): void
    {
        abort_unless(auth()->check() && auth()->user()->is_admin, 403);
    }

    public function approve(int $id): void
    {
        $cp = Checkpoint::find($id);
        if (! $cp) return;

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

        return [
            'moduleTitles' => $titles,
            'pending' => Checkpoint::with('enrollment')
                ->where('status', 'submitted')
                ->orderBy('submitted_at')
                ->get(),
            'recent' => Checkpoint::with('enrollment')
                ->whereIn('status', ['approved', 'rejected'])
                ->latest('reviewed_at')
                ->limit(15)
                ->get(),
        ];
    }
}; ?>

<div class="max-w-4xl mx-auto p-6 lg:p-10 space-y-10">
    <div>
        <h1 class="text-2xl font-black uppercase italic tracking-tighter text-white">Proof Checkpoints</h1>
        <p class="text-sm text-zinc-400 mt-1">Review student build proofs. Approving unlocks the next module for that student.</p>
    </div>

    <!-- PENDING -->
    <section class="space-y-3">
        <h2 class="text-[10px] font-black uppercase tracking-[0.2em] text-cyan-500">Pending review ({{ $pending->count() }})</h2>

        @forelse($pending as $cp)
            <div wire:key="cp-{{ $cp->id }}" x-data="{ rejecting: false, note: '' }"
                 class="rounded-2xl border border-zinc-800 bg-zinc-900/40 p-5">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="text-sm font-bold text-white">{{ $cp->enrollment->full_name ?? 'Unknown' }}</p>
                        <p class="text-[11px] text-zinc-500">{{ $cp->enrollment->email ?? '' }}</p>
                        <p class="text-[11px] font-mono text-cyan-500 mt-1">{{ $moduleTitles[$cp->module_id] ?? $cp->module_id }}</p>
                        <p class="text-[10px] text-zinc-600 mt-1">Submitted {{ optional($cp->submitted_at)->diffForHumans() }}</p>
                    </div>
                    <div class="flex flex-col items-end gap-2">
                        @if($cp->proof_url)
                            <a href="{{ $cp->proof_url }}" target="_blank" rel="noopener"
                               class="text-[11px] font-bold text-cyan-500 hover:underline break-all max-w-[220px] text-right">View proof →</a>
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
                    <input type="text" x-model="note" placeholder="Reason (optional, shown to the student)"
                           class="flex-1 bg-zinc-950 border border-zinc-800 text-white p-2.5 rounded-lg text-xs placeholder:text-zinc-700 focus:border-amber-500 focus:ring-0">
                    <button wire:click="reject({{ $cp->id }}, note)" x-on:click="rejecting = false"
                            class="px-4 py-2.5 rounded-lg bg-amber-500/15 border border-amber-500/40 text-amber-400 text-[10px] font-black uppercase tracking-widest hover:bg-amber-500/25 transition">
                        Confirm reject
                    </button>
                </div>
            </div>
        @empty
            <div class="rounded-2xl border border-dashed border-zinc-800 bg-zinc-950/40 p-8 text-center">
                <p class="text-sm text-zinc-500">No checkpoints awaiting review. 🎉</p>
            </div>
        @endforelse
    </section>

    <!-- RECENTLY REVIEWED -->
    @if($recent->isNotEmpty())
        <section class="space-y-3">
            <h2 class="text-[10px] font-black uppercase tracking-[0.2em] text-zinc-500">Recently reviewed</h2>
            <div class="rounded-2xl border border-zinc-800 bg-zinc-900/20 divide-y divide-zinc-900">
                @foreach($recent as $cp)
                    <div wire:key="recent-{{ $cp->id }}" class="flex items-center justify-between gap-4 px-5 py-3">
                        <div class="min-w-0">
                            <span class="text-xs text-zinc-300">{{ $cp->enrollment->full_name ?? 'Unknown' }}</span>
                            <span class="text-[10px] font-mono text-zinc-600"> · {{ $moduleTitles[$cp->module_id] ?? $cp->module_id }}</span>
                        </div>
                        <span class="text-[9px] font-black uppercase tracking-widest px-2 py-0.5 rounded
                            {{ $cp->status === 'approved' ? 'bg-green-500/10 text-green-500' : 'bg-amber-500/10 text-amber-400' }}">
                            {{ $cp->status }}
                        </span>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</div>
