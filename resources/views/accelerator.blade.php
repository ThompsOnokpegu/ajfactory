<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>AI Automation Builder Accelerator | AJ Thompson</title>
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

        <!-- Background grid & Orbs -->
        <div class="fixed inset-0 bg-grid z-0 opacity-40"></div>
        <div class="fixed top-[-10%] left-[-10%] w-[40%] h-[40%] bg-cyan-900/20 blur-[120px] rounded-full z-0 pointer-events-none"></div>
        <div class="fixed bottom-[-10%] right-[-10%] w-[40%] h-[40%] bg-purple-900/10 blur-[120px] rounded-full z-0 pointer-events-none"></div>

        <div class="relative z-10">
            <!-- STICKY HEADER -->
            <nav class="sticky top-0 w-full z-50 bg-zinc-950/80 backdrop-blur-md border-b border-zinc-900">
                <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
                    <div class="text-xl font-black tracking-tighter italic text-white uppercase">
                        AUTO<span class="text-cyan-500">MATION</span>.ACCELERATOR
                    </div>
                    <div class="hidden md:flex items-center gap-8">
                        <a href="#curriculum" class="text-xs font-bold uppercase tracking-widest text-zinc-500 hover:text-white transition">Curriculum</a>
                        <a href="#guarantee" class="text-xs font-bold uppercase tracking-widest text-zinc-500 hover:text-white transition">Guarantee</a>
                        <a href="/checkout" class="px-5 py-2 bg-white text-black text-[10px] font-black uppercase tracking-widest rounded hover:bg-cyan-500 transition-all">Secure Spot</a>
                    </div>
                </div>
            </nav>

            <!-- HERO SECTION -->
            <section class="max-w-6xl mx-auto px-6 pt-20 pb-32 text-center">
                <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-cyan-950/30 border border-cyan-500/30 text-cyan-400 text-[10px] font-black uppercase tracking-[0.2em] mb-10 badge-pulse">
                    <span class="h-2 w-2 rounded-full bg-cyan-500 shadow-[0_0_10px_#06b6d4] animate-pulse"></span>
                    Limited to First 20 Builders
                </div>
                
                <h1 class="text-6xl md:text-8xl font-black text-white leading-[0.85] tracking-tighter uppercase italic font-['Space_Grotesk'] mb-8">
                    BUILD COMMERCIAL <br>
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-cyan-400 via-blue-500 to-cyan-400 bg-300% animate-gradient">AI AGENTS.</span>
                </h1>
                
                <p class="text-xl md:text-2xl text-zinc-400 max-w-3xl mx-auto font-medium mb-12 leading-snug">
                    I'm handing you the keys to the <span class="text-white border-b-2 border-cyan-500">exact automation stack</span> I use to build $1,500/mo systems for clinics and service businesses.
                </p>

                <div class="flex flex-col sm:flex-row gap-6 justify-center items-center">
                    <a href="/checkout" class="group relative px-10 py-5 bg-cyan-500 text-black font-black uppercase tracking-tighter text-xl hover:scale-105 transition-all rounded shadow-[0_0_30px_rgba(6,182,212,0.3)]">
                        Join The Accelerator
                        {{-- <span class="absolute -top-3 -right-3 bg-red-600 text-white text-[9px] px-2 py-1 rotate-3">Only 7 Spots Left</span> --}}
                    </a>
                </div>
                
                <div class="mt-16 flex justify-center items-center gap-12 opacity-40 grayscale hover:grayscale-0 transition-all duration-700">
                    <span class="text-xl font-black font-['Space_Grotesk'] tracking-tighter italic">LARAVEL</span>
                    <span class="text-xl font-black font-['Space_Grotesk'] tracking-tighter italic">N8N</span>
                    <span class="text-xl font-black font-['Space_Grotesk'] tracking-tighter italic">VAPI</span>
                    <span class="text-xl font-black font-['Space_Grotesk'] tracking-tighter italic">PINECONE</span>
                </div>
            </section>

            <!-- THE STACK: VISUAL BREAKDOWN -->
            <section id="curriculum" class="max-w-7xl mx-auto px-6 py-24 border-t border-zinc-900">
                <div class="grid lg:grid-cols-3 gap-16">
                    <div class="lg:col-span-1 space-y-6">
                        <div class="text-xs font-mono text-cyan-500 uppercase tracking-widest">// Phase 01: Deployment</div>
                        <h2 class="text-4xl font-black text-white uppercase italic tracking-tighter">The End of <br>No-Code Toys.</h2>
                        <p class="text-zinc-500 leading-relaxed">Most people build simple bots that break. We build <strong>resilient commercial-grade automation</strong> that handles errors, keeps logs, and scales with the business.</p>
                        
                        <div class="pt-8 space-y-4">
                            <div class="flex items-center gap-3">
                                <div class="h-5 w-5 rounded-full bg-cyan-500/20 border border-cyan-500/50 flex items-center justify-center">
                                    <svg class="w-3 h-3 text-cyan-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                </div>
                                <span class="text-sm font-bold text-zinc-300">Self-Hosted n8n (Save $50/mo)</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="h-5 w-5 rounded-full bg-cyan-500/20 border border-cyan-500/50 flex items-center justify-center">
                                    <svg class="w-3 h-3 text-cyan-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                </div>
                                <span class="text-sm font-bold text-zinc-300">WhatsApp Cloud API (Meta)</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="h-5 w-5 rounded-full bg-cyan-500/20 border border-cyan-500/50 flex items-center justify-center">
                                    <svg class="w-3 h-3 text-cyan-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                </div>
                                <span class="text-sm font-bold text-zinc-300">Vapi Voice AI Architecture</span>
                            </div>
                        </div>
                    </div>

                    <div class="lg:col-span-2 grid md:grid-cols-2 gap-4">
                        <div class="p-8 bg-zinc-900/50 border border-zinc-800 rounded-3xl group hover:border-cyan-500/50 transition-all">
                            <div class="text-[10px] font-mono text-zinc-600 mb-4 uppercase tracking-[0.2em]">Deployment_01</div>
                            <h3 class="text-xl font-bold text-white mb-4 italic uppercase">The WhatsApp Interceptor</h3>
                            <p class="text-sm text-zinc-500 leading-relaxed">Build the bot that doesn't just chat—it checks databases, confirms appointments, and triggers human-handoffs when things get serious.</p>
                        </div>
                        <div class="p-8 bg-zinc-900/50 border border-zinc-800 rounded-3xl group hover:border-cyan-500/50 transition-all">
                            <div class="text-[10px] font-mono text-zinc-600 mb-4 uppercase tracking-[0.2em]">Deployment_02</div>
                            <h3 class="text-xl font-bold text-white mb-4 italic uppercase">The AI Receptionist (Vapi)</h3>
                            <p class="text-sm text-zinc-500 leading-relaxed">Master zero-latency voice agents. Learn how to prompt them for dental clinics, HVAC shops, and solar agencies.</p>
                        </div>
                        <div class="p-8 bg-zinc-900/50 border border-zinc-800 rounded-3xl group hover:border-cyan-500/50 transition-all">
                            <div class="text-[10px] font-mono text-zinc-600 mb-4 uppercase tracking-[0.2em]">Deployment_03</div>
                            <h3 class="text-xl font-bold text-white mb-4 italic uppercase">RAG & Vector Memory</h3>
                            <p class="text-sm text-zinc-500 leading-relaxed">The AI needs to know the business. We connect Pinecone and PDFs so your agents speak with 100% accuracy from local data.</p>
                        </div>
                        <div class="p-8 bg-zinc-900 border border-cyan-900/30 rounded-3xl shimmer">
                            <div class="text-[10px] font-mono text-cyan-500 mb-4 uppercase tracking-[0.2em]">The_Snapshot_Vault</div>
                            <h3 class="text-xl font-bold text-white mb-4 italic uppercase">Import & Go.</h3>
                            <p class="text-sm text-zinc-400 leading-relaxed">I am giving you the raw JSON files for every agent. Don't want to build from scratch? Click Import, paste your API keys, and deploy.</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- WHO THIS IS FOR -->
            <section class="max-w-7xl mx-auto px-6 py-24 bg-zinc-900/30 rounded-[3rem] border border-zinc-900">
                <div class="grid md:grid-cols-2 gap-16">
                    <div class="p-10 border-l-4 border-cyan-500 bg-zinc-900/50 rounded-r-3xl">
                        <h4 class="text-xl font-bold text-white uppercase italic tracking-tighter mb-6">This is for you if...</h4>
                        <ul class="space-y-4">
                            <li class="flex items-start gap-3 text-sm text-zinc-400">
                                <span class="text-cyan-500 mt-1">→</span>
                                You're tired of watching "YouTube gurus" and want to actually ship code.
                            </li>
                            <li class="flex items-start gap-3 text-sm text-zinc-400">
                                <span class="text-cyan-500 mt-1">→</span>
                                You want to charge businesses $1,500+ Setup Fees for automation.
                            </li>
                            <li class="flex items-start gap-3 text-sm text-zinc-400">
                                <span class="text-cyan-500 mt-1">→</span>
                                You want to master n8n, the most powerful visual logic engine on Earth.
                            </li>
                        </ul>
                    </div>
                    <div class="p-10 border-l-4 border-zinc-800 bg-zinc-950/50 rounded-r-3xl">
                        <h4 class="text-xl font-bold text-zinc-500 uppercase italic tracking-tighter mb-6">This is NOT for you if...</h4>
                        <ul class="space-y-4">
                            <li class="flex items-start gap-3 text-sm text-zinc-600 italic">
                                <span class="text-zinc-700 mt-1">×</span>
                                You're looking for a "get rich quick" button without doing the work.
                            </li>
                            <li class="flex items-start gap-3 text-sm text-zinc-600 italic">
                                <span class="text-zinc-700 mt-1">×</span>
                                You're afraid of technical documentation and learning APIs.
                            </li>
                            <li class="flex items-start gap-3 text-sm text-zinc-600 italic">
                                <span class="text-zinc-700 mt-1">×</span>
                                You aren't ready to invest in your own deployment infrastructure.
                            </li>
                        </ul>
                    </div>
                </div>
            </section>

            <!-- THE FOUNDING OFFER -->
            <section id="pricing" class="max-w-4xl mx-auto px-6 py-32 text-center">
                <div class="space-y-4 mb-12">
                    <h2 class="text-4xl md:text-6xl font-black text-white uppercase italic tracking-tighter">The Founding Batch.</h2>
                    <p class="text-zinc-500">Only 20 spots available. Once they're gone, the price doubles.</p>
                </div>

                <div class="bg-zinc-900 border border-zinc-800 p-8 md:p-16 rounded-[4rem] relative overflow-hidden">
                    <div class="absolute inset-0 shimmer opacity-50"></div>
                    
                    <div class="relative z-10">
                        <div class="flex flex-col items-center mb-12">
                            <div class="text-zinc-500 line-through text-lg mb-2">Total Value: N450,000+</div>
                            <div class="text-6xl md:text-8xl font-black text-white tracking-tighter italic">N150,000</div>
                            <div class="text-zinc-500 text-sm font-mono mt-2 tracking-widest uppercase">/ Founding Member Price</div>
                        </div>

                        <div class="grid sm:grid-cols-2 gap-4 max-w-lg mx-auto mb-12 text-left">
                            <div class="flex items-center gap-2 text-[10px] font-black uppercase text-zinc-400">
                                <svg class="w-4 h-4 text-cyan-500" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/></svg>
                                30-Day Deployment Roadmap
                            </div>
                            <div class="flex items-center gap-2 text-[10px] font-black uppercase text-zinc-400">
                                <svg class="w-4 h-4 text-cyan-500" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/></svg>
                                Live Weekly Build-Along Q&A
                            </div>
                            <div class="flex items-center gap-2 text-[10px] font-black uppercase text-zinc-400">
                                <svg class="w-4 h-4 text-cyan-500" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/></svg>
                                The Full Snapshot Vault
                            </div>
                            <div class="flex items-center gap-2 text-[10px] font-black uppercase text-zinc-400">
                                <svg class="w-4 h-4 text-cyan-500" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/></svg>
                                Private Community Access
                            </div>
                        </div>

                        <a href="/checkout" class="block w-full py-6 bg-cyan-500 text-black font-black uppercase tracking-tighter text-2xl rounded-2xl hover:bg-cyan-500 transition-all shadow-[0_20px_50px_rgba(255,255,255,0.1)]">
                            SECURE MY SPOT NOW
                        </a>
                    </div>
                </div>
            </section>

            <!-- GUARANTEE -->
            <section id="guarantee" class="max-w-3xl mx-auto px-6 py-20 text-center mb-32">
                <div class="mb-8 flex justify-center">
                    <div class="w-20 h-20 border-2 border-cyan-500/20 rounded-full flex items-center justify-center">
                        <svg class="w-10 h-10 text-cyan-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                    </div>
                </div>
                <h3 class="text-2xl font-black text-white uppercase italic mb-6">The "Zero-Risk" Deployment Policy</h3>
                <p class="text-zinc-500 text-sm leading-relaxed max-w-lg mx-auto">
                    "I am so confident in this stack that if you complete the 30-day roadmap and don't have a working AI agent running on your computer, I will personally jump on a private Zoom call with you and fix your logic. I don't let my students fail."
                </p>
                <div class="mt-6 font-mono text-[10px] uppercase text-zinc-600">— AJ Thompson // Founder</div>
            </section>

            <!-- FOOTER -->
            <footer class="py-12 border-t border-zinc-900 text-center">
                <p class="text-[10px] font-mono text-zinc-700 uppercase tracking-[0.5em]">AJ.THOMPSON // AUTOMATION FACTORY // 2026</p>
            </footer>
        </div>
    </body>
</html>