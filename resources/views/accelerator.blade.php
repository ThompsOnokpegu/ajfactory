<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Build 9 real AI automations in 6 weeks — Telegram, WhatsApp & Voice AI on your own infrastructure, even if you can't code. Finish, or we coach you 1-on-1 until you do.">

        <!-- Open Graph / Facebook -->
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ config('app.url') }}">
        <meta property="og:title" content="AI Automation Accelerator | Cohort 2">
        <meta property="og:description" content="Build 9 real AI automations in 6 weeks — and the playbook to charge for them.">
        <meta property="og:image" content="{{ asset('img/og-preview.jpg') }}">

        <!-- Twitter -->
        <meta property="twitter:card" content="summary_large_image">
        <meta property="twitter:image" content="{{ asset('img/og-preview.jpg') }}">
        <title>AI Automation Accelerator | Cohort 2</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;700;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>
            .text-glow-cyan { text-shadow: 0 0 20px rgba(6, 182, 212, 0.5); }
            .bg-grid { background-image: linear-gradient(to right, #18181b 1px, transparent 1px), linear-gradient(to bottom, #18181b 1px, transparent 1px); background-size: 50px 50px; }
            .shimmer { background: linear-gradient(90deg, transparent, rgba(6, 182, 212, 0.1), transparent); background-size: 200% 100%; animation: shimmer 3s infinite; }
            @keyframes shimmer { 0% { background-position: -200% 0; } 100% { background-position: 200% 0; } }
            .badge-pulse { animation: pulse-border 2s infinite; }
            @keyframes pulse-border { 0% { border-color: rgba(6, 182, 212, 0.2); box-shadow: 0 0 0 0 rgba(6, 182, 212, 0.2); } 50% { border-color: rgba(6, 182, 212, 0.6); box-shadow: 0 0 20px 0 rgba(6, 182, 212, 0.1); } 100% { border-color: rgba(6, 182, 212, 0.2); box-shadow: 0 0 0 0 rgba(6, 182, 212, 0.2); } }
        </style>
    </head>
    @php
        use App\Support\Accelerator;

        $seatsLeft   = Accelerator::seatsLeft();
        $cap         = (int) config('accelerator.cohort_cap');
        $soldOut     = Accelerator::isSoldOut();
        $earlybird   = Accelerator::earlybirdActive();
        $fullPrice   = Accelerator::fullPrice('NGN');
        $regular     = Accelerator::regularFullPrice('NGN');
        $instEach    = Accelerator::installmentEach('NGN');
        $instCount   = Accelerator::installmentCount();
        $startsAt    = Accelerator::cohortStartsAt();
        $startLabel  = $startsAt ? $startsAt->format('l jS F') : '{{TODO: cohort start date}}';
        $primaryCta  = $soldOut ? '/builders' : '/checkout?plan=full';
        $primaryText = $soldOut ? 'Join The Waitlist' : 'Join Cohort 2 - ₦'.number_format($fullPrice);
    @endphp
    <body class="bg-zinc-950 text-zinc-300 font-sans antialiased selection:bg-cyan-500 selection:text-black">

        <!-- Background grid & Orbs -->
        <div class="fixed inset-0 bg-grid z-0 opacity-40"></div>
        <div class="fixed top-[-10%] left-[-10%] w-[40%] h-[40%] bg-cyan-900/20 blur-[120px] rounded-full z-0 pointer-events-none"></div>
        <div class="fixed bottom-[-10%] right-[-10%] w-[40%] h-[40%] bg-purple-900/10 blur-[120px] rounded-full z-0 pointer-events-none"></div>

        <div class="relative z-10">
            <!-- STICKY HEADER -->
            <nav class="sticky top-0 w-full z-50 bg-zinc-950/80 backdrop-blur-md border-b border-zinc-900">
                <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
                    <div class="text-xl font-black tracking-tighter italic text-white uppercase">
                        AI.<span class="text-cyan-500">ACCELERATOR</span>
                    </div>
                    <div class="hidden md:flex items-center gap-8">
                        <a href="#curriculum" class="text-xs font-bold uppercase tracking-widest text-zinc-500 hover:text-white transition">Curriculum</a>
                        <a href="#pricing" class="text-xs font-bold uppercase tracking-widest text-zinc-500 hover:text-white transition">Pricing</a>
                        <a href="#guarantee" class="text-xs font-bold uppercase tracking-widest text-zinc-500 hover:text-white transition">Guarantee</a>
                        <a href="{{ $primaryCta }}" class="px-5 py-2 bg-white text-black text-[10px] font-black uppercase tracking-widest rounded hover:bg-cyan-500 transition-all">{{ $soldOut ? 'Waitlist' : 'Secure Spot' }}</a>
                    </div>
                </div>
            </nav>

            <!-- 3.1 HERO -->
            <section class="max-w-6xl mx-auto px-6 pt-20 pb-32 text-center">
                <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-cyan-950/30 border border-cyan-500/30 text-cyan-400 text-[10px] font-black uppercase tracking-[0.2em] mb-10 badge-pulse">
                    <span class="h-2 w-2 rounded-full bg-cyan-500 shadow-[0_0_10px_#06b6d4] animate-pulse"></span>
                    AI Automation Accelerator
                </div>

                <h1 class="text-5xl md:text-7xl font-black text-white leading-[0.95] tracking-tighter uppercase italic font-['Space_Grotesk'] mb-8">
                    You're one <br>
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-cyan-400 via-blue-500 to-cyan-400">workflow away...</span>
                </h1>

                <p class="text-lg md:text-2xl text-zinc-400 max-w-3xl mx-auto font-medium mb-12 leading-snug">
                    A hands-on, <span class="text-white border-b-2 border-cyan-500">beginner-friendly AI Automation</span>. Build enterprise-level workflows: Telegram, WhatsApp &amp; Voice AI on your own infrastructure - <span class="text-white border-b-2 border-cyan-500">even if you can't code.</span> Finish, or we coach you 1-on-1 until you do.
                </p>

                <div class="flex flex-col sm:flex-row gap-6 justify-center items-center">
                    <a href="{{ $primaryCta }}" class="group relative px-10 py-5 bg-cyan-500 text-black font-black uppercase tracking-tighter text-xl hover:scale-105 transition-all rounded shadow-[0_0_30px_rgba(6,182,212,0.3)]">
                        {{ $primaryText }}
                    </a>
                </div>

                <!-- Trust line -->
                <div class="mt-8 text-xs font-mono text-zinc-500 uppercase tracking-widest flex flex-wrap justify-center items-center gap-x-3 gap-y-1">
                    <span>{{ $startLabel }}</span>
                    <span class="text-zinc-700">·</span>
                    @if($soldOut)
                        <span class="text-amber-400">Cohort full - join the waitlist</span>
                    @else
                        <span class="text-cyan-400">{{ $seatsLeft }} of {{ $cap }} seats left</span>
                    @endif
                    <span class="text-zinc-700">·</span>
                    <span>Installments available</span>
                </div>

                <div class="mt-16 flex flex-wrap justify-center items-center gap-5 opacity-40 grayscale hover:grayscale-0 transition-all duration-700">
                    <span class="text-xl font-black font-['Space_Grotesk'] tracking-tighter italic">N8N</span>
                    <span class="text-xl font-black font-['Space_Grotesk'] tracking-tighter italic">TELEGRAM</span>
                    <span class="text-xl font-black font-['Space_Grotesk'] tracking-tighter italic">WHATSAPP</span>
                    <span class="text-xl font-black font-['Space_Grotesk'] tracking-tighter italic">VAPI</span>
                    <span class="text-xl font-black font-['Space_Grotesk'] tracking-tighter italic">PINECONE</span>
                </div>
            </section>

            <!-- 3.2 PROBLEM -> OUTCOME -->
            <section class="max-w-6xl mx-auto px-6 py-20 border-t border-zinc-900">
                <div class="grid md:grid-cols-2 gap-8">
                    <div class="p-10 rounded-3xl border border-zinc-800 bg-zinc-950/50">
                        <div class="text-[10px] font-black uppercase tracking-widest text-zinc-600 mb-4">The Problem</div>
                        <p class="text-lg text-zinc-400 leading-relaxed">
                            Everyone's talking about AI automation. Most courses leave you with theory, surprise tool bills, and a half-built project you abandon.
                        </p>
                    </div>
                    <div class="p-10 rounded-3xl border border-cyan-500/30 bg-cyan-950/10">
                        <div class="text-[10px] font-black uppercase tracking-widest text-cyan-500 mb-4">The Outcome</div>
                        <p class="text-lg text-white leading-relaxed">
                            You'll ship nine working automations, own your stack, and have a tested way to land paying clients.
                        </p>
                    </div>
                </div>
            </section>

            <!-- 3.3 WHAT YOU'LL BUILD -->
            <section id="curriculum" class="max-w-7xl mx-auto px-6 py-24 border-t border-zinc-900">
                <div class="text-center mb-16 space-y-3">
                    <div class="text-xs font-mono text-cyan-500 uppercase tracking-widest">// What You'll Build</div>
                    <h2 class="text-4xl md:text-5xl font-black text-white uppercase italic tracking-tighter">9 production-grade workflows,<br>built end-to-end.</h2>
                </div>

                <div class="grid lg:grid-cols-2 gap-8">
                    <!-- Part 1 -->
                    <div class="p-8 md:p-10 rounded-3xl border border-zinc-800 bg-zinc-900/40">
                        <div class="text-[10px] font-black uppercase tracking-[0.2em] text-cyan-500 mb-2">Part 1 — Zero-Friction</div>
                        <div class="text-zinc-500 text-xs mb-8">Free tools only.</div>
                        <ul class="space-y-5">
                            @foreach([
                                ['Intake Funnel', 'form → email'],
                                ['Automated Archivist', 'files invoices automatically'],
                                ['Lead Qualifier', 'smart routing'],
                                ['FX & Quotation Engine', 'live pricing'],
                                ['AI Agent on Telegram', 'captures leads in real time'],
                            ] as [$title, $desc])
                                <li class="flex items-start gap-4">
                                    <div class="mt-1 h-5 w-5 rounded-full bg-cyan-500/20 border border-cyan-500/50 flex items-center justify-center shrink-0">
                                        <svg class="w-3 h-3 text-cyan-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                    </div>
                                    <div>
                                        <div class="text-sm font-bold text-white">{{ $title }}</div>
                                        <div class="text-xs text-zinc-500">{{ $desc }}</div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <!-- Part 2 -->
                    <div class="p-8 md:p-10 rounded-3xl border border-cyan-900/40 bg-zinc-900 shimmer relative overflow-hidden">
                        <div class="relative z-10">
                            <div class="text-[10px] font-black uppercase tracking-[0.2em] text-cyan-500 mb-2">Part 2 — Professional Agency</div>
                            <div class="text-zinc-500 text-xs mb-8">Your own owned stack.</div>
                            <ul class="space-y-5">
                                @foreach([
                                    ['Your own self-hosted server', '₦0 / month'],
                                    ['RAG knowledge base', 'no hallucinations'],
                                    ['Official WhatsApp bot', 'Meta Cloud API'],
                                    ['AI voice receptionist', 'real-time, Vapi'],
                                    ['The business/pricing playbook', 'land paying clients'],
                                ] as [$title, $desc])
                                    <li class="flex items-start gap-4">
                                        <div class="mt-1 h-5 w-5 rounded-full bg-cyan-500/20 border border-cyan-500/50 flex items-center justify-center shrink-0">
                                            <svg class="w-3 h-3 text-cyan-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                        </div>
                                        <div>
                                            <div class="text-sm font-bold text-white">{{ $title }}</div>
                                            <div class="text-xs text-zinc-500">{{ $desc }}</div>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 3.4 HOW IT WORKS -->
            <section class="max-w-7xl mx-auto px-6 py-24 border-t border-zinc-900">
                <div class="text-center mb-16 space-y-3">
                    <div class="text-xs font-mono text-cyan-500 uppercase tracking-widest">// How It Works</div>
                    <h2 class="text-4xl md:text-5xl font-black text-white uppercase italic tracking-tighter">Why you'll actually finish.</h2>
                    <p class="text-zinc-500 font-mono text-xs uppercase tracking-widest">6 weeks · ~5–8 hours/week</p>
                </div>

                <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    @foreach([
                        ['Self-paced video', 'Every module lives on this LMS — learn on your schedule.'],
                        ['Ship-to-unlock', 'The next module opens when you submit proof the last build works, so you never silently fall behind.'],
                        ['Weekly live clinics', 'Build & Debug sessions — get unblocked in real time.'],
                        ['Accountability pods', '3–4 classmates working alongside you.'],
                    ] as $i => [$title, $desc])
                        <div class="p-8 bg-zinc-900/50 border border-zinc-800 rounded-3xl hover:border-cyan-500/50 transition-all">
                            <div class="text-[10px] font-mono text-cyan-500 mb-4 uppercase tracking-[0.2em]">0{{ $i + 1 }}</div>
                            <h3 class="text-lg font-bold text-white mb-3 italic uppercase">{{ $title }}</h3>
                            <p class="text-sm text-zinc-500 leading-relaxed">{{ $desc }}</p>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- 3.5 PROOF — temporarily hidden until we have real testimonials. Re-enable this whole block once config('accelerator.testimonials') is populated. --}}
            {{--
            <section class="max-w-7xl mx-auto px-6 py-24 border-t border-zinc-900">
                <div class="text-center mb-12 space-y-3">
                    <div class="text-xs font-mono text-cyan-500 uppercase tracking-widest">// Proof</div>
                    <h2 class="text-4xl md:text-5xl font-black text-white uppercase italic tracking-tighter">Builders, not bystanders.</h2>
                </div>

                @php $testimonials = Accelerator::publishedTestimonials(); @endphp
                @if($testimonials->isNotEmpty())
                    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($testimonials as $t)
                            <div class="p-8 bg-zinc-900/50 border border-zinc-800 rounded-3xl">
                                <p class="text-sm text-zinc-300 leading-relaxed mb-6">"{{ $t['quote'] ?? '' }}"</p>
                                <div class="flex items-center gap-3">
                                    @if(!empty($t['photo']))
                                        <img src="{{ $t['photo'] }}" alt="{{ $t['name'] ?? '' }}" class="h-10 w-10 rounded-full object-cover">
                                    @endif
                                    <div>
                                        <div class="text-sm font-bold text-white">{{ $t['name'] ?? '' }}</div>
                                        <div class="text-[10px] text-zinc-500 uppercase tracking-widest">{{ $t['role'] ?? '' }}</div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <!-- Graceful empty state — no fabricated testimonials -->
                    <div class="max-w-2xl mx-auto p-12 rounded-3xl border border-dashed border-zinc-800 bg-zinc-950/40 text-center">
                        <p class="text-zinc-500 text-sm leading-relaxed">
                            Cohort 2 results land here as builders ship. Want to be one of the first case studies?
                        </p>
                        <a href="{{ $primaryCta }}" class="inline-block mt-6 px-6 py-3 border border-zinc-800 text-zinc-400 hover:text-white hover:border-cyan-500/50 transition-all uppercase font-black text-[10px] tracking-widest rounded-lg">
                            {{ $soldOut ? 'Join The Waitlist' : 'Claim Your Seat' }}
                        </a>
                        TODO: owner adds real testimonials + build/result clips via config('accelerator.testimonials')
                    </div>
                @endif
            </section>
            --}}

            <!-- 3.6 OFFER STACK -->
            <section class="max-w-4xl mx-auto px-6 py-24 border-t border-zinc-900">
                <div class="text-center mb-12 space-y-3">
                    <div class="text-xs font-mono text-cyan-500 uppercase tracking-widest">// What's Included</div>
                    <h2 class="text-4xl md:text-5xl font-black text-white uppercase italic tracking-tighter">Everything in the box.</h2>
                </div>

                <div class="rounded-3xl border border-zinc-800 bg-zinc-900/40 divide-y divide-zinc-900">
                    @foreach([
                        '10 modules · 9 production workflows (full curriculum)',
                        'Ship-to-unlock structure + weekly live Build & Debug clinics',
                        'Accountability pod (3–4 peers)',
                        'Done-for-you friction kit: one-command self-host script, Money & Tools Map, sandbox/credit budgets, Meta verification help',
                        'The Agency Toolkit: intake form, outreach script, onboarding roadmap, pricing playbook',
                        'Lifetime LMS access + all future updates & session recordings',
                        'Alumni community + Demo Day',
                        'Completion guarantee (see below)',
                    ] as $item)
                        <div class="flex items-start gap-4 p-5">
                            <svg class="w-5 h-5 text-cyan-500 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/></svg>
                            <span class="text-sm text-zinc-300 leading-snug">{{ $item }}</span>
                        </div>
                    @endforeach
                </div>

                <p class="mt-8 text-center text-base text-zinc-400 leading-relaxed max-w-2xl mx-auto">
                    Agencies charge <span class="text-amber-400 font-bold">₦300k–1M to build <em>one</em></span> of these. You'll learn to build all nine — and sell them — for <span class="text-white font-bold">₦{{ number_format(config('accelerator.price_full')) }}</span>.
                </p>
            </section>

            <!-- 3.7 PRICING -->
            <section id="pricing" class="max-w-6xl mx-auto px-6 py-24 border-t border-zinc-900">
                <div class="text-center mb-16 space-y-3">
                    <h2 class="text-4xl md:text-6xl font-black text-white uppercase italic tracking-tighter">Cohort 02.</h2>
                    @if($soldOut)
                        <p class="text-amber-400 font-bold">This cohort is full. Join the waitlist for the next one.</p>
                    @else
                        <p class="text-zinc-500">{{ $seatsLeft }} of {{ $cap }} seats left.@if($earlybird) <span class="text-cyan-400">Early-bird pricing is live.</span>@endif</p>
                    @endif
                </div>

                <div class="grid md:grid-cols-2 gap-6 max-w-4xl mx-auto">
                    <!-- Pay in full -->
                    <div class="relative p-8 md:p-10 rounded-[2.5rem] border {{ $earlybird ? 'border-cyan-500/50' : 'border-zinc-800' }} bg-zinc-900/50 flex flex-col">
                        @if($earlybird)
                            <div class="absolute -top-3 left-8 bg-cyan-500 text-black text-[9px] font-black uppercase tracking-widest px-3 py-1 rounded">Early-bird active</div>
                        @endif
                        <div class="text-[10px] font-black uppercase tracking-widest text-zinc-500 mb-4">Pay in full</div>
                        <div class="flex items-end gap-3 mb-2">
                            <div class="text-5xl font-black text-white tracking-tighter italic">₦{{ number_format($fullPrice) }}</div>
                            @if($earlybird)
                                <div class="text-zinc-600 line-through text-lg mb-1">₦{{ number_format($regular) }}</div>
                            @endif
                        </div>
                        <div class="text-zinc-500 text-xs font-mono uppercase tracking-widest mb-8">
                            {{ $earlybird ? 'Early-bird price · one payment' : 'One payment' }}
                        </div>
                        <a href="{{ $soldOut ? '/builders' : '/checkout?plan=full' }}" class="mt-auto block text-center w-full py-4 bg-cyan-500 text-black font-black uppercase tracking-tighter text-lg rounded-2xl hover:bg-white transition-all">
                            {{ $soldOut ? 'Join Waitlist' : 'Choose Full' }}
                        </a>
                    </div>

                    <!-- Installment -->
                    <div class="p-8 md:p-10 rounded-[2.5rem] border border-zinc-800 bg-zinc-950/50 flex flex-col">
                        <div class="text-[10px] font-black uppercase tracking-widest text-zinc-500 mb-4">{{ $instCount }} payments</div>
                        <div class="flex items-end gap-2 mb-2">
                            <div class="text-5xl font-black text-white tracking-tighter italic">₦{{ number_format($instEach) }}</div>
                            <div class="text-zinc-500 text-xl font-bold mb-1">× {{ $instCount }}</div>
                        </div>
                        <div class="text-zinc-500 text-xs font-mono uppercase tracking-widest mb-8">
                            Pay ₦{{ number_format($instEach) }} today · total ₦{{ number_format($instEach * $instCount) }}
                        </div>
                        <a href="{{ $soldOut ? '/builders' : '/checkout?plan=installment' }}" class="mt-auto block text-center w-full py-4 border border-zinc-700 text-white font-black uppercase tracking-tighter text-lg rounded-2xl hover:border-cyan-500 hover:text-cyan-400 transition-all">
                            {{ $soldOut ? 'Join Waitlist' : 'Pay in 2' }}
                        </a>
                    </div>
                </div>

                <p class="mt-8 text-center text-[11px] font-mono text-zinc-600 uppercase tracking-widest">
                    Secure checkout · Paystack (card or bank transfer) · USD via Flutterwave available
                </p>
            </section>

            <!-- 3.8 COMPLETION GUARANTEE -->
            <section id="guarantee" class="max-w-3xl mx-auto px-6 py-24 text-center border-t border-zinc-900">
                <div class="mb-8 flex justify-center">
                    <div class="w-20 h-20 border-2 border-cyan-500/20 rounded-full flex items-center justify-center">
                        <svg class="w-10 h-10 text-cyan-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                    </div>
                </div>
                <h3 class="text-3xl font-black text-white uppercase italic mb-6">Finish, or we finish with you.</h3>
                <p class="text-zinc-400 text-base leading-relaxed max-w-xl mx-auto">
                    Do the work, attend the clinics, and if your stack still isn't live by the end of the cohort, you get free 1-on-1 sessions until it works — at no extra cost.
                </p>
            </section>

            <!-- 3.9 REQUIREMENTS & COSTS -->
            <section class="max-w-4xl mx-auto px-6 py-24 border-t border-zinc-900">
                <x-requirements-costs />
            </section>

            <!-- 3.10 FAQ -->
            <section class="max-w-3xl mx-auto px-6 py-24 border-t border-zinc-900">
                <div class="text-center mb-12 space-y-3">
                    <div class="text-xs font-mono text-cyan-500 uppercase tracking-widest">// FAQ</div>
                    <h2 class="text-4xl md:text-5xl font-black text-white uppercase italic tracking-tighter">Straight answers.</h2>
                </div>

                <div class="space-y-3" x-data="{ open: null }">
                    @foreach([
                        ['Do I need to know how to code?', "No. It's drag-and-drop automation. If you can follow step-by-step instructions, you can do this."],
                        ['What will it cost beyond the ₦79,000?', 'About one cheap domain (from ~$10/yr). Vapi gives a free $10 credit for the optional voice module, and everything else runs on free tiers.'],
                        ['Do I need a registered business (CAC)?', 'No — not to start or to finish. It\'s only needed for the optional WhatsApp module; without it you complete via the Telegram path.'],
                        ['What if I fall behind?', 'Ship-to-unlock keeps you on track, there\'s a catch-up buffer week, weekly live clinics, and the completion guarantee.'],
                        ['Do I need an international card?', 'Only for free Google Cloud verification (and the optional Vapi credits) — you\'ll need a card that works for international/USD payments.'],
                        ['How much time per week?', 'About 5–8 hours, over 6 weeks.'],
                        ['Is it live or recorded?', 'Both — self-paced videos plus weekly live Build & Debug clinics and an accountability pod.'],
                        ['Can I pay in installments?', 'Yes — ₦42,000 × 2.'],
                        ['What\'s the guarantee?', 'Do the work and if your stack still isn\'t live by the end, we coach you 1-on-1 until it is.'],
                    ] as $i => [$q, $a])
                        <div class="rounded-2xl border border-zinc-800 bg-zinc-900/40 overflow-hidden">
                            <button type="button" @click="open === {{ $i }} ? open = null : open = {{ $i }}" class="w-full flex justify-between items-center gap-4 text-left p-5">
                                <span class="text-sm font-bold text-white">{{ $q }}</span>
                                <span class="text-cyan-500 text-xl shrink-0" x-text="open === {{ $i }} ? '−' : '+'">+</span>
                            </button>
                            <div x-show="open === {{ $i }}" x-cloak class="px-5 pb-5 text-sm text-zinc-400 leading-relaxed">{{ $a }}</div>
                        </div>
                    @endforeach
                </div>
            </section>

            <!-- 3.11 FINAL CTA + SCARCITY -->
            <section class="max-w-4xl mx-auto px-6 py-24 text-center border-t border-zinc-900">
                <h2 class="text-4xl md:text-6xl font-black text-white uppercase italic tracking-tighter mb-6">
                    {{ $soldOut ? 'Cohort 2 is full.' : 'Your seat is waiting.' }}
                </h2>
                <p class="text-zinc-400 max-w-xl mx-auto mb-4 leading-relaxed">
                    Build nine real automations, own your stack, and finish — backed by the completion guarantee. Finish, or we finish with you.
                </p>
                <div class="mb-10 text-xs font-mono text-zinc-500 uppercase tracking-widest flex flex-wrap justify-center items-center gap-x-3 gap-y-1">
                    <span>{{ $startLabel }}</span>
                    <span class="text-zinc-700">·</span>
                    @if($soldOut)
                        <span class="text-amber-400">Waitlist open</span>
                    @else
                        <span class="text-cyan-400">{{ $seatsLeft }} of {{ $cap }} seats left</span>
                    @endif
                </div>
                <a href="{{ $primaryCta }}" class="inline-block px-12 py-6 bg-cyan-500 text-black font-black uppercase tracking-tighter text-2xl rounded-2xl hover:bg-white transition-all shadow-[0_20px_50px_rgba(6,182,212,0.2)]">
                    {{ $primaryText }}
                </a>
            </section>

            <!-- FOOTER -->
            <footer class="py-20 border-t border-zinc-900 bg-zinc-950 relative overflow-hidden">
                <div class="max-w-6xl mx-auto px-6 relative z-10">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-12 lg:gap-24 mb-16">
                        <!-- Brand & Address -->
                        <div class="space-y-6">
                            <div class="text-xl font-black text-white uppercase italic tracking-tighter">
                                DEEPR<span class="text-cyan-500 text-sm">WEB SERVICES</span>
                            </div>
                            <div class="text-xs font-mono text-zinc-500 leading-relaxed uppercase tracking-[0.1em]">
                                Jidu Airport Road, Abuja.<br>
                                Federal Capital Territory, Nigeria.
                            </div>
                            <div class="flex flex-col gap-2">
                                <a href="mailto:hello@ajbuildai.com" class="text-xs font-bold text-zinc-400 hover:text-cyan-500 transition">hello@ajbuildai.com</a>
                                <a href="tel:08068125034" class="text-xs font-bold text-zinc-400 hover:text-cyan-500 transition">+2348068125034</a>
                            </div>
                        </div>

                        <!-- Legal Navigation -->
                        <div class="flex flex-col md:items-end gap-8">
                            <div class="flex flex-wrap gap-8 md:justify-end">
                                <a href="{{ route('legal') }}" class="text-[10px] font-black uppercase tracking-widest text-zinc-500 hover:text-white transition">Terms</a>
                                <a href="{{ route('legal') }}" class="text-[10px] font-black uppercase tracking-widest text-zinc-500 hover:text-white transition">Privacy</a>
                                <a href="{{ route('legal') }}" class="text-[10px] font-black uppercase tracking-widest text-zinc-500 hover:text-white transition">Refunds</a>
                            </div>

                            <div class="text-right">
                                <p class="text-[9px] font-mono text-zinc-700 uppercase tracking-widest leading-relaxed">
                                    A High-Performance Training Division by <br>
                                    <span class="text-zinc-500 font-bold">Deepr Web Services</span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="pt-10 border-t border-zinc-900 flex justify-between items-center">
                        <p class="text-[10px] font-mono text-zinc-800 uppercase tracking-widest">
                            &copy; {{ date('Y') }} Deepr Web Services.
                        </p>
                        <div class="h-2 w-2 rounded-full bg-zinc-900 animate-pulse"></div>
                    </div>
                </div>
            </footer>
        </div>
    </body>
</html>
