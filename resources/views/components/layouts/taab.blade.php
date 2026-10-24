@props([
    'title' => 'TAAB - The AI Automation Bootcamp',
    'description' => 'Free tools from The AI Automation Bootcamp - see where you stand, what it costs, and the exact stack to use.',
])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @include('partials.icons')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }}</title>
    <meta name="description" content="{{ $description }}">

    <!-- Open Graph / Twitter -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $title }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:image" content="{{ asset('img/taab-og.jpg') }}">
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:image" content="{{ asset('img/taab-og.jpg') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
      {{-- ── TAAB brand tokens ─────────────────────────────────────────────
         TAAB shares the Accelerator's identity: zinc-950 + cyan #06b6d4,
         Space Grotesk display over Inter body. It used to run its own
         lime/Syne brand, which made the funnel look like two companies -
         a registrant left a lime page, got a lime email and landed on a
         cyan checkout. Unified 24 Oct 2026.

         The two products are still positioned differently (TAAB sells
         clarity, the Accelerator sells building) - that lives in the COPY,
         not in the palette. Don't re-fork the colours to signal it.

         Values are the Tailwind stops the Accelerator page uses, so the
         two stay in step: zinc-950/900/800/700/500, cyan-500/400. --}}
      :root {
        --bg: #09090b;            /* zinc-950 */
        --surface: #18181b;       /* zinc-900 */
        --surface2: #27272a;      /* zinc-800 */
        --border: rgba(255,255,255,0.07);
        --border-strong: rgba(255,255,255,0.14);
        --border-hover: rgba(255,255,255,0.14);
        --text: #fafafa;          /* zinc-50  */
        --muted: #71717a;         /* zinc-500 */
        --faint: #3f3f46;         /* zinc-700 */
        --accent: #06b6d4;        /* cyan-500 */
        --accent-bright: #22d3ee; /* cyan-400 */
        --accent-dim: rgba(6,182,212,0.12);
        --accent-dim2: rgba(6,182,212,0.06);
        --accent-border: rgba(6,182,212,0.30);
        --purple: #a855f7;        /* purple-500 - the second orb, and "advanced" */
        --green: #4ade80; --green-bg: rgba(74,222,128,0.10); --green-border: rgba(74,222,128,0.28);
        --amber: #fbbf24; --amber-bg: rgba(251,191,36,0.10); --amber-border: rgba(251,191,36,0.28);
        --red: #f87171;   --red-bg: rgba(248,113,113,0.10);  --red-border: rgba(248,113,113,0.28);
        {{-- tool-stack level accents. Intermediate IS the brand cyan on purpose:
           the ladder reads beginner -> house style -> beyond. --}}
        --beg: #4ade80; --beg-bg: rgba(74,222,128,0.10);  --beg-border: rgba(74,222,128,0.28);
        --int: #22d3ee; --int-bg: rgba(34,211,238,0.10);   --int-border: rgba(34,211,238,0.28);
        --adv: #c084fc; --adv-bg: rgba(192,132,252,0.10);  --adv-border: rgba(192,132,252,0.28);
        --highlight: #06b6d4;
        --font-display: 'Space Grotesk', sans-serif;
        --font-body: 'Inter', sans-serif;
        --font-mono: ui-monospace, SFMono-Regular, Menlo, Consolas, 'Liberation Mono', monospace;
        {{-- Cards stay soft (the Accelerator uses rounded-2xl/3xl); buttons and
           badges are tight. Don't collapse these into one value. --}}
        --radius: 14px;
        --radius-sm: 6px;
      }
      *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
      html { scroll-behavior: smooth; }
      body {
        font-family: var(--font-body);
        background: var(--bg);
        color: #d4d4d8;             /* zinc-300, as on the Accelerator */
        min-height: 100vh;
        line-height: 1.6;
        -webkit-font-smoothing: antialiased;
      }
      ::selection { background: var(--accent); color: #000; }

      {{-- Background grid + orbs. Same construction as the Accelerator page -
         if you retune one, retune both. Fixed and pointer-events:none so they
         never intercept a tap on mobile. --}}
      .bg-grid-fixed {
        position: fixed; inset: 0; z-index: 0; pointer-events: none; opacity: 0.4;
        background-image:
          linear-gradient(to right, #18181b 1px, transparent 1px),
          linear-gradient(to bottom, #18181b 1px, transparent 1px);
        background-size: 50px 50px;
      }
      .bg-orb { position: fixed; width: 40%; height: 40%; border-radius: 9999px; filter: blur(120px); z-index: 0; pointer-events: none; }
      .bg-orb-cyan { top: -10%; left: -10%; background: rgba(22,78,99,0.20); }     /* cyan-900/20 */
      .bg-orb-purple { bottom: -10%; right: -10%; background: rgba(88,28,135,0.10); } /* purple-900/10 */
    </style>
    @stack('styles')
    @include('partials.meta-pixel')
</head>
<body>
    <div class="bg-grid-fixed"></div>
    <div class="bg-orb bg-orb-cyan"></div>
    <div class="bg-orb bg-orb-purple"></div>

    <div style="position: relative; z-index: 1;">
        {{ $slot }}
    </div>

    {{-- Lead gate removed - the TAAB tools are ungated. --}}
    @stack('scripts')
</body>
</html>
