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

      /* ── Shared lead gate overlay ──────────────────────────────────── */
      .taab-gate {
        position: fixed; inset: 0; z-index: 200;
        display: none;
        align-items: center; justify-content: center;
        background: rgba(8,8,10,0.85);
        backdrop-filter: blur(6px);
        padding: 1.5rem;
      }
      .taab-gate.show { display: flex; }
      .taab-gate-card {
        background: var(--surface); border: 1px solid var(--border);
        border-radius: var(--radius); padding: 2rem; max-width: 420px; width: 100%;
        animation: taabUp 0.35s ease both;
      }
      .taab-gate-eyebrow {
        font-family: 'Syne', sans-serif; font-size: 10px; font-weight: 700;
        letter-spacing: 0.12em; text-transform: uppercase; color: var(--accent); margin-bottom: 0.6rem;
      }
      .taab-gate-title { font-family: 'Syne', sans-serif; font-size: 1.35rem; font-weight: 800; line-height: 1.2; margin-bottom: 0.4rem; }
      .taab-gate-sub { font-size: 13px; color: var(--muted); font-weight: 300; margin-bottom: 1.5rem; }
      .taab-input {
        width: 100%; background: var(--bg); border: 1px solid var(--border-strong);
        color: var(--text); padding: 11px 14px; border-radius: var(--radius-sm);
        font-family: 'DM Sans', sans-serif; font-size: 14px; margin-bottom: 10px; outline: none;
      }
      .taab-input:focus { border-color: var(--accent); }
      .taab-input::placeholder { color: var(--faint); }
      .taab-gate-btn {
        width: 100%; background: var(--accent); color: var(--bg);
        font-family: 'Syne', sans-serif; font-weight: 700; font-size: 14px; letter-spacing: 0.02em;
        border: none; padding: 13px; border-radius: var(--radius-sm); cursor: pointer; transition: background 0.15s; margin-top: 4px;
      }
      .taab-gate-btn:hover { background: #d4f474; }
      .taab-gate-btn:disabled { opacity: 0.5; cursor: not-allowed; }
      .taab-gate-err { color: var(--red); font-size: 12px; min-height: 16px; margin-top: 6px; }
      .taab-gate-fine { font-size: 11px; color: var(--faint); font-weight: 300; margin-top: 12px; line-height: 1.5; }
      @keyframes taabUp { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }
    </style>
    @stack('styles')
</head>
<body>
    <div style="position: relative; z-index: 1;">
        {{ $slot }}
    </div>

    <!-- Reusable email gate -->
    <div class="taab-gate" id="taab-gate">
      <div class="taab-gate-card">
        <div class="taab-gate-eyebrow">One step left</div>
        <div class="taab-gate-title" id="taab-gate-title">See your results</div>
        <div class="taab-gate-sub">Enter your details and we'll show your results — plus the next masterclass invite.</div>
        <input class="taab-input" type="text" id="taab-name" placeholder="Full name" autocomplete="name">
        <input class="taab-input" type="email" id="taab-email" placeholder="Email address" autocomplete="email">
        <input class="taab-input" type="text" id="taab-whatsapp" placeholder="WhatsApp (optional)" autocomplete="tel">
        <div class="taab-gate-err" id="taab-gate-err"></div>
        <button class="taab-gate-btn" id="taab-gate-btn" onclick="taabSubmitLead()">Show my results →</button>
        <div class="taab-gate-fine">No spam. We'll only email you about The AI Automation Bootcamp.</div>
      </div>
    </div>

    <script>
      // Shows the gate, then runs onSuccess() once a lead is captured (or immediately
      // for a returning lead). Used by the scorecard + ROI calculator.
      window.taabRequireLead = function (source, onSuccess) {
        window.__taabOnSuccess = onSuccess;
        window.__taabSource = source;
        if (localStorage.getItem('taab_lead')) { onSuccess(); return; }
        document.getElementById('taab-gate-err').textContent = '';
        document.getElementById('taab-gate').classList.add('show');
        setTimeout(() => document.getElementById('taab-name').focus(), 50);
      };

      function taabSubmitLead() {
        const name = document.getElementById('taab-name').value.trim();
        const email = document.getElementById('taab-email').value.trim();
        const whatsapp = document.getElementById('taab-whatsapp').value.trim();
        const err = document.getElementById('taab-gate-err');
        const btn = document.getElementById('taab-gate-btn');

        if (name.length < 2) { err.textContent = 'Please enter your name.'; return; }
        if (!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(email)) { err.textContent = 'Please enter a valid email.'; return; }

        err.textContent = '';
        btn.disabled = true; btn.textContent = 'Saving…';

        fetch('/taab/lead', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
          },
          body: JSON.stringify({ name, email, whatsapp, source: window.__taabSource }),
        })
        .then(async (r) => {
          if (!r.ok) { const d = await r.json().catch(() => ({})); throw new Error(d.message || 'Something went wrong.'); }
          return r.json();
        })
        .then(() => {
          localStorage.setItem('taab_lead', email);
          document.getElementById('taab-gate').classList.remove('show');
          btn.disabled = false; btn.textContent = 'Show my results →';
          if (typeof window.__taabOnSuccess === 'function') window.__taabOnSuccess();
        })
        .catch((e) => {
          btn.disabled = false; btn.textContent = 'Show my results →';
          err.textContent = e.message || 'Network error — please try again.';
        });
      }
    </script>
    @stack('scripts')
</body>
</html>
