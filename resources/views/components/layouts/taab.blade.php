@props([
    'title' => 'TAAB — The AI Automation Bootcamp',
    'description' => 'Free tools from The AI Automation Bootcamp — see where you stand, what it costs, and the exact stack to use.',
])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }}</title>
    <meta name="description" content="{{ $description }}">

    <!-- Open Graph / Twitter -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $title }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:image" content="{{ asset('img/taab-og.jpg') }}"> {{-- {{TODO: TAAB OG share image}} --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:image" content="{{ asset('img/taab-og.jpg') }}">

    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@400;500&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
    <style>
      /* ── TAAB brand tokens — dark + lime ───────────────────────────── */
      :root {
        --bg: #0c0c0e;
        --surface: #131316;
        --surface2: #1a1a1f;
        --border: rgba(255,255,255,0.07);
        --border-strong: rgba(255,255,255,0.14);
        --border-hover: rgba(255,255,255,0.14);
        --text: #f0ede8;
        --muted: #7a7875;
        --faint: #56544f;
        --accent: #c8f064;
        --accent-dim: rgba(200,240,100,0.12);
        --accent-dim2: rgba(200,240,100,0.06);
        --green: #8fe07a; --green-bg: rgba(143,224,122,0.10); --green-border: rgba(143,224,122,0.28);
        --amber: #f5a623; --amber-bg: rgba(245,166,35,0.10); --amber-border: rgba(245,166,35,0.28);
        --red: #ff6b6b;   --red-bg: rgba(255,107,107,0.10);  --red-border: rgba(255,107,107,0.28);
        /* tool-stack level accents (dark-friendly) */
        --beg: #8fe07a; --beg-bg: rgba(143,224,122,0.10); --beg-border: rgba(143,224,122,0.28);
        --int: #60c8f0; --int-bg: rgba(96,200,240,0.10);  --int-border: rgba(96,200,240,0.28);
        --adv: #c87df0; --adv-bg: rgba(200,125,240,0.10); --adv-border: rgba(200,125,240,0.28);
        --highlight: #c8f064;
        --radius: 14px;
        --radius-sm: 8px;
      }
      *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
      html { scroll-behavior: smooth; }
      body {
        font-family: 'DM Sans', sans-serif;
        background: var(--bg);
        color: var(--text);
        min-height: 100vh;
        line-height: 1.6;
      }
      /* Subtle noise texture */
      body::before {
        content: '';
        position: fixed; inset: 0;
        background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='0.04'/%3E%3C/svg%3E");
        pointer-events: none; z-index: 0; opacity: 0.5;
      }
    </style>
    @stack('styles')
</head>
<body>
    <div style="position: relative; z-index: 1;">
        {{ $slot }}
    </div>

    {{-- Lead gate removed — the TAAB tools are ungated. --}}
    @stack('scripts')
</body>
</html>
