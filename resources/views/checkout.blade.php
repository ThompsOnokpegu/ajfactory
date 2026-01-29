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
        <!-- HEADER -->
        <header class="p-6 border-b border-zinc-900 bg-zinc-950/80 backdrop-blur-md sticky top-0 z-50">
            <div class="max-w-6xl mx-auto flex justify-between items-center">
                <a href="/accelerator" class="text-xs font-black uppercase tracking-widest text-zinc-500 hover:text-white transition">← Back to Accelerator</a>
                <div class="text-[10px] font-mono text-cyan-500 uppercase tracking-widest flex items-center gap-2">
                    <span class="h-1.5 w-1.5 rounded-full bg-cyan-500 animate-pulse"></span>
                    Terminal_Secure_Handshake
                </div>
            </div>
        </header>

        <!-- MAIN CONTENT -->
        <main class="flex-1 max-w-6xl mx-auto w-full">
            <livewire:accelerator-checkout />
        </main>

        <!-- OFFICIAL COMPLIANCE FOOTER -->
        <footer class="py-12 border-t border-zinc-900 bg-zinc-950/50 mt-auto">
            <div class="max-w-6xl mx-auto px-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-10 items-start mb-10">
                    <!-- Business Info -->
                    <div class="space-y-4">
                        <div class="text-sm font-black text-white uppercase tracking-tighter italic">
                            DEEPR<span class="text-cyan-500 text-xs">WEB SERVICES</span>
                        </div>
                        <div class="text-[11px] font-mono text-zinc-500 leading-relaxed uppercase tracking-wider">
                            <p>Jidu Airport Road Lugbe, Abuja.</p>
                            <p>Nigeria</p>
                        </div>
                        <div class="text-[11px] font-mono text-zinc-400 space-y-1">
                            <p>Support: <a href="mailto:tommyriode@gmail.com" class="text-cyan-500 hover:underline">tommyriode@gmail.com</a></p>
                            <p>Line: <a href="tel:08068125034" class="text-cyan-500 hover:underline">08068125034</a></p>
                        </div>
                    </div>

                    <!-- Quick Links -->
                    <div class="flex flex-wrap gap-6 md:justify-end">
                        <a href="{{ route("legal") }}" target="_blank" class="text-[10px] font-black uppercase tracking-widest text-zinc-500 hover:text-white transition">Terms of Service</a>
                        <a href="{{ route("legal") }}" target="_blank" class="text-[10px] font-black uppercase tracking-widest text-zinc-500 hover:text-white transition">Privacy Policy</a>
                        <a href="{{ route("legal") }}" target="_blank" class="text-[10px] font-black uppercase tracking-widest text-zinc-500 hover:text-white transition">Refund Policy</a>
                    </div>
                </div>

                <div class="pt-8 border-t border-zinc-900/50 flex flex-col md:flex-row justify-between items-center gap-4">
                    <p class="text-[10px] font-mono text-zinc-700 uppercase tracking-[0.2em]">
                        &copy; {{ date('Y') }} Deepr Web Services. All rights reserved.
                    </p>
                    <div class="flex items-center gap-4 opacity-30 grayscale pointer-events-none">
                        <!-- Visual indicator of secure payments -->
                        <span class="text-[9px] font-mono uppercase tracking-widest text-zinc-500 italic">Secure Encryption Active</span>
                    </div>
                </div>
            </div>
        </footer>
    </div>

    @livewireScripts
</body>
</html>