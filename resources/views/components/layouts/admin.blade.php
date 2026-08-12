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
        ['admin.reviews', 'Reviews'],
        ['admin.snippets', 'Snippets'],
        ['admin.masterclass', 'Masterclass'],
        ['admin.leads', 'Leads & Waitlist'],
        ['admin.resources', 'Free Resources'],
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
            @php
                $icons = [
                    'admin.overview' => 'M3 12l9-9 9 9M5 10v10a1 1 0 001 1h4v-6h4v6h4a1 1 0 001-1V10',
                    'admin.enrollments' => 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4z',
                    'admin.checkpoints' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
                    'admin.reviews' => 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.9 9.9 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
                    'admin.snippets' => 'M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4',
                    'admin.masterclass' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
                    'admin.leads' => 'M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z',
                    'admin.resources' => 'M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4',
                ];
            @endphp
            @foreach($nav as [$route, $label])
                @php $active = request()->routeIs($route); @endphp
                <a href="{{ route($route) }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-xs font-bold uppercase tracking-widest transition
                   {{ $active ? 'bg-cyan-500/10 text-cyan-400 border border-cyan-500/20' : 'text-zinc-500 hover:text-white hover:bg-zinc-800/50 border border-transparent' }}">
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons[$route] ?? '' }}"/></svg>
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
