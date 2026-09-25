<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use App\Models\Enrollment;
use App\Support\Progress;

/**
 * Per-student progress, ranked within a cohort.
 *
 * Reads App\Support\Progress so this screen and the student leaderboard can never
 * disagree about who is ahead. Ranking counts only what is VERIFIED - approved
 * checkpoints and code-marked live attendance. Lessons completed is shown because
 * it's useful ("they're watching but not shipping"), but it is self-marked and is
 * deliberately not part of the ranking.
 */
new #[Layout('components.layouts.admin', ['title' => 'Progress'])] class extends Component {
    #[Url] public string $cohort = '';
    #[Url] public string $search = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->is_admin, 403);

        if ($this->cohort === '') {
            $this->cohort = (string) (int) config('accelerator.cohort_number', 1);
        }
    }

    public function with(): array
    {
        $cohort = (int) $this->cohort;

        $rows = Progress::forCohort($cohort);

        if ($this->search !== '') {
            $needle = mb_strtolower($this->search);
            $rows = $rows->filter(fn ($r) =>
                str_contains(mb_strtolower($r['name']), $needle)
                || str_contains(mb_strtolower($r['email']), $needle)
            )->values();
        }

        $coreTotal = count(Progress::coreModuleIds());

        return [
            'rows'         => $rows,
            'coreTotal'    => $coreTotal,
            'lessonTotal'  => Progress::totalLessonCount(),
            'minLive'      => (int) config('accelerator.guarantee_min_live_sessions', 0),
            'cohorts'      => Enrollment::where('status', 'paid')->distinct()->orderBy('cohort')->pluck('cohort'),
            'shipped'      => $rows->sum('approved'),
            'finished'     => $coreTotal > 0 ? $rows->where('approved', $coreTotal)->count() : 0,
            'notStarted'   => $rows->where('approved', 0)->count(),
        ];
    }
}; ?>

<div class="max-w-6xl mx-auto space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h2 class="text-xl font-black tracking-tighter text-white">Student progress</h2>
            <p class="text-[11px] text-zinc-500 mt-0.5">Ranked by approved checkpoints, then live sessions attended - the same order students see on their leaderboard.</p>
        </div>
        <div class="flex items-center gap-2">
            <select wire:model.live="cohort"
                    class="bg-zinc-950 border border-zinc-800 text-white text-xs rounded-lg px-3 py-2 focus:border-cyan-500 focus:ring-0">
                @foreach($cohorts as $c)
                    <option value="{{ $c }}">Cohort {{ $c }}</option>
                @endforeach
            </select>
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Name or email"
                   class="bg-zinc-950 border border-zinc-800 text-white text-xs rounded-lg px-3 py-2 w-48 placeholder:text-zinc-700 focus:border-cyan-500 focus:ring-0">
        </div>
    </div>

    <!-- SUMMARY -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        @php
            $tiles = [
                ['Students', $rows->count(), 'text-white'],
                ['Checkpoints approved', $shipped, 'text-cyan-400'],
                ['Finished all ' . $coreTotal, $finished, 'text-green-500'],
                ['Not shipped yet', $notStarted, $notStarted > 0 ? 'text-amber-400' : 'text-zinc-500'],
            ];
        @endphp
        @foreach($tiles as [$label, $value, $tone])
            <div class="rounded-2xl border border-zinc-800 bg-zinc-900/30 p-4">
                <div class="text-[9px] font-black uppercase tracking-widest text-zinc-500">{{ $label }}</div>
                <div class="text-2xl font-black mt-1 {{ $tone }}">{{ $value }}</div>
            </div>
        @endforeach
    </div>

    <!-- TABLE -->
    <div class="rounded-2xl border border-zinc-800 bg-zinc-900/20 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b border-zinc-800 text-[9px] font-black uppercase tracking-widest text-zinc-500">
                        <th class="px-4 py-3 w-12">#</th>
                        <th class="px-4 py-3">Student</th>
                        <th class="px-4 py-3 whitespace-nowrap">Shipped</th>
                        <th class="px-4 py-3 whitespace-nowrap">Live</th>
                        <th class="px-4 py-3 whitespace-nowrap">Lessons</th>
                        <th class="px-4 py-3 whitespace-nowrap">Last approval</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-900">
                    @forelse($rows as $r)
                        <tr wire:key="p-{{ $r['enrollment_id'] }}" class="hover:bg-zinc-900/40 transition">
                            <td class="px-4 py-3 text-[11px] font-mono font-black {{ $r['rank'] === 1 ? 'text-cyan-400' : 'text-zinc-600' }}">{{ $r['rank'] }}</td>
                            <td class="px-4 py-3 min-w-0">
                                <div class="flex items-center gap-3">
                                    <x-admin.avatar :name="$r['name'] ?: '?'" class="h-7 w-7 text-[10px]" />
                                    <div class="min-w-0">
                                        <p class="text-xs font-bold text-white truncate">{{ $r['name'] ?: 'Unknown' }}</p>
                                        <p class="text-[10px] text-zinc-600 truncate">{{ $r['email'] }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="text-xs font-mono {{ $coreTotal > 0 && $r['approved'] === $coreTotal ? 'text-green-500 font-bold' : ($r['approved'] === 0 ? 'text-amber-400' : 'text-zinc-300') }}">
                                    {{ $r['approved'] }}/{{ $coreTotal }}
                                </span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="text-xs font-mono {{ $minLive > 0 && $r['live'] >= $minLive ? 'text-green-500' : 'text-zinc-400' }}">
                                    {{ $r['live'] }}@if($minLive > 0)/{{ $minLive }}@endif
                                </span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-xs font-mono text-zinc-500">{{ $r['lessons'] }}/{{ $lessonTotal }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-[11px] text-zinc-600">
                                {{ $r['last_approved_at'] ? $r['last_approved_at']->diffForHumans() : '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-sm text-zinc-500">
                                No paid students in this cohort{{ $search ? ' matching that search' : '' }}.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <p class="text-[10px] text-zinc-600 leading-relaxed">
        <strong class="text-zinc-500">Shipped</strong> counts approved proof checkpoints on core modules - the only progress a human has verified.
        <strong class="text-zinc-500">Live</strong> counts sessions marked with the attendance code.
        <strong class="text-zinc-500">Lessons</strong> are self-marked ticks, so treat them as intent, not proof.
    </p>
</div>
