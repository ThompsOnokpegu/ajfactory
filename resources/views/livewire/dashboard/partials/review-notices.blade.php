{{--
    Checkpoint review notices.

    The answer to "has my proof been approved yet?" - shown at the top of the dashboard
    so a student never has to click through modules (or ask in Telegram) to find out.
    Raised by App\Models\Checkpoint::scopeUnseenReview, cleared by dismissReviewNotices.
--}}
@if(!empty($reviewNotices))
    <div class="space-y-3">
        @foreach($reviewNotices as $notice)
            @php $approved = $notice['status'] === 'approved'; @endphp
            <div wire:key="notice-{{ $notice['module_id'] }}"
                 class="rounded-2xl border p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4
                    {{ $approved ? 'border-green-500/40 bg-green-500/5' : 'border-amber-500/40 bg-amber-500/5' }}">
                <div class="min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="h-1.5 w-1.5 rounded-full {{ $approved ? 'bg-green-500' : 'bg-amber-400' }} animate-pulse"></span>
                        <span class="text-[10px] font-black uppercase tracking-widest {{ $approved ? 'text-green-500' : 'text-amber-400' }}">
                            {{ $approved ? 'Checkpoint approved' : 'Checkpoint needs another look' }}
                        </span>
                    </div>
                    <p class="text-sm text-zinc-300 leading-snug">
                        @if($approved)
                            Your proof for <strong class="text-white">{{ $notice['title'] }}</strong> is approved. The next module is unlocked.
                        @else
                            Your proof for <strong class="text-white">{{ $notice['title'] }}</strong> needs a tweak before it can be approved.
                            @if(!empty($notice['note']))
                                <span class="block mt-1 text-amber-400">{{ $notice['note'] }}</span>
                            @endif
                        @endif
                    </p>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    <button wire:click="openReviewNotice('{{ $notice['module_id'] }}')"
                            class="px-5 py-2.5 rounded-xl text-[11px] font-black uppercase tracking-widest transition-all
                                {{ $approved ? 'bg-green-500 text-black hover:bg-white' : 'bg-amber-500 text-black hover:bg-white' }}">
                        {{ $approved ? 'Keep going' : 'Fix it' }}
                    </button>
                    <button wire:click="dismissReviewNotices"
                            class="px-3 py-2.5 rounded-xl border border-zinc-800 text-zinc-500 hover:text-white hover:border-zinc-600 transition"
                            title="Dismiss">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>
        @endforeach
    </div>
@endif
