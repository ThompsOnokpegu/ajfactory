<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Get Paid to Build AI Automations | AJBuilds AI</title>
        <meta name="description" content="The skill is learnable and businesses are already paying for it. Get the exact path from zero to your first paying AI automation client — starting with a free masterclass.">

        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ config('app.url') }}/clients">
        <meta property="og:title" content="Get Paid to Build AI Automations">
        <meta property="og:description" content="No coding, no big following. The exact path from zero to your first paying AI automation client.">
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
                <a href="/taab" class="text-[10px] font-mono uppercase tracking-widest text-zinc-600 hover:text-cyan-400 transition">Free masterclass →</a>
            </nav>

            <main class="flex-1 flex items-center justify-center py-8">
                <livewire:client-leads />
            </main>

        </div>

        @livewireScripts
    </body>
</html>
