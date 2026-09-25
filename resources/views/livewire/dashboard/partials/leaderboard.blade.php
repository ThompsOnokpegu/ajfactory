{{--
    Cohort leaderboard.

    Ranked by APPROVED checkpoints, then live sessions attended, then who got there
    first - see App\Support\Progress. Completed-lesson ticks are deliberately not
    ranked: they're self-marked and would reward clicking rather than building.

    Shows the top 10 plus the viewer's own row, always, so a student at #17 of 30 can
    see where they stand without the whole cohort being listed bottom-first.
--}}
@if($shipToUnlock && !empty($leaderboard['top']))
    @php
        $youInTop = collect($leaderboard['top'])->contains(fn ($r) => $r['is_you']);
    @endphp

    <div class="border border-zinc-900 rounded-2xl bg-zinc-950/50 overflow-hidden">
        <div class="flex items-center justify-between px-6 py-4 border-b border-zinc-900">
            <div class="flex items-center gap-2">
                <span class="text-[10px] font-black uppercase tracking-[0.2em] text-cyan-500">Cohort {{ $cohort }} Leaderboard</span>
            </div>
            <span class="text-[10px] font-mono text-zinc-600">{{ $leaderboard['total'] }} building</span>
        </div>

        <div class="divide-y divide-zinc-900">
            @foreach($leaderboard['top'] as $row)
                <div wire:key="lb-{{ $row['rank'] }}"
                     class="flex items-center gap-4 px-6 py-3 {{ $row['is_you'] ? 'bg-cyan-500/5' : '' }}">
                    <span class="w-7 shrink-0 text-center text-[11px] font-black font-mono
                        {{ $row['rank'] === 1 ? 'text-cyan-400' : ($row['is_you'] ? 'text-cyan-500' : 'text-zinc-600') }}">
                        {{ $row['rank'] }}
                    </span>
                    <span class="flex-1 min-w-0 truncate text-sm {{ $row['is_you'] ? 'text-white font-bold' : 'text-zinc-300' }}">
                        {{ $row['name'] }}@if($row['is_you'])<span class="text-cyan-500 font-black text-[10px] uppercase tracking-widest"> · you</span>@endif
                    </span>
                    <span class="shrink-0 text-[11px] font-mono text-zinc-500">
                        <span class="{{ $row['approved'] > 0 ? 'text-green-500' : '' }}">{{ $row['approved'] }}</span> shipped
                        <span class="text-zinc-700 mx-1">·</span>
                        {{ $row['live'] }} live
                    </span>
                </div>
            @endforeach

            {{-- Your own row, when you're outside the top 10. --}}
            @if(!$youInTop && $leaderboard['you'])
                <div class="flex items-center gap-4 px-6 py-3 bg-cyan-500/5 border-t border-zinc-800">
                    <span class="w-7 shrink-0 text-center text-[11px] font-black font-mono text-cyan-500">{{ $leaderboard['you']['rank'] }}</span>
                    <span class="flex-1 min-w-0 truncate text-sm text-white font-bold">
                        {{ $leaderboard['you']['name'] }}<span class="text-cyan-500 font-black text-[10px] uppercase tracking-widest"> · you</span>
                    </span>
                    <span class="shrink-0 text-[11px] font-mono text-zinc-500">
                        <span class="{{ $leaderboard['you']['approved'] > 0 ? 'text-green-500' : '' }}">{{ $leaderboard['you']['approved'] }}</span> shipped
                        <span class="text-zinc-700 mx-1">·</span>
                        {{ $leaderboard['you']['live'] }} live
                    </span>
                </div>
            @endif
        </div>

        <p class="px-6 py-3 text-[10px] text-zinc-600 leading-relaxed border-t border-zinc-900">
            Ranked by approved proof checkpoints, then live sessions attended. Ship a module to climb.
        </p>
    </div>
@endif
