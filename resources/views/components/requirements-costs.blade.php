{{--
    Requirements & Costs — the "no-surprises" promise (CLAUDE.md §5).
    Reusable: rendered inline on the landing page AND inside the checkout
    acknowledgement modal. Keep this copy intact.

    Usage: <x-requirements-costs />  (optionally pass :compact="true")
--}}
@props(['compact' => false])

<div {{ $attributes->merge(['class' => 'text-left']) }}>
    <div class="mb-8">
        <div class="text-[10px] font-mono text-cyan-500 uppercase tracking-[0.25em] mb-3">// No Surprises</div>
        <h3 class="text-2xl md:text-3xl font-black text-white uppercase italic tracking-tighter">Before you enroll — Requirements &amp; Costs</h3>
        <p class="text-zinc-400 mt-4 leading-relaxed text-sm">
            Beyond <strong class="text-white">₦{{ number_format(config('accelerator.price_full')) }} tuition</strong>, the tools are almost entirely free.
            Here is every cost you may incur — there are no others.
        </p>
    </div>

    {{-- Cost table --}}
    <div class="overflow-hidden rounded-2xl border border-zinc-800 bg-zinc-900/40">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-zinc-800 bg-zinc-900/60 text-[10px] font-black uppercase tracking-widest text-zinc-500">
                    <th class="text-left py-3 px-4">Tool</th>
                    <th class="text-left py-3 px-4 hidden sm:table-cell">For</th>
                    <th class="text-right py-3 px-4">Cost</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-900">
                @php
                    $rows = [
                        ['n8n cloud trial', 'first builds (14-day free trial, no card)', '₦0', false],
                        ['Tally', 'the lead form (Module 1)', '₦0 free plan', false],
                        ['Google (Gmail/Sheets/Drive/Docs)', 'throughout', '₦0', false],
                        ['Telegram + a free bot', 'first automation + AI agent', '₦0', false],
                        ['Loom / any screen recorder', 'weekly proof clips', '₦0', false],
                        ['LLM API key (Gemini free path)', 'AI agent brain', '₦0', false],
                        ['Domain name (Namecheap)', 'your self-hosted server', '~₦8,000–15,000 / year', true],
                        ['Google Cloud Always-Free server', 'hosting', '₦0 / month', false],
                        ['Pinecone', 'AI knowledge base', '₦0 free tier', false],
                        ['Meta WhatsApp Cloud API', 'official WhatsApp bot (optional)', '₦0 service tier', false],
                        ['Vapi voice credits', 'AI phone receptionist (optional)', '~$5–10 one time', true],
                    ];
                @endphp
                @foreach($rows as [$tool, $for, $cost, $highlight])
                    <tr class="{{ $highlight ? 'bg-amber-500/5' : '' }}">
                        <td class="py-3 px-4 font-bold {{ $highlight ? 'text-white' : 'text-zinc-300' }}">{{ $tool }}</td>
                        <td class="py-3 px-4 text-zinc-500 hidden sm:table-cell">{{ $for }}</td>
                        <td class="py-3 px-4 text-right font-mono text-xs {{ $highlight ? 'text-amber-400 font-bold' : 'text-zinc-400' }}">{{ $cost }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Bottom line --}}
    <div class="mt-6 p-5 rounded-2xl border border-amber-500/20 bg-amber-500/5">
        <p class="text-sm text-zinc-300 leading-relaxed">
            <span class="font-black text-amber-400 uppercase tracking-wide text-xs">Bottom line:</span>
            beyond tuition, plan for about <strong class="text-white">one cheap domain (~₦8–15k) + ~$5–10 of voice credits.</strong>
            No servers to rent, no monthly bills, no surprise charges.
        </p>
    </div>

    @unless($compact)
        <div class="grid md:grid-cols-2 gap-6 mt-8">
            <div class="p-6 rounded-2xl border border-zinc-800 bg-zinc-900/40">
                <h4 class="text-[10px] font-black uppercase tracking-widest text-cyan-500 mb-4">You'll need</h4>
                <ul class="space-y-3 text-sm text-zinc-400 leading-snug">
                    <li class="flex gap-2"><span class="text-cyan-500 mt-0.5 shrink-0">→</span><span>A computer + stable internet.</span></li>
                    <li class="flex gap-2"><span class="text-cyan-500 mt-0.5 shrink-0">→</span><span>A smartphone.</span></li>
                    <li class="flex gap-2"><span class="text-cyan-500 mt-0.5 shrink-0">→</span><span>A Google account.</span></li>
                    <li class="flex gap-2"><span class="text-cyan-500 mt-0.5 shrink-0">→</span><span>A card that works for international/USD payments (or a virtual USD card) for free Google Cloud verification and the optional voice credits — <em class="text-zinc-300">if you can't get one, we have a shared option so it never blocks you</em>.</span></li>
                    <li class="flex gap-2"><span class="text-cyan-500 mt-0.5 shrink-0">→</span><span>~5–8 hours/week for 6 weeks.</span></li>
                </ul>
            </div>
            <div class="p-6 rounded-2xl border border-zinc-800 bg-zinc-950/60">
                <h4 class="text-[10px] font-black uppercase tracking-widest text-zinc-500 mb-4">You do NOT need</h4>
                <ul class="space-y-3 text-sm text-zinc-500 leading-snug">
                    <li class="flex gap-2"><span class="text-zinc-700 mt-0.5 shrink-0">×</span><span>Coding experience.</span></li>
                    <li class="flex gap-2"><span class="text-zinc-700 mt-0.5 shrink-0">×</span><span>A registered business to start or finish (only the <em>optional</em> WhatsApp module needs one — without it you complete via the Telegram path).</span></li>
                    <li class="flex gap-2"><span class="text-zinc-700 mt-0.5 shrink-0">×</span><span>Expensive software.</span></li>
                    <li class="flex gap-2"><span class="text-zinc-700 mt-0.5 shrink-0">×</span><span>Prior AI knowledge.</span></li>
                </ul>
            </div>
        </div>

        <p class="mt-6 text-xs text-zinc-500 leading-relaxed">
            <strong class="text-zinc-400">Why the n8n trial won't trap you:</strong>
            it's 14 days, but we move you onto your own free self-hosted server early — before it expires.
        </p>
    @endunless
</div>
