<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-zinc-950">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Admin' }} | AJBuilds AI</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@700;900&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <style>
        [x-cloak] { display: none !important; }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #09090b; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #27272a; border-radius: 10px; }
    </style>
</head>
<body class="h-full text-zinc-300 font-sans antialiased">
@php
    $nav = [
        ['admin.overview', 'Overview'],
        ['admin.enrollments', 'Enrollments'],
        ['admin.checkpoints', 'Checkpoints'],
        ['admin.masterclass', 'Masterclass'],
        ['admin.leads', 'Leads & Waitlist'],
    ];
@endphp
<div class="flex h-screen w-full bg-zinc-950 overflow-hidden" x-data="{ open: false }">

    <!-- Mobile overlay -->
    <div x-show="open" x-cloak @click="open = false" class="fixed inset-0 z-40 bg-zinc-950/80 backdrop-blur-sm lg:hidden"></div>

    <!-- SIDEBAR -->
    <aside class="fixed inset-y-0 left-0 z-50 w-64 bg-zinc-900 border-r border-zinc-800 flex flex-col transition-transform duration-300 lg:translate-x-0 lg:static"
           :class="open ? 'translate-x-0' : '-translate-x-full'">
        <div class="h-16 flex items-center px-6 border-b border-zinc-800">
            <span class="text-sm font-black uppercase italic tracking-tighter text-white">AJBUILDS<span class="text-cyan-500"> AI</span></span>
            <span class="ml-2 text-[9px] font-mono uppercase tracking-widest text-zinc-600">Admin</span>
        </div>
        <nav class="flex-1 overflow-y-auto custom-scrollbar p-4 space-y-1">
            @foreach($nav as [$route, $label])
                @php $active = request()->routeIs($route); @endphp
                <a href="{{ route($route) }}"
                   class="block px-3 py-2.5 rounded-lg text-xs font-bold uppercase tracking-widest transition
                   {{ $active ? 'bg-cyan-500/10 text-cyan-400 border border-cyan-500/20' : 'text-zinc-500 hover:text-white hover:bg-zinc-800/50 border border-transparent' }}">
                    {{ $label }}
                </a>
            @endforeach
        </nav>
        <div class="p-4 border-t border-zinc-800 space-y-2">
            <a href="/" class="block text-center px-3 py-2 rounded-lg text-[10px] font-black uppercase tracking-widest text-zinc-500 hover:text-cyan-400 border border-zinc-800 transition">View site →</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full text-center px-3 py-2 rounded-lg text-[10px] font-black uppercase tracking-widest text-zinc-700 hover:text-red-500 transition">Log out</button>
            </form>
        </div>
    </aside>

    <!-- MAIN -->
    <main class="flex-1 flex flex-col min-w-0 h-full overflow-hidden">
        <header class="h-16 flex items-center justify-between px-6 border-b border-zinc-800 bg-zinc-950/80 backdrop-blur sticky top-0 z-20">
            <div class="flex items-center gap-4">
                <button @click="open = true" class="lg:hidden p-2 -ml-2 text-zinc-400 hover:text-white">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                </button>
                <h1 class="text-sm font-black uppercase tracking-widest text-white">{{ $title ?? 'Admin' }}</h1>
            </div>
            <div class="text-[10px] font-mono uppercase tracking-widest text-zinc-600 hidden sm:block">
                {{ auth()->user()->name }}
            </div>
        </header>

        <div class="flex-1 overflow-y-auto custom-scrollbar p-4 lg:p-8">
            {{ $slot }}
        </div>
    </main>
</div>
@livewireScripts
</body>
</html>
