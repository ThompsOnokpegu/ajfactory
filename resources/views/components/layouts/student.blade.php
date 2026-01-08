<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name', 'Automation Factory') }}</title>

    <!-- FONTS -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;700;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- ASSETS -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <style>
        .bg-grid { background-image: linear-gradient(to right, #18181b 1px, transparent 1px), linear-gradient(to bottom, #18181b 1px, transparent 1px); background-size: 50px 50px; }
        .text-glow-cyan { text-shadow: 0 0 20px rgba(6, 182, 212, 0.4); }
    </style>
</head>
<body class="bg-zinc-950 text-zinc-300 font-sans antialiased selection:bg-cyan-500 selection:text-black">
    
    <!-- UNIVERSAL BACKGROUND -->
    <div class="fixed inset-0 bg-grid z-0 opacity-20 pointer-events-none"></div>

    <!-- MAIN CONTENT AREA -->
    <div class="relative z-10 min-h-screen">
        {{ $slot }}
    </div>

    @livewireScripts
</body>
</html>