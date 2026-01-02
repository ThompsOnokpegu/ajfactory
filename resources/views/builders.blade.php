<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Automation Builders | Learn the Stack</title>
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

        <div class="relative z-10 max-w-7xl mx-auto px-6 lg:px-8 h-screen flex flex-col">
            
            <nav class="flex items-center justify-between py-8">
                <a href="/" class="text-xl font-black tracking-tighter italic text-zinc-600 hover:text-white transition">
                    AUTO<span class="text-zinc-700 hover:text-purple-500 transition">MATION</span>.FACTORY
                </a>
            </nav>

            <main class="flex-1 flex items-center justify-center -mt-20">
                <livewire:student-waitlist />
            </main>

        </div>
        
        @livewireScripts
    </body>
</html>