<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="AJBuilds AI teaches you to build and sell real AI automations — Telegram, WhatsApp & voice agents on your own infrastructure. Nigeria-first, no code required.">

        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ config('app.url') }}">
        <meta property="og:title" content="AJBuilds AI — Build AI automations people pay for">
        <meta property="og:description" content="Learn to build and sell real AI automations. Start free at the bootcamp, then go all in with the Accelerator.">
        <meta property="og:image" content="{{ asset('img/og-preview.jpg') }}">
        <meta name="twitter:card" content="summary_large_image">

        <title>AJBuilds AI — Build &amp; sell AI automations</title>

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
    <body class="bg-zinc-950 text-zinc-300 font-sans antialiased selection:bg-cyan-500 selection:text-black">

        <!-- Background grid & orbs -->
        <div class="fixed inset-0 bg-grid z-0 opacity-40"></div>
        <div class="fixed top-[-10%] left-[-10%] w-[40%] h-[40%] bg-cyan-900/20 blur-[120px] rounded-full z-0 pointer-events-none"></div>
        <div class="fixed bottom-[-10%] right-[-10%] w-[40%] h-[40%] bg-purple-900/10 blur-[120px] rounded-full z-0 pointer-events-none"></div>

        <div class="relative z-10">

            <!-- NAV -->
            <nav class="sticky top-0 w-full z-50 bg-zinc-950/80 backdrop-blur-md border-b border-zinc-900">
                <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
                    <div class="text-xl font-black tracking-tighter italic text-white uppercase">
                        AJBUILDS<span class="text-cyan-500"> AI</span>
                    </div>
                    <div class="hidden md:flex items-center gap-8">
                        <a href="{{ route('taab.index') }}" class="text-xs font-bold uppercase tracking-widest text-zinc-500 hover:text-white transition">Bootcamp</a>
                        <a href="{{ route('accelerator') }}" class="text-xs font-bold uppercase tracking-widest text-zinc-500 hover:text-white transition">Accelerator</a>
                        <a href="{{ route('taab.index') }}" class="px-5 py-2 bg-white text-black text-[10px] font-black uppercase tracking-widest rounded hover:bg-cyan-500 transition-all">Start Free</a>
                    </div>
                </div>
            </nav>

            <!-- HERO -->
            <section class="max-w-5xl mx-auto px-6 pt-20 pb-28 text-center">
                <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-cyan-950/30 border border-cyan-500/30 text-cyan-400 text-[10px] font-black uppercase tracking-[0.2em] mb-10 badge-pulse">
                    <span class="h-2 w-2 rounded-full bg-cyan-500 shadow-[0_0_10px_#06b6d4] animate-pulse"></span>
                    AJBuilds AI · ajbuildai.com
                </div>

                <h1 class="text-5xl md:text-7xl font-black text-white leading-[0.9] tracking-tighter uppercase italic font-['Space_Grotesk'] mb-8">
                    Build AI automations<br>
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-cyan-400 via-blue-500 to-cyan-400">people pay for.</span>
                </h1>

                <p class="text-lg md:text-2xl text-zinc-400 max-w-3xl mx-auto font-medium mb-12 leading-snug">
                    Learn to build and sell real AI automations — Telegram, WhatsApp &amp; voice agents on your own infrastructure. Hands-on, beginner-friendly, <span class="text-white border-b-2 border-cyan-500">no code required.</span>
                </p>

                <div class="flex flex-col sm:flex-row gap-6 justify-center items-center">
                    <a href="{{ route('taab.index') }}" class="group relative px-10 py-5 bg-cyan-500 text-black font-black uppercase tracking-tighter text-xl hover:scale-105 transition-all rounded shadow-[0_0_30px_rgba(6,182,212,0.3)]">
                        Start Free
                    </a>
                    <a href="{{ route('accelerator') }}" class="px-10 py-5 border border-zinc-800 text-white font-black uppercase tracking-tighter text-xl rounded hover:border-cyan-500/50 hover:text-cyan-400 transition-all">
                        See the Accelerator
                    </a>
                </div>

                <p class="mt-10 text-[10px] font-black text-zinc-600 uppercase tracking-[0.2em]">
                    Free one-day bootcamp · 6-week paid cohort · Build &amp; sell, no surprises
                </p>
            </section>

            <!-- FREE TOOLS -->
            <section class="max-w-7xl mx-auto px-6 py-24 border-t border-zinc-900">
                <div class="text-center mb-16 space-y-3">
                    <div class="text-xs font-mono text-cyan-500 uppercase tracking-widest">// Free Tools</div>
                    <h2 class="text-4xl md:text-5xl font-black text-white uppercase italic tracking-tighter">Get clarity in 5 minutes.</h2>
                </div>

                <div class="grid sm:grid-cols-3 gap-4">
                    @foreach([
                        ['Readiness Scorecard', 'Are you ready to go all in? Score yourself across 5 dimensions.', 'taab.scorecard'],
                        ['ROI Calculator', 'What it costs and when it pays — with your real numbers.', 'taab.roi'],
                        ['Tool Stack Guide', 'The right tools for your level, with honest monthly costs.', 'taab.tools'],
                    ] as [$title, $desc, $route])
                        <a href="{{ route($route) }}" class="group p-8 bg-zinc-900/50 border border-zinc-800 rounded-3xl hover:border-cyan-500/50 transition-all flex flex-col">
                            <h3 class="text-xl font-bold text-white mb-3 italic uppercase group-hover:text-cyan-400 transition">{{ $title }}</h3>
                            <p class="text-sm text-zinc-500 leading-relaxed flex-1">{{ $desc }}</p>
                            <span class="mt-6 text-[10px] font-black text-cyan-500 uppercase tracking-widest">Open tool →</span>
                        </a>
                    @endforeach
                </div>
            </section>

            <!-- TWO PATHS -->
            <section class="max-w-5xl mx-auto px-6 py-24 border-t border-zinc-900">
                <div class="text-center mb-16 space-y-3">
                    <div class="text-xs font-mono text-cyan-500 uppercase tracking-widest">// Two Ways In</div>
                    <h2 class="text-4xl md:text-5xl font-black text-white uppercase italic tracking-tighter">Start where you are.</h2>
                </div>

                <div class="grid md:grid-cols-2 gap-6">
                    <!-- Bootcamp -->
                    <div class="p-8 md:p-10 rounded-[2.5rem] border border-zinc-800 bg-zinc-900/50 flex flex-col">
                        <div class="text-[10px] font-black uppercase tracking-widest text-zinc-500 mb-4">The Bootcamp · TAAB</div>
                        <div class="flex items-end gap-2 mb-2">
                            <div class="text-5xl font-black text-white tracking-tighter italic">Free</div>
                            <div class="text-zinc-500 text-sm mb-2">· one day, live</div>
                        </div>
                        <p class="text-zinc-500 leading-relaxed my-6 flex-1">
                            Clarity before you commit. Five live sessions on what AI automation really is, what it costs, and whether you're ready — plus three free tools you keep.
                        </p>
                        <a href="{{ route('taab.index') }}" class="block text-center w-full py-4 bg-cyan-500 text-black font-black uppercase tracking-tighter text-lg rounded-2xl hover:bg-white transition-all">
                            Reserve Your Seat
                        </a>
                    </div>

                    <!-- Accelerator -->
                    <div class="relative p-8 md:p-10 rounded-[2.5rem] border border-cyan-500/40 bg-zinc-900 shimmer flex flex-col overflow-hidden">
                        <div class="absolute -top-3 left-8 bg-cyan-500 text-black text-[9px] font-black uppercase tracking-widest px-3 py-1 rounded">Go All In</div>
                        <div class="relative z-10 flex flex-col flex-1">
                            <div class="text-[10px] font-black uppercase tracking-widest text-zinc-500 mb-4">The Accelerator · {{ \App\Support\Accelerator::cohortLabel() }}</div>
                            <div class="flex items-end gap-2 mb-2">
                                <div class="text-5xl font-black text-white tracking-tighter italic">₦{{ number_format((int) config('accelerator.price_full')) }}</div>
                                <div class="text-amber-400 text-sm font-bold mb-2">· or ₦{{ number_format((int) config('accelerator.installment_each')) }} × {{ (int) config('accelerator.installment_count') }}</div>
                            </div>
                            <p class="text-zinc-400 leading-relaxed my-6 flex-1">
                                Build 9 real automations in 6 weeks — and the playbook to charge for them. Ship-to-unlock structure, weekly live clinics, and a completion guarantee.
                            </p>
                            <a href="{{ route('accelerator') }}" class="block text-center w-full py-4 border border-zinc-700 text-white font-black uppercase tracking-tighter text-lg rounded-2xl hover:border-cyan-500 hover:text-cyan-400 transition-all">
                                Explore the Accelerator
                            </a>
                        </div>
                    </div>
                </div>
            </section>

            <!-- FOUNDER -->
            <section class="max-w-3xl mx-auto px-6 py-24 border-t border-zinc-900">
                <div class="flex flex-col sm:flex-row gap-8 items-start">
                    <div class="h-20 w-20 shrink-0 rounded-full bg-cyan-500/10 border-2 border-cyan-500/30 flex items-center justify-center text-2xl font-black text-cyan-400 italic">AJ</div>
                    <div>
                        <div class="text-xs font-mono text-cyan-500 uppercase tracking-widest mb-2">// Who's Teaching</div>
                        <h3 class="text-2xl font-black text-white uppercase italic tracking-tighter mb-4">AJ Thompson</h3>
                        <p class="text-zinc-400 leading-relaxed">
                            AI Automation Engineer with 7+ years building production systems for real clients — WhatsApp lead qualifiers, booking workflows, and AI sales agents. AJBuilds AI is where he teaches the exact stack, plainly and without hype, so you can build it and sell it yourself.
                        </p>
                    </div>
                </div>
            </section>

            <!-- FINAL CTA -->
            <section class="max-w-3xl mx-auto px-6 py-28 text-center border-t border-zinc-900">
                <h2 class="text-4xl md:text-6xl font-black text-white uppercase italic tracking-tighter mb-6">Your first automation is closer than you think.</h2>
                <p class="text-zinc-400 text-lg max-w-xl mx-auto mb-10">Start free at the bootcamp. Go all in when you're ready.</p>
                <div class="flex flex-col sm:flex-row gap-6 justify-center">
                    <a href="{{ route('taab.index') }}" class="px-10 py-5 bg-cyan-500 text-black font-black uppercase tracking-tighter text-xl rounded-2xl hover:bg-white transition-all shadow-[0_20px_50px_rgba(6,182,212,0.2)]">Start Free</a>
                    <a href="{{ route('accelerator') }}" class="px-10 py-5 border border-zinc-800 text-white font-black uppercase tracking-tighter text-xl rounded-2xl hover:border-cyan-500/50 hover:text-cyan-400 transition-all">See the Accelerator</a>
                </div>
            </section>

            <!-- FOOTER -->
            <footer class="py-20 border-t border-zinc-900 bg-zinc-950">
                <div class="max-w-6xl mx-auto px-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-12 mb-16">
                        <div class="space-y-6">
                            <div class="text-xl font-black text-white uppercase italic tracking-tighter">
                                AJBUILDS<span class="text-cyan-500"> AI</span>
                            </div>
                            <div class="text-xs font-mono text-zinc-500 leading-relaxed uppercase tracking-[0.1em]">
                                ajbuildai.com<br>
                                Abuja, Federal Capital Territory, Nigeria.
                            </div>
                            <a href="mailto:hello@ajbuildai.com" class="block text-xs font-bold text-zinc-400 hover:text-cyan-500 transition">hello@ajbuildai.com</a>
                        </div>

                        <div class="flex flex-col md:items-end gap-8">
                            <div class="flex flex-wrap gap-8 md:justify-end">
                                <a href="{{ route('taab.index') }}" class="text-[10px] font-black uppercase tracking-widest text-zinc-500 hover:text-white transition">Bootcamp</a>
                                <a href="{{ route('accelerator') }}" class="text-[10px] font-black uppercase tracking-widest text-zinc-500 hover:text-white transition">Accelerator</a>
                                <a href="{{ route('legal') }}" class="text-[10px] font-black uppercase tracking-widest text-zinc-500 hover:text-white transition">Terms</a>
                                <a href="{{ route('legal') }}" class="text-[10px] font-black uppercase tracking-widest text-zinc-500 hover:text-white transition">Privacy</a>
                            </div>
                            <div class="text-right">
                                <p class="text-[9px] font-mono text-zinc-700 uppercase tracking-widest leading-relaxed">
                                    A training division by <br>
                                    <span class="text-zinc-500 font-bold">Deepr Web Services</span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="pt-10 border-t border-zinc-900 flex justify-between items-center">
                        <p class="text-[10px] font-mono text-zinc-800 uppercase tracking-widest">&copy; {{ date('Y') }} AJBuilds AI · Deepr Web Services.</p>
                        <div class="h-2 w-2 rounded-full bg-zinc-900 animate-pulse"></div>
                    </div>
                </div>
            </footer>

        </div>
    </body>
</html>
