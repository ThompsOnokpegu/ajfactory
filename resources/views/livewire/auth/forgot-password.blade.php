<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Account Recovery | Member Terminal</title>
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
                    Account_Recovery
                </div>
                <h1 class="text-4xl font-black text-white uppercase italic tracking-tighter">
                    Recover <span class="text-cyan-500">Access</span>
                </h1>
                <p class="text-xs text-zinc-500 mt-4 max-w-xs mx-auto leading-relaxed">
                    Enter the email tied to your account and we'll send a secure link to reset your access key.
                </p>
            </div>

            <!-- Card -->
            <div class="bg-zinc-900/50 border border-zinc-800 p-8 rounded-[2.5rem] backdrop-blur-sm shadow-2xl">

                @if (session('status'))
                    <div class="mb-6 p-4 bg-cyan-500/10 border border-cyan-500/20 rounded-xl">
                        <p class="text-[10px] font-bold text-cyan-400 uppercase tracking-widest leading-relaxed">
                            {{ session('status') }}
                        </p>
                    </div>
                @endif

                @error('email')
                    <div class="mb-6 p-4 bg-red-500/10 border border-red-500/20 rounded-xl">
                        <p class="text-[10px] font-bold text-red-500 uppercase tracking-widest leading-tight">{{ $message }}</p>
                    </div>
                @enderror

                <form method="POST" action="{{ route('password.email') }}" class="space-y-6">
                    @csrf

                    <div class="space-y-2">
                        <label for="email" class="text-[10px] font-black uppercase text-zinc-600 tracking-widest ml-1">Identity (Email)</label>
                        <input type="email" name="email" id="email" required autofocus value="{{ old('email') }}"
                            class="w-full bg-zinc-950 border border-zinc-800 text-white p-4 rounded-xl focus:border-cyan-500 focus:ring-0 transition-all font-mono text-sm placeholder:text-zinc-800"
                            placeholder="deploy@factory.io">
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="w-full py-5 bg-white text-black font-black uppercase text-sm rounded-2xl hover:bg-cyan-500 transition-all shadow-xl shadow-white/5 active:scale-[0.98]">
                            Email Reset Link
                        </button>
                    </div>
                </form>
            </div>

            <!-- Back to login -->
            <p class="mt-8 text-center">
                <a href="{{ route('login') }}" class="text-[10px] font-black uppercase tracking-widest text-zinc-600 hover:text-cyan-500 transition">← Back to login</a>
            </p>

            <p class="mt-8 text-center text-[9px] text-zinc-700 uppercase tracking-[0.4em] font-black">
                AJBuilds AI // Member Terminal
            </p>
        </div>
    </div>
</body>
</html>
