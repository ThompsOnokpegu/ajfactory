{{--
    Snippets for the active module - AI prompts, code, JSON shared by AJ.
    Read-only for students. Expects $snippets (Collection<Snippet>).
    Managed at /admin/snippets; global snippets (module_id null) show everywhere.
--}}
@if($snippets->isNotEmpty())
    <div class="border border-zinc-900 rounded-2xl p-6 lg:p-8 bg-zinc-950/50">
        <div class="flex items-center gap-2 mb-1">
            <span class="h-1.5 w-1.5 rounded-full bg-cyan-500"></span>
            <span class="text-[10px] font-black uppercase tracking-[0.2em] text-cyan-500">Snippets</span>
        </div>
        <p class="text-xs text-zinc-500 mb-5 leading-relaxed">Prompts and code for this module - copy them straight into your build.</p>

        <div class="space-y-4">
            @foreach($snippets as $snippet)
                <div wire:key="snippet-{{ $snippet->id }}"
                     x-data="{ open: false, copied: false, copy() { navigator.clipboard.writeText(this.$refs.body.textContent).then(() => { this.copied = true; setTimeout(() => this.copied = false, 1600) }) } }"
                     class="rounded-xl border border-zinc-800 bg-zinc-900/40 overflow-hidden">

                    <div class="flex items-center gap-3 px-4 py-3">
                        <button type="button" @click="open = !open" class="flex items-center gap-3 min-w-0 flex-1 text-left">
                            <svg class="w-3.5 h-3.5 shrink-0 text-zinc-600 transition-transform" :class="open ? 'rotate-90' : ''"
                                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                            </svg>
                            <span class="text-xs font-bold text-zinc-200 truncate">{{ $snippet->title }}</span>
                            <span class="shrink-0 text-[9px] font-black uppercase tracking-widest px-2 py-0.5 rounded bg-zinc-800 text-zinc-400">
                                {{ $snippet->languageLabel() }}
                            </span>
                            @unless($snippet->module_id)
                                <span class="shrink-0 hidden sm:inline text-[9px] font-mono uppercase tracking-widest text-zinc-600">all modules</span>
                            @endunless
                        </button>

                        <button type="button" @click="copy()"
                                class="shrink-0 px-3 py-1.5 rounded-lg border text-[9px] font-black uppercase tracking-widest transition"
                                :class="copied ? 'border-green-500 text-green-500' : 'border-zinc-700 text-zinc-400 hover:border-cyan-500 hover:text-cyan-400'">
                            <span x-show="!copied">Copy</span>
                            <span x-show="copied" x-cloak>Copied &check;</span>
                        </button>
                    </div>

                    {{-- The body stays in the DOM while collapsed (hidden, not removed)
                         so Copy works without expanding first. --}}
                    <div x-show="open" x-cloak class="border-t border-zinc-800">
                        <pre class="p-4 overflow-x-auto text-[11px] leading-relaxed font-mono text-zinc-300 whitespace-pre-wrap break-words"><code x-ref="body">{{ $snippet->body }}</code></pre>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif
