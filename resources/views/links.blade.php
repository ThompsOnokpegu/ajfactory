<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Links | AJ Thompson</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@700;900&family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        .bg-grid { background-image: linear-gradient(to right, #18181b 1px, transparent 1px), linear-gradient(to bottom, #18181b 1px, transparent 1px); background-size: 40px 40px; }
        .btn-hover-effect:hover { box-shadow: 0 0 20px rgba(6, 182, 212, 0.15); border-color: rgba(6, 182, 212, 0.5); }
    </style>
</head>
<body class="bg-zinc-950 text-white font-sans antialiased flex flex-col min-h-screen">

    <!-- Background -->
    <div class="fixed inset-0 bg-grid z-0 opacity-20 pointer-events-none"></div>
    <div class="fixed top-0 left-1/2 -translate-x-1/2 w-[600px] h-[600px] bg-cyan-900/10 blur-[100px] rounded-full z-0 pointer-events-none"></div>

    <div class="relative z-10 w-full max-w-md mx-auto px-6 py-16 flex-1 flex flex-col items-center">
        
        <!-- Profile / Brand -->
        <div class="mb-10 text-center space-y-3">
            <div class="w-24 h-24 mx-auto rounded-full bg-zinc-900 border border-zinc-800 flex items-center justify-center shadow-2xl relative">
                <!-- Headshot Image -->
                <img src="{{ asset('img/headshot.jpg') }}" alt="AJ Thompson" class="w-full h-full object-cover rounded-full">
                <!-- Status Dot -->
                <div class="absolute bottom-0 right-0 w-6 h-6 bg-cyan-500 border-4 border-zinc-950 rounded-full"></div>
            </div>
            <div>
                <h1 class="text-2xl font-black uppercase italic tracking-tighter">AJ Thompson</h1>
                <p class="text-xs font-mono text-zinc-500 uppercase tracking-widest mt-1">AI Automation Architect</p>
            </div>
        </div>

        <!-- The Stack -->
        <div class="w-full space-y-4">
            
            <!-- 1. Agency Lead Magnet (Generalized) -->
            <a href="https://cal.com/thompson-ajegre" class="group relative block w-full bg-zinc-900/80 border border-zinc-800 p-4 rounded-xl text-center transition-all duration-300 btn-hover-effect hover:-translate-y-1">
                <div class="flex items-center justify-center gap-3">
                    <span class="text-lg">💼</span>
                    <span class="font-bold text-white tracking-wide">Service Business? Book AI Audit</span>
                </div>
                <div class="absolute inset-0 border border-cyan-500/0 rounded-xl transition-all group-hover:border-cyan-500/20"></div>
            </a>

            <!-- 2. Accelerator Waitlist -->
            <a href="/builders" class="group relative block w-full bg-zinc-900/80 border border-zinc-800 p-4 rounded-xl text-center transition-all duration-300 btn-hover-effect hover:-translate-y-1">
                <div class="flex items-center justify-center gap-3">
                    <span class="text-lg">⚡️</span>
                    <span class="font-bold text-white tracking-wide">Join AI Accelerator (Cohort 2)</span>
                </div>
                <!-- Subtle "Closed" or "Waitlist" tag if needed -->
                <span class="absolute top-1/2 -translate-y-1/2 right-4 h-1.5 w-1.5 rounded-full bg-cyan-500 animate-pulse"></span>
            </a>

            <!-- 3. The Playbook (Lead Magnet) -->
            <a href="#" class="group relative block w-full bg-white text-zinc-950 border border-white p-4 rounded-xl text-center transition-all duration-300 hover:bg-cyan-400 hover:border-cyan-400 hover:-translate-y-1 hover:shadow-[0_0_25px_rgba(34,211,238,0.4)]">
                <div class="flex items-center justify-center gap-3">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    <span class="font-black uppercase tracking-tight">Get the FREE Audit Playbook</span>
                </div>
            </a>

        </div>

        <!-- Socials -->
        <div class="mt-12 flex items-center gap-6 opacity-60">
            <a href="https://tiktok.com/@ajthompson.ai" target="_blank" class="text-zinc-400 hover:text-white transition">
                <span class="sr-only">TikTok</span>
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64 2.93 2.93 0 0 1 .88.13V9.4a6.84 6.84 0 0 0-1-.05A6.33 6.33 0 0 0 5 20.1a6.34 6.34 0 0 0 10.86-4.43v-7a8.16 8.16 0 0 0 4.77 1.52v-3.4a4.85 4.85 0 0 1-1-.1z"/></svg>
            </a>
            <a href="https://instagram.com/ajegrethompson" target="_blank" class="text-zinc-400 hover:text-white transition">
                <span class="sr-only">Instagram</span>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="2" y="2" width="20" height="20" rx="5" ry="5" stroke-width="2"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z" stroke-width="2"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5" stroke-width="2"></line></svg>
            </a>
        </div>

    </div>

    <!-- Footer -->
    <footer class="py-6 text-center border-t border-zinc-900/50">
        <p class="text-[9px] font-mono text-zinc-700 uppercase tracking-[0.2em]">
            Deepr Web Services &copy; {{ date('Y') }}
        </p>
    </footer>

</body>
</html>