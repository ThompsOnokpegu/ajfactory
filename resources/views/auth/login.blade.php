<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>System Access | Automation Factory</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@700;900&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <style>
        .bg-grid { background-image: linear-gradient(to right, #18181b 1px, transparent 1px), linear-gradient(to bottom, #18181b 1px, transparent 1px); background-size: 50px 50px; }
    </style>
</head>
<body class="bg-zinc-950 text-zinc-300 font-sans antialiased overflow-hidden selection:bg-cyan-500 selection:text-black">
    
    <div class="fixed inset-0 bg-grid z-0 opacity-20"></div>

    <div class="relative z-10 min-h-screen flex items-center justify-center p-6">
        <div class="max-w-md w-full">
            
            <!-- Branding Header -->
            <div class="text-center mb-10">
                <div class="inline-block px-3 py-1 rounded bg-cyan-500/10 border border-cyan-500/20 text-[10px] font-mono text-cyan-500 uppercase tracking-[0.3em] mb-4">
                    Secure_Access_Required
                </div>
                <h1 class="text-4xl font-black text-white uppercase italic tracking-tighter">
                    Member <span class="text-cyan-500">Terminal</span>
                </h1>
            </div>

            <!-- Login Form Container -->
            <div class="bg-zinc-900/50 border border-zinc-800 p-8 rounded-[2.5rem] backdrop-blur-sm shadow-2xl">
                
                @if ($errors->any())
                    <div class="mb-6 p-4 bg-red-500/10 border border-red-500/20 rounded-xl">
                        <p class="text-[10px] font-bold text-red-500 uppercase tracking-widest leading-tight">
                            Access Denied: Check credentials and retry.
                        </p>
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="space-y-6">
                    @csrf

                    <!-- Email Field -->
                    <div class="space-y-2">
                        <label for="email" class="text-[10px] font-black uppercase text-zinc-600 tracking-widest ml-1">Identity (Email)</label>
                        <input type="email" name="email" id="email" required autofocus
                            class="w-full bg-zinc-950 border border-zinc-800 text-white p-4 rounded-xl focus:border-cyan-500 focus:ring-0 transition-all font-mono text-sm placeholder:text-zinc-800"
                            placeholder="deploy@factory.io">
                    </div>

                    <!-- Password Field -->
                    <div class="space-y-2">
                        <label for="password" class="text-[10px] font-black uppercase text-zinc-600 tracking-widest ml-1">Access_Key (Password)</label>
                        <input type="password" name="password" id="password" required
                            class="w-full bg-zinc-950 border border-zinc-800 text-white p-4 rounded-xl focus:border-cyan-500 focus:ring-0 transition-all font-mono text-sm placeholder:text-zinc-800"
                            placeholder="••••••••••••">
                    </div>

                    <!-- Remember Me -->
                    <div class="flex items-center justify-between px-1">
                        <label class="flex items-center gap-2 cursor-pointer group">
                            <input type="checkbox" name="remember" class="rounded border-zinc-800 bg-zinc-950 text-cyan-500 focus:ring-0 focus:ring-offset-0">
                            <span class="text-[9px] font-bold uppercase text-zinc-500 group-hover:text-zinc-300 transition">Stay Authenticated</span>
                        </label>
                        <a href="{{ route('password.request') }}" class="text-[9px] font-bold uppercase text-zinc-600 hover:text-cyan-500 transition">Lost Access?</a>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-4">
                        <button type="submit" class="w-full py-5 bg-white text-black font-black uppercase text-sm rounded-2xl hover:bg-cyan-500 transition-all shadow-xl shadow-white/5 active:scale-[0.98]">
                            Establish Connection
                        </button>
                    </div>
                </form>
            </div>

            <!-- Footer Intel -->
            <p class="mt-10 text-center text-[9px] text-zinc-700 uppercase tracking-[0.4em] font-black">
                Automation Factory // Batch_001 // v1.0
            </p>
        </div>
    </div>
</body>
</html>