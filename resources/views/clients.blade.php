<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Watch Me Land AI Automation Clients | AJBuilds AI</title>
        <meta name="description" content="I don't teach what I haven't proven. For ~60 days I'm documenting exactly how I land AI automation clients from scratch — real messages, real numbers, wins and flops. Get on the list.">

        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ config('app.url') }}/earn">
        <meta property="og:title" content="Watch Me Land AI Automation Clients (Real Numbers)">
        <meta property="og:description" content="No course pitch. ~60 days documenting exactly how I land AI automation clients — the messages, the numbers, the ones that flop.">
        <meta property="og:image" content="{{ asset('img/og-preview.jpg') }}">
        <meta property="twitter:card" content="summary_large_image">
        <meta property="twitter:image" content="{{ asset('img/og-preview.jpg') }}">

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;700;900&family=Inter:wght@400;600&display=swap" rel="stylesheet">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="bg-zinc-950 text-white font-sans antialiased overflow-x-hidden selection:bg-cyan-500 selection:text-black">

        <!-- Cyan Grid Background -->
        <div class="fixed inset-0 z-0 pointer-events-none opacity-[0.03]"
             style="background-image: linear-gradient(to right, #06b6d4 1px, transparent 1px), linear-gradient(to bottom, #06b6d4 1px, transparent 1px); background-size: 50px 50px;">
        </div>

        <div class="relative z-10 max-w-7xl mx-auto px-6 lg:px-8 min-h-screen flex flex-col">

            <nav class="flex items-center justify-between py-8">
                <a href="/accelerator" class="text-xl font-black tracking-tighter italic text-zinc-500 hover:text-white transition">
                    AJBUILDS<span class="text-cyan-500"> AI</span>
                </a>
                <a href="https://tiktok.com/@ajthompson.ai" target="_blank" rel="noopener" class="text-[10px] font-mono uppercase tracking-widest text-zinc-600 hover:text-cyan-400 transition">Building in public →</a>
            </nav>

            <main class="flex-1 flex items-center justify-center py-8">
                <livewire:client-leads />
            </main>

        </div>

        @livewireScripts
    </body>
</html>
