<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Free Resources | AJ Thompson</title>
    <meta name="description" content="Free AI automation workflows, cheatsheets, and templates — grab the ones you want.">
    <meta property="og:title" content="Free AI Automation Resources">
    <meta property="og:description" content="Workflows, cheatsheets & templates — free to grab.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;700;900&family=Inter:wght@400;600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .bg-grid { background-image: linear-gradient(to right, #18181b 1px, transparent 1px), linear-gradient(to bottom, #18181b 1px, transparent 1px); background-size: 44px 44px; }
        .card-hover:hover { border-color: rgba(6,182,212,0.45); box-shadow: 0 0 24px rgba(6,182,212,0.10); }
    </style>
</head>
<body class="bg-zinc-950 text-white font-sans antialiased flex flex-col min-h-screen overflow-x-hidden">

    <div class="fixed inset-0 bg-grid z-0 opacity-20 pointer-events-none"></div>
    <div class="fixed top-0 left-1/2 -translate-x-1/2 w-[600px] h-[600px] bg-cyan-900/10 blur-[110px] rounded-full z-0 pointer-events-none"></div>

    <main class="relative z-10 w-full max-w-2xl mx-auto px-5 sm:px-6 py-14 sm:py-20 flex-1">

        <!-- Header -->
        <div class="text-center mb-10">
            <span class="inline-block py-1 px-3 rounded-full bg-cyan-900/30 border border-cyan-500/30 text-cyan-400 text-[10px] font-mono uppercase tracking-widest mb-4">
                Free · Grab & go
            </span>
            <h1 class="text-3xl sm:text-4xl font-black text-white uppercase italic tracking-tighter">
                Free <span class="text-transparent bg-clip-text bg-gradient-to-r from-cyan-400 to-blue-500">Resources</span>
            </h1>
            <p class="text-zinc-400 mt-4 max-w-md mx-auto text-sm sm:text-base leading-relaxed">
                Workflows, cheatsheets and templates I share — free. Grab what you need. No sign-up.
            </p>
        </div>

        <!-- Resources -->
        <div class="space-y-3">
            @forelse($resources as $resource)
                @php $paid = $resource->isPaid(); @endphp
                <a href="{{ $paid ? route('resource.buy', $resource) : route('resources.go', $resource) }}" @unless($paid) target="_blank" rel="noopener" @endunless
                   class="card-hover group block w-full bg-zinc-900/70 border border-zinc-800 p-4 sm:p-5 rounded-2xl transition-all duration-300 hover:-translate-y-0.5">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 h-11 w-11 rounded-xl bg-zinc-950 border border-zinc-800 flex items-center justify-center text-xl">
                            {{ $resource->emoji ?: '📦' }}
                        </div>
                        <div class="min-w-0 flex-1">
                            @if($resource->category)
                                <span class="inline-block text-[9px] font-mono uppercase tracking-widest text-cyan-400/80 mb-1">{{ $resource->category }}</span>
                            @endif
                            <div class="font-bold text-white tracking-wide group-hover:text-cyan-400 transition-colors leading-snug">{{ $resource->title }}</div>
                            @if($resource->description)
                                <p class="text-sm text-zinc-400 mt-1 leading-relaxed">{{ $resource->description }}</p>
                            @endif
                        </div>
                        <div class="flex-shrink-0 self-center text-cyan-500 font-black text-sm tracking-tight opacity-70 group-hover:opacity-100 transition whitespace-nowrap">
                            @if($paid)
                                ₦{{ number_format($resource->price) }} →
                            @else
                                Get it →
                            @endif
                        </div>
                    </div>
                </a>
            @empty
                <div class="text-center border border-dashed border-zinc-800 rounded-2xl p-12">
                    <p class="text-zinc-500 text-sm">New drops coming soon. Check back shortly.</p>
                </div>
            @endforelse
        </div>

        <div class="text-center mt-12">
            <a href="/taab" class="text-[11px] font-mono uppercase tracking-widest text-zinc-500 hover:text-cyan-400 transition">
                Want the full picture? The free TAAB Masterclass →
            </a>
        </div>
    </main>

    <footer class="relative z-10 py-6 text-center border-t border-zinc-900/50">
        <p class="text-[9px] font-mono text-zinc-700 uppercase tracking-[0.2em]">AJBuilds AI · Deepr Web Services &copy; {{ date('Y') }}</p>
    </footer>

</body>
</html>
