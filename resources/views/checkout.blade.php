<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Checkout | AI Automation Accelerator</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-zinc-950 text-zinc-300 font-sans antialiased overflow-x-hidden">
    
    <div class="fixed inset-0 z-0 opacity-20 pointer-events-none" 
         style="background-image: linear-gradient(to right, #18181b 1px, transparent 1px), linear-gradient(to bottom, #18181b 1px, transparent 1px); background-size: 40px 40px;">
    </div>

    <div class="relative z-10 min-h-screen flex flex-col">
        <header class="p-6 border-b border-zinc-900 bg-zinc-950/80 backdrop-blur-md sticky top-0">
            <div class="max-w-6xl mx-auto flex justify-between items-center">
                <a href="/accelerator" class="text-xs font-black uppercase tracking-widest text-zinc-500 hover:text-white transition">← Back to Accelerator</a>
                <div class="text-[10px] font-mono text-cyan-500 uppercase tracking-widest flex items-center gap-2">
                    <span class="h-1.5 w-1.5 rounded-full bg-cyan-500 animate-pulse"></span>
                    Secure Checkout
                </div>
            </div>
        </header>

        <main class="flex-1 max-w-6xl mx-auto w-full">
            <livewire:accelerator-checkout />
        </main>
    </div>

    @livewireScripts
</body>
</html>