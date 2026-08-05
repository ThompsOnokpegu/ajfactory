{{--
    Staged review "soft ask" — see config/reviews.php.
    Shown when a stage is due (its module checkpoint is approved). Purely
    additive: it never gates the curriculum and "Not now" always works.
    Expects: $reviewPrompt, $reviewAnswers, $reviewRating, $reviewConsent,
             $reviewCreditAs, $reviewThanks.
--}}

@if($reviewThanks)
    <div class="border border-green-500/30 bg-green-500/5 rounded-2xl p-6 lg:p-8">
        <div class="flex items-center gap-2 mb-3">
            <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span>
            <span class="text-[10px] font-black uppercase tracking-[0.2em] text-green-500">Thank you</span>
        </div>
        <h3 class="text-lg font-black text-white uppercase italic tracking-tighter">Got it — that genuinely helps</h3>
        <p class="text-xs text-zinc-400 mt-2 leading-relaxed">
            AJ reads every one of these. Now get back to building.
        </p>
    </div>

@elseif($reviewPrompt)
    @php
        $unhappyAt = (int) config('reviews.unhappy_at_or_below', 3);
        $hasRating = $reviewRating > 0;
        $isUnhappy = $hasRating && $reviewRating <= $unhappyAt;
    @endphp

    <div class="border border-cyan-900/40 bg-zinc-900/40 rounded-2xl p-6 lg:p-8">
        <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
            <div class="flex items-center gap-2">
                <span class="h-1.5 w-1.5 rounded-full bg-cyan-500 animate-pulse"></span>
                <span class="text-[10px] font-black uppercase tracking-[0.2em] text-cyan-500">{{ $reviewPrompt['eyebrow'] }}</span>
            </div>
            <button type="button" wire:click="dismissReview" wire:loading.attr="disabled"
                    class="text-[10px] font-bold uppercase tracking-widest text-zinc-600 hover:text-zinc-400 transition">
                Not now
            </button>
        </div>

        <h3 class="text-lg font-black text-white uppercase italic tracking-tighter">{{ $reviewPrompt['headline'] }}</h3>
        <p class="text-xs text-zinc-400 mt-2 leading-relaxed max-w-2xl">{{ $reviewPrompt['intro'] }}</p>

        <form wire:submit.prevent="submitReview" class="mt-6 space-y-6">

            {{-- RATING — one tap, and it decides what we ask next --}}
            <div>
                <p class="text-[11px] font-bold text-zinc-300 mb-2">How's it going so far?</p>
                <div class="flex flex-wrap items-center gap-2">
                    @foreach([1, 2, 3, 4, 5] as $score)
                        <button type="button" wire:click="$set('reviewRating', {{ $score }})"
                                class="h-10 w-10 rounded-lg border text-sm font-black transition
                                {{ $reviewRating === $score
                                    ? 'border-cyan-500 bg-cyan-500/15 text-cyan-400'
                                    : 'border-zinc-800 bg-zinc-950 text-zinc-500 hover:border-zinc-700 hover:text-zinc-300' }}">
                            {{ $score }}
                        </button>
                    @endforeach
                    <span class="ml-1 text-[10px] font-mono text-zinc-600">1 = struggling · 5 = loving it</span>
                </div>
                @error('reviewRating') <p class="text-[10px] text-amber-400 uppercase font-bold tracking-widest mt-2">{{ $message }}</p> @enderror
            </div>

            {{-- A low score is a save opportunity, not marketing copy: we ask what
                 to fix and never show the "quote me publicly" block. --}}
            @if($isUnhappy)
                <div class="rounded-xl border border-amber-500/30 bg-amber-500/5 p-4">
                    <label for="review-improve" class="block text-[11px] font-bold text-amber-300 mb-2">
                        Sorry — that's not where we want you. What's the one thing we should fix?
                    </label>
                    <textarea id="review-improve" rows="3" wire:model="reviewAnswers.improve"
                              placeholder="Be blunt. This goes straight to AJ and he'll come back to you personally."
                              class="w-full bg-zinc-950 border border-zinc-800 text-white p-3 rounded-xl focus:border-amber-500 focus:ring-0 transition-all text-xs placeholder:text-zinc-700 leading-relaxed"></textarea>
                    @error('reviewAnswers.improve') <p class="text-[10px] text-amber-400 uppercase font-bold tracking-widest mt-1">{{ $message }}</p> @enderror
                </div>
            @endif

            {{-- THE GUIDED QUESTIONS --}}
            @foreach($reviewPrompt['questions'] as $q)
                <div>
                    <label for="review-{{ $q['key'] }}" class="block text-[11px] font-bold text-zinc-300 mb-2">
                        {{ $q['label'] }}
                        @unless($q['required'] ?? false)
                            <span class="ml-1 font-mono font-normal text-zinc-600 uppercase tracking-widest text-[9px]">Optional</span>
                        @endunless
                    </label>
                    <textarea id="review-{{ $q['key'] }}" rows="{{ $q['rows'] ?? 3 }}"
                              wire:model="reviewAnswers.{{ $q['key'] }}"
                              placeholder="{{ $q['placeholder'] ?? '' }}"
                              class="w-full bg-zinc-950 border border-zinc-800 text-white p-3 rounded-xl focus:border-cyan-500 focus:ring-0 transition-all text-xs placeholder:text-zinc-700 leading-relaxed"></textarea>
                    @error('reviewAnswers.'.$q['key']) <p class="text-[10px] text-red-500 uppercase font-bold tracking-widest mt-1">{{ $message }}</p> @enderror
                </div>
            @endforeach

            {{-- CONSENT — only offered to happy students, and only after they've scored --}}
            @if($hasRating && ! $isUnhappy)
                <div class="rounded-xl border border-zinc-800 bg-zinc-950/60 p-4 space-y-3">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" wire:model.live="reviewConsent"
                               class="mt-0.5 h-4 w-4 shrink-0 rounded border-zinc-700 bg-zinc-900 text-cyan-500 focus:ring-0 focus:ring-offset-0">
                        <span class="text-[11px] text-zinc-300 leading-relaxed">
                            AJ can share my answers publicly — website, socials, ads.
                            <span class="block text-zinc-600 mt-0.5">Leave this unticked and your answers stay internal. Either way is fine.</span>
                        </span>
                    </label>

                    @if($reviewConsent)
                        <div class="pl-7">
                            <p class="text-[10px] font-black uppercase tracking-widest text-zinc-500 mb-2">Credit me as</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach(config('reviews.credit_options', []) as $value => $label)
                                    <button type="button" wire:click="$set('reviewCreditAs', '{{ $value }}')"
                                            class="px-3 py-2 rounded-lg border text-[10px] font-bold uppercase tracking-widest transition
                                            {{ $reviewCreditAs === $value
                                                ? 'border-cyan-500 bg-cyan-500/15 text-cyan-400'
                                                : 'border-zinc-800 bg-zinc-950 text-zinc-500 hover:border-zinc-700 hover:text-zinc-300' }}">
                                        {{ $label }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            <div class="flex flex-wrap items-center gap-4">
                <button type="submit" wire:loading.attr="disabled"
                        class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-white text-black text-[11px] font-black uppercase tracking-widest hover:bg-cyan-500 transition-all disabled:opacity-50">
                    <span wire:loading.remove wire:target="submitReview">Send it</span>
                    <span wire:loading wire:target="submitReview">Sending...</span>
                </button>
                <button type="button" wire:click="dismissReview"
                        class="text-[10px] font-bold uppercase tracking-widest text-zinc-600 hover:text-zinc-400 transition">
                    Ask me later
                </button>
            </div>
        </form>
    </div>
@endif
