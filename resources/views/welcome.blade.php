<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Speed-to-Lead Automation | AJ Thompson</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;700;900&family=Inter:wght@400;600&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
        
        <style>
            .glitch-text:hover { animation: glitch 0.3s cubic-bezier(.25,.46,.45,.94) both infinite; text-shadow: 2px 0 #ff00ff, -2px 0 #00ffff; }
            @keyframes glitch { 0% { transform: translate(0); } 20% { transform: translate(-2px, 2px); } 40% { transform: translate(-2px, -2px); } 60% { transform: translate(2px, 2px); } 80% { transform: translate(2px, -2px); } 100% { transform: translate(0); } }
            @keyframes flicker { 0%, 19.999%, 22%, 62.999%, 64%, 64.999%, 70%, 100% { opacity: 1; } 20%, 21.999%, 63%, 63.999%, 65%, 69.999% { opacity: 0.4; } }
            .flicker-slow { animation: flicker 3s linear infinite; }
        </style>
    </head>
    <body class="bg-zinc-950 text-white font-sans antialiased selection:bg-orange-500 selection:text-white overflow-x-hidden">

        <!-- Background Grid -->
        <div class="fixed inset-0 z-0 pointer-events-none opacity-10" 
             style="background-image: linear-gradient(to right, #3f3f46 1px, transparent 1px), linear-gradient(to bottom, #3f3f46 1px, transparent 1px); background-size: 40px 40px;">
        </div>

        <!-- Ambient Glow -->
        <div class="fixed top-0 left-1/2 -translate-x-1/2 w-[800px] h-[800px] bg-orange-900/10 blur-[120px] rounded-full z-0 pointer-events-none"></div>

        <div class="relative z-10 max-w-7xl mx-auto px-6 lg:px-8">
            
            <!-- NAVBAR -->
            <nav class="flex items-center justify-between py-8">
                <div class="text-2xl font-black tracking-tighter italic uppercase">
                    AJ.<span class="text-orange-500">AUTOMATION</span>
                </div>
                <div class="flex items-center gap-6">
                    <a href="/accelerator" class="text-xs font-mono text-zinc-500 hover:text-white uppercase tracking-widest transition border border-zinc-800 px-4 py-2 rounded">
                        For Builders
                    </a>
                </div>
            </nav>

            <!-- HERO SECTION -->
            <main class="grid lg:grid-cols-2 gap-16 items-start py-16 lg:py-24">
    
                <!-- LEFT: The Pitch -->
                <div class="space-y-8">
                    <!-- Status Indicator -->
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded bg-zinc-900 border border-zinc-700 text-zinc-400 text-[9px] font-black uppercase tracking-[0.2em] flicker-slow">
                        <span class="h-2 w-2 rounded-full bg-red-600 shadow-[0_0_8px_#dc2626]"></span>
                        Leaking Revenue Detected
                    </div>

                    <!-- Headline -->
                    <h1 class="text-5xl lg:text-7xl font-black leading-[0.9] tracking-tighter font-['Space_Grotesk'] uppercase italic">
                        YOUR ADS ARE <br> WORKING. <br><br>
                        YOUR <span class="text-transparent bg-clip-text bg-gradient-to-r from-orange-500 to-red-500 glitch-text">SPEED-TO-LEAD</span> <br>
                        IS BROKEN.
                    </h1>

                    <!-- Sub-headline -->
                    <div class="max-w-xl space-y-6">
                        <p class="text-lg text-zinc-300 leading-snug font-medium border-l-2 border-orange-500/50 pl-6">
                            You are paying for leads that your team calls back 2 hours later. By then, they have already hired your competitor. Stop burning your marketing budget on slow follow-up.
                        </p>
                        
                        <p class="text-zinc-500 font-mono text-sm leading-relaxed">
                            I deploy commercial-grade <strong>AI Revenue Agents</strong> that intercept every lead instantly—via Voice, WhatsApp, or SMS. They qualify the prospect, handle objections, and book the appointment directly into your calendar in under 60 seconds.
                        </p>
                    </div>

                    <!-- The Stats (Proof) -->
                    <div class="flex flex-wrap items-center gap-8 pt-6 border-t border-zinc-900">
                        <div>
                            <div class="text-3xl font-black text-white italic tracking-tighter">&lt; 2s</div>
                            <div class="text-[9px] text-zinc-500 uppercase font-black tracking-widest mt-1">Response Time</div>
                        </div>
                        <div class="h-8 w-px bg-zinc-800 hidden sm:block"></div>
                        <div>
                            <div class="text-3xl font-black text-white italic tracking-tighter">24/7</div>
                            <div class="text-[9px] text-zinc-500 uppercase font-black tracking-widest mt-1">Agent Availability</div>
                        </div>
                        <div class="h-8 w-px bg-zinc-800 hidden sm:block"></div>
                        <div>
                            <div class="text-3xl font-black text-white italic tracking-tighter">100%</div>
                            <div class="text-[9px] text-zinc-500 uppercase font-black tracking-widest mt-1">Lead Capture</div>
                        </div>
                    </div>
                </div>

                <!-- RIGHT: The Demo Form (Sticky) -->
                <div class="relative sticky top-8">
                    <div class="absolute -top-6 -left-6 bg-orange-600 text-white px-4 py-1.5 text-[10px] font-black uppercase tracking-widest rotate-[-2deg] z-20 shadow-xl shadow-orange-900/20">
                        Book A Demo
                    </div>
                    <!-- The Volt Component -->
                    <livewire:lead-demo-form />
                </div>

            </main>

            <!-- FOOTER / TRUST -->
            <div class="border-t border-zinc-900 py-12 mt-12">
                <p class="text-center text-zinc-600 text-[10px] font-mono uppercase tracking-[0.3em] mb-8">
                    Built on Enterprise Infrastructure
                </p>
                <div class="flex justify-center items-center gap-12 opacity-40 grayscale hover:grayscale-0 transition-all duration-500">
                    <span class="text-xl font-black font-['Space_Grotesk'] tracking-tighter">VAPI</span>
                    <span class="text-xl font-black font-['Space_Grotesk'] tracking-tighter">OPENAI</span>
                    <span class="text-xl font-black font-['Space_Grotesk'] tracking-tighter">META</span>
                    <span class="text-xl font-black font-['Space_Grotesk'] tracking-tighter">TWILIO</span>
                </div>
            </div>

        </div>
        
        @livewireScripts
    </body>
</html>