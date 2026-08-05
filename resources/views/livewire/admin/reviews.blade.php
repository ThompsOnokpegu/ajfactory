<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\StudentReview;

new #[Layout('components.layouts.admin', ['title' => 'Reviews'])] class extends Component {
    public string $filter = 'usable';   // usable | all | unhappy
    public string $stage = 'all';

    public function mount(): void
    {
        abort_unless(auth()->check() && auth()->user()->is_admin, 403);
    }

    public function setFilter(string $filter): void
    {
        $this->filter = $filter;
    }

    public function setStage(string $stage): void
    {
        $this->stage = $stage;
    }

    public function with(): array
    {
        $unhappyAt = (int) config('reviews.unhappy_at_or_below', 3);

        $query = StudentReview::with('enrollment')
            ->where('status', 'submitted')
            ->latest('submitted_at');

        if ($this->stage !== 'all') {
            $query->where('stage', $this->stage);
        }

        match ($this->filter) {
            // Quotable: consented AND happy. This is the only set that may
            // ever end up in Cohort 3 copy.
            'usable' => $query->where('consent_public', true)->where('rating', '>', $unhappyAt),
            'unhappy' => $query->where('rating', '<=', $unhappyAt),
            default => null,
        };

        $all = StudentReview::where('status', 'submitted');

        return [
            'reviews' => $query->get(),
            'stages' => collect(config('reviews.stages', []))->keyBy('key'),
            'questionLabels' => collect(config('reviews.stages', []))
                ->flatMap(fn ($s) => collect($s['questions'])->pluck('label', 'key'))
                ->put('improve', 'What should we fix?')
                ->all(),
            'counts' => [
                'usable' => (clone $all)->where('consent_public', true)->where('rating', '>', $unhappyAt)->count(),
                'unhappy' => (clone $all)->where('rating', '<=', $unhappyAt)->count(),
                'all' => (clone $all)->count(),
            ],
            'unhappyAt' => $unhappyAt,
        ];
    }
}; ?>

<div class="max-w-5xl mx-auto space-y-8">
    <div>
        <h2 class="text-xl font-black tracking-tighter text-white">Student reviews</h2>
        <p class="text-[11px] text-zinc-500 mt-0.5">
            Staged feedback collected in the dashboard after each approved checkpoint.
            Only <span class="text-cyan-400">Quotable</span> responses may be used in marketing — the rest are internal.
        </p>
    </div>

    <!-- FILTERS -->
    <div class="flex flex-wrap items-center gap-2">
        @foreach([['usable', 'Quotable'], ['unhappy', 'Needs a call'], ['all', 'Everything']] as [$key, $label])
            <button wire:click="setFilter('{{ $key }}')"
                    class="px-3 py-2 rounded-lg border text-[10px] font-black uppercase tracking-widest transition
                    {{ $filter === $key
                        ? ($key === 'unhappy' ? 'border-amber-500/50 bg-amber-500/10 text-amber-400' : 'border-cyan-500/40 bg-cyan-500/10 text-cyan-400')
                        : 'border-zinc-800 bg-zinc-900/40 text-zinc-500 hover:text-white' }}">
                {{ $label }} <span class="font-mono opacity-60">{{ $counts[$key] }}</span>
            </button>
        @endforeach

        <span class="mx-1 h-5 w-px bg-zinc-800"></span>

        <button wire:click="setStage('all')"
                class="px-3 py-2 rounded-lg border text-[10px] font-black uppercase tracking-widest transition
                {{ $stage === 'all' ? 'border-zinc-600 bg-zinc-800 text-white' : 'border-zinc-800 bg-zinc-900/40 text-zinc-500 hover:text-white' }}">
            All stages
        </button>
        @foreach($stages as $key => $s)
            <button wire:click="setStage('{{ $key }}')"
                    class="px-3 py-2 rounded-lg border text-[10px] font-black uppercase tracking-widest transition
                    {{ $stage === $key ? 'border-zinc-600 bg-zinc-800 text-white' : 'border-zinc-800 bg-zinc-900/40 text-zinc-500 hover:text-white' }}">
                {{ $key }}
            </button>
        @endforeach
    </div>

    <!-- RESPONSES -->
    <section class="space-y-3">
        @forelse($reviews as $review)
            @php
                $isUnhappy = (int) $review->rating <= $unhappyAt;
                $quotable = $review->isUsablePublicly();
            @endphp

            <div wire:key="review-{{ $review->id }}"
                 class="rounded-2xl border p-5 {{ $isUnhappy ? 'border-amber-500/30 bg-amber-500/5' : 'border-zinc-800 bg-zinc-900/40' }}">

                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="flex items-start gap-3 min-w-0">
                        <x-admin.avatar :name="$review->enrollment->full_name ?? '?'" />
                        <div class="min-w-0">
                            <p class="text-sm font-bold text-white truncate">{{ $review->enrollment->full_name ?? 'Unknown' }}</p>
                            <p class="text-[11px] text-zinc-500 truncate">{{ $review->enrollment->email ?? '' }}</p>
                            <div class="flex flex-wrap items-center gap-2 mt-1.5">
                                <span class="text-[9px] font-black uppercase tracking-widest px-2 py-0.5 rounded bg-zinc-800 text-zinc-400">{{ $review->stage }}</span>
                                <span class="text-[10px] text-zinc-600">{{ optional($review->submitted_at)->diffForHumans() }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col items-end gap-2">
                        <span class="text-[10px] font-black uppercase tracking-widest px-2.5 py-1 rounded
                            {{ $isUnhappy ? 'bg-amber-500/15 text-amber-400' : 'bg-green-500/10 text-green-500' }}">
                            {{ $review->rating }}/5
                        </span>
                        @if($quotable)
                            <span class="text-[9px] font-black uppercase tracking-widest px-2 py-0.5 rounded bg-cyan-500/10 text-cyan-400">Quotable</span>
                            <span class="text-[10px] text-zinc-500 text-right">as “{{ $review->creditLine() }}”</span>
                        @elseif(! $isUnhappy)
                            <span class="text-[9px] font-black uppercase tracking-widest px-2 py-0.5 rounded bg-zinc-800 text-zinc-500">Internal only</span>
                        @endif
                    </div>
                </div>

                <!-- ANSWERS -->
                <div class="mt-4 space-y-3 border-t border-zinc-800/70 pt-4">
                    @foreach(($review->answers ?? []) as $key => $answer)
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-widest {{ $key === 'improve' ? 'text-amber-500' : 'text-zinc-600' }}">
                                {{ $questionLabels[$key] ?? $key }}
                            </p>
                            <p class="text-xs text-zinc-300 mt-1 leading-relaxed whitespace-pre-line">{{ $answer }}</p>
                        </div>
                    @endforeach
                </div>

                @if($isUnhappy)
                    <p class="mt-4 text-[10px] font-bold uppercase tracking-widest text-amber-400">
                        ⚠ Reach out to this student — do not use in marketing.
                    </p>
                @endif
            </div>
        @empty
            <div class="rounded-2xl border border-dashed border-zinc-800 bg-zinc-950/40 p-10 text-center">
                <p class="text-sm text-zinc-500">No responses in this view yet.</p>
            </div>
        @endforelse
    </section>
</div>
