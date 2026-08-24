<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Links | AJ Thompson</title>
    <meta name="description" content="Everything AJ Thompson builds and teaches: the free TAAB masterclass, the AI Automation Accelerator, 1-on-1 coaching, and the readiness scorecard.">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@700;900&family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        .bg-grid { background-image: linear-gradient(to right, #18181b 1px, transparent 1px), linear-gradient(to bottom, #18181b 1px, transparent 1px); background-size: 40px 40px; }
        .btn-hover-effect:hover { box-shadow: 0 0 20px rgba(6, 182, 212, 0.15); border-color: rgba(6, 182, 212, 0.5); }
    </style>
</head>
@php
    use App\Support\Accelerator;
    use App\Support\Masterclass;

    /*
     | Dates come from config, never typed here: config/taab.php drives the masterclass
     | and config/accelerator.php drives the cohort. Rolling either one updates this page
     | on its own. Every branch below has an honest fallback, so an unset date shows
     | "date to be announced" rather than a blank or a stale one.
     */

    // --- TAAB masterclass -------------------------------------------------------
    $taabStartsAt = Masterclass::startsAt();
    $taabOpen     = Masterclass::registrationOpen();
    $taabMeta     = match (true) {
        ! $taabStartsAt        => 'Next date to be announced',
        $taabOpen              => $taabStartsAt->format('D j M') . ' · ' . $taabStartsAt->format('g:i A') . ' WAT',
        default                => 'Registration closed - join the waitlist',
    };

    // --- Accelerator cohort -----------------------------------------------------
    $cohortLabel   = Accelerator::cohortLabel();
    $cohortStarts  = Accelerator::cohortStartsAt();
    $cartCloses    = Accelerator::cartClosesAt();
    $acceleratorMeta = match (true) {
        Accelerator::isSoldOut()      => $cohortLabel . ' is full - join the waitlist',
        ! $cohortStarts               => $cohortLabel . ' - start date to be announced',
        // Mid-cohort it's self-paced, so the deadline that matters is the cart close.
        Accelerator::hasStarted()     => $cohortLabel . ' running' . ($cartCloses ? ' · doors close ' . $cartCloses->format('D j M') : ''),
        default                       => $cohortLabel . ' starts ' . $cohortStarts->format('D j M'),
    };
    $acceleratorPrice = '₦' . number_format(Accelerator::fullPrice('NGN'));
@endphp
<body class="bg-zinc-950 text-white font-sans antialiased flex flex-col min-h-screen">

    <!-- Background -->
    <div class="fixed inset-0 bg-grid z-0 opacity-20 pointer-events-none"></div>
    <div class="fixed top-0 left-1/2 -translate-x-1/2 w-[600px] h-[600px] bg-cyan-900/10 blur-[100px] rounded-full z-0 pointer-events-none"></div>

    <div class="relative z-10 w-full max-w-md mx-auto px-6 py-16 flex-1 flex flex-col items-center">

        <!-- Profile / Brand -->
        <div class="mb-10 text-center space-y-3">
            <div class="w-24 h-24 mx-auto rounded-full bg-zinc-900 border border-zinc-800 flex items-center justify-center shadow-2xl relative">
                <img src="{{ asset('img/headshot.jpg') }}" alt="AJ Thompson" class="w-full h-full object-cover rounded-full">
                <div class="absolute bottom-0 right-0 w-6 h-6 bg-cyan-500 border-4 border-zinc-950 rounded-full"></div>
            </div>
            <div>
                <h1 class="text-2xl font-black uppercase italic tracking-tighter">AJ Thompson</h1>
                <p class="text-xs font-mono text-zinc-500 uppercase tracking-widest mt-1">AI Automation Architect</p>
            </div>
        </div>

        <!-- The Stack -->
        <div class="w-full space-y-4">

            <!-- 1. TAAB Masterclass (featured, free) -->
            <a href="/taab" class="group relative block w-full bg-zinc-900/80 border border-cyan-500/30 p-5 rounded-2xl transition-all duration-300 btn-hover-effect hover:-translate-y-1 hover:bg-zinc-900/90">
                <span class="absolute top-5 right-5 text-[9px] font-mono uppercase tracking-widest text-cyan-400">Free</span>
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0 mt-0.5"><span class="text-lg">🧠</span></div>
                    <div class="flex flex-col text-left">
                        <span class="font-bold text-white text-lg tracking-wide group-hover:text-cyan-400 transition-colors">TAAB Masterclass</span>
                        <p class="text-sm text-zinc-400 mt-1 leading-relaxed max-w-[92%]">
                            A free two-hour live session on what AI automation really costs, before you commit.
                        </p>
                        <span class="mt-2.5 text-[10px] font-mono uppercase tracking-widest {{ $taabOpen && $taabStartsAt ? 'text-cyan-400' : 'text-zinc-500' }}">
                            {{ $taabMeta }}
                        </span>
                    </div>
                </div>
                <span class="absolute bottom-5 right-5 h-2 w-2 rounded-full bg-cyan-500 animate-pulse shadow-[0_0_12px_rgba(6,182,212,0.6)]"></span>
            </a>

            <!-- 2. AI Automation Accelerator -->
            <a href="/accelerator" class="group relative block w-full bg-zinc-900/80 border border-zinc-800 p-5 rounded-2xl transition-all duration-300 btn-hover-effect hover:-translate-y-1">
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0 mt-0.5"><span class="text-lg">⚡️</span></div>
                    <div class="flex flex-col text-left">
                        <span class="font-bold text-white text-lg tracking-wide group-hover:text-cyan-400 transition-colors">AI Automation Accelerator</span>
                        <p class="text-sm text-zinc-400 mt-1 leading-relaxed max-w-[92%]">
                            Six weeks, nine production workflows, and the playbook to charge real money for building them.
                        </p>
                        <span class="mt-2.5 text-[10px] font-mono uppercase tracking-widest text-zinc-500">
                            {{ $acceleratorMeta }} · from {{ $acceleratorPrice }}
                        </span>
                    </div>
                </div>
            </a>

            <!-- 3. 1-on-1 Coaching — deliberately NOT a link: enquiries come by DM. -->
            <div class="relative block w-full bg-zinc-900/60 border border-amber-500/30 p-5 rounded-2xl">
                <span class="absolute top-5 right-5 text-[9px] font-mono uppercase tracking-widest text-amber-400">By DM</span>
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0 mt-0.5"><span class="text-lg">🎧</span></div>
                    <div class="flex flex-col text-left">
                        <span class="font-bold text-white text-lg tracking-wide">1-on-1 Coaching</span>
                        <p class="text-sm text-zinc-400 mt-1 leading-relaxed max-w-[92%]">
                            One-to-one help with your build, from stuck to shipped, arranged directly with me by DM.
                        </p>
                        <span class="mt-2.5 text-[10px] font-mono uppercase tracking-widest text-amber-400">
                            $300 · ₦400,000
                        </span>
                        <span class="mt-1 text-[10px] font-mono uppercase tracking-widest text-zinc-500">
                            No booking link - send me a DM below
                        </span>
                    </div>
                </div>
            </div>

            <!-- 4. Free Readiness Scorecard -->
            <a href="/taab/scorecard" class="group relative block w-full bg-zinc-900/80 border border-zinc-800 p-5 rounded-2xl transition-all duration-300 btn-hover-effect hover:-translate-y-1">
                <span class="absolute top-5 right-5 text-[9px] font-mono uppercase tracking-widest text-zinc-500">Free</span>
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0 mt-0.5"><span class="text-lg">🎯</span></div>
                    <div class="flex flex-col text-left">
                        <span class="font-bold text-white text-lg tracking-wide group-hover:text-cyan-400 transition-colors">Readiness Scorecard</span>
                        <p class="text-sm text-zinc-400 mt-1 leading-relaxed max-w-[92%]">
                            A short honest check on whether you are ready to start automating, before spending anything.
                        </p>
                    </div>
                </div>
            </a>

            <!-- 5. Agency — book a call -->
            <a href="https://repetigo.co/book" target="_blank" rel="noopener" class="group relative block w-full bg-zinc-900/80 border border-zinc-800 p-5 rounded-2xl transition-all duration-300 btn-hover-effect hover:-translate-y-1">
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0 mt-0.5"><span class="text-lg">💼</span></div>
                    <div class="flex flex-col text-left">
                        <span class="font-bold text-white text-lg tracking-wide group-hover:text-cyan-400 transition-colors">Book a Discovery Call</span>
                        <p class="text-sm text-zinc-400 mt-1 leading-relaxed max-w-[92%]">
                            A no-pitch call to scope the automation your business actually needs and what it costs.
                        </p>
                    </div>
                </div>
            </a>

        </div>

        <!-- Socials -->
        <div class="mt-12 flex items-center gap-6 opacity-60">
            <a href="https://tiktok.com/@ajthompson.ai" target="_blank" rel="noopener" class="text-zinc-400 hover:text-white transition">
                <span class="sr-only">TikTok</span>
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64 2.93 2.93 0 0 1 .88.13V9.4a6.84 6.84 0 0 0-1-.05A6.33 6.33 0 0 0 5 20.1a6.34 6.34 0 0 0 10.86-4.43v-7a8.16 8.16 0 0 0 4.77 1.52v-3.4a4.85 4.85 0 0 1-1-.1z"/></svg>
            </a>
            <a href="https://instagram.com/ajegrethompson" target="_blank" rel="noopener" class="text-zinc-400 hover:text-white transition">
                <span class="sr-only">Instagram</span>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="2" y="2" width="20" height="20" rx="5" ry="5" stroke-width="2"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z" stroke-width="2"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5" stroke-width="2"></line></svg>
            </a>
        </div>
        <p class="mt-3 text-[10px] font-mono text-zinc-600 uppercase tracking-widest">DM for 1-on-1 coaching</p>

    </div>

    <!-- Footer -->
    <footer class="py-6 text-center border-t border-zinc-900/50">
        <p class="text-[9px] font-mono text-zinc-700 uppercase tracking-[0.2em]">
            Deepr Web Services &copy; {{ date('Y') }}
        </p>
    </footer>

</body>
</html>
