<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name') }} | Speed-to-Lead Automation</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;700;900&family=Inter:wght@400;600&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
        
    </head>
    <body class="bg-zinc-950 text-white font-sans antialiased selection:bg-orange-500 selection:text-white overflow-x-hidden">

        <div class="fixed inset-0 z-0 pointer-events-none opacity-10" 
             style="background-image: linear-gradient(to right, #3f3f46 1px, transparent 1px), linear-gradient(to bottom, #3f3f46 1px, transparent 1px); background-size: 40px 40px;">
        </div>

        <div class="fixed top-0 left-1/2 -translate-x-1/2 w-[800px] h-[800px] bg-purple-900/20 blur-[120px] rounded-full z-0 pointer-events-none"></div>

        <div class="relative z-10 max-w-7xl mx-auto px-6 lg:px-8">
            
            <nav class="flex items-center justify-between py-8">
                <div class="text-2xl font-black tracking-tighter italic">
                    AJ.<span class="text-purple-500">THOMPSON</span>
                </div>
                <div class="flex items-center gap-6">
                    <!-- Added Builders Link -->
                    <a href="/builders" class="md:block text-xs font-mono text-cyan-500 hover:text-cyan-400 uppercase tracking-widest transition">
                        For Builders
                    </a>
                </div>
            </nav>

            <main class="grid lg:grid-cols-2 gap-16 items-start py-16 lg:py-24">
    
                <div class="space-y-6">
                    <h1 class="text-6xl lg:text-8xl font-black leading-[0.85] tracking-tighter font-['Space_Grotesk'] uppercase italic">
                        STOP MISSING <br>
                        <span class="text-orange-500">THE LEADS</span> <br>
                        YOU PAID FOR.
                    </h1>

                    <div class="max-w-md space-y-4">
                        <p class="text-lg text-zinc-300 leading-snug">
                            You’re running a service business. Your ads are bringing in leads. But if you aren't capturing them instantly across every channel, you're burning cash.
                        </p>
                        <p class="text-zinc-500 font-mono text-sm leading-relaxed">
                            I build complete automation ecosystems—AI Voice, SMS, WhatsApp, and Custom Workflows—that intercept, qualify, and book your leads 24/7. It's more than just a phone bot; it's your entire front-of-house, automated.
                        </p>
                    </div>

                    <div class="flex items-center gap-6 pt-4">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-white leading-none">10s</div>
                            <div class="text-[10px] text-zinc-500 uppercase font-black tracking-widest">Response</div>
                        </div>
                        <div class="h-8 w-px bg-zinc-800"></div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-white leading-none">24/7</div>
                            <div class="text-[10px] text-zinc-500 uppercase font-black tracking-widest">Coverage</div>
                        </div>
                        <div class="h-8 w-px bg-zinc-800"></div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-white leading-none">0</div>
                            <div class="text-[10px] text-zinc-500 uppercase font-black tracking-widest">Missed Ops</div>
                        </div>
                    </div>
                </div>

                <div class="relative sticky top-8">
                    <div class="absolute -top-6 -left-6 bg-orange-500 text-black px-4 py-1 text-xs font-black uppercase tracking-widest rotate-[-2deg] z-20">
                        Workflow Audit
                    </div>
                    <livewire:lead-demo-form />
                </div>

            </main>

            <div class="border-t border-zinc-900 py-12 mt-12">
                <p class="text-center text-zinc-600 text-xs font-mono uppercase tracking-widest mb-8">Built on Enterprise Standards</p>
                <div class="flex justify-center items-center gap-12 opacity-50 grayscale hover:grayscale-0 transition-all duration-500">
                    <span class="text-xl font-bold font-['Space_Grotesk']">Laravel</span>
                    <span class="text-xl font-bold font-['Space_Grotesk']">n8n</span>
                    <span class="text-xl font-bold font-['Space_Grotesk']">Vapi</span>
                    <span class="text-xl font-bold font-['Space_Grotesk']">Meta</span>
                </div>
            </div>

        </div>
        
        @livewireScripts
    </body>
</html>