@php
    $prefill = $prefill ?? [];
    $mc = config('taab.masterclass');
    $mcDate = !empty($mc['date']) ? \Illuminate\Support\Carbon::parse($mc['date']) : null;
    $dateLong = $mcDate ? $mcDate->translatedFormat('l j F') : 'Date to be announced';
    $closes = !empty($mc['registration_closes']) ? \Illuminate\Support\Carbon::parse($mc['registration_closes'])->translatedFormat('l j F') : null;
@endphp
<x-layouts.taab
    title="The AI Automation Bootcamp - Register | TAAB"
    description="A free 2-hour live bootcamp to get clarity before you commit to AI automation. {{ $dateLong }}, on Google Meet. Free to attend.">

@push('styles')
<style>
{{-- Page tokens only. The palette, fonts, grid and orbs all live in
   x-layouts.taab - this block used to redeclare --bg/--surface/--muted with
   slightly different values than the layout, which is drift waiting to happen.
   It also drew its own noise texture and glow orbs on top of the layout's. --}}
body { overflow-x: hidden; }

.page-wrap { position: relative; z-index: 1; max-width: 1100px; margin: 0 auto; padding: 0 1.5rem; }

.site-nav { position: sticky; top: 0; z-index: 50; width: 100%; background: rgba(9,9,11,0.80); backdrop-filter: blur(12px); border-bottom: 1px solid #18181b; }
.site-nav-inner { max-width: 1100px; margin: 0 auto; padding: 0.9rem 1.5rem; display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
.nav-logo { font-family: var(--font-display); font-size: 1.2rem; font-weight: 700; font-style: italic; text-transform: uppercase; letter-spacing: -0.045em; color: #fff; white-space: nowrap; }
.nav-logo span { color: var(--accent); }
.nav-right { display: flex; align-items: center; gap: 1.25rem; }
.nav-by { font-family: var(--font-mono); font-size: 10px; text-transform: uppercase; letter-spacing: 0.14em; color: var(--muted); white-space: nowrap; }
.nav-cta { font-family: var(--font-display); font-size: 10px; font-weight: 700; letter-spacing: 0.16em; text-transform: uppercase; color: #000; background: #fff; padding: 9px 20px; border-radius: 6px; text-decoration: none; transition: background 0.2s; white-space: nowrap; }
.nav-cta:hover { background: var(--accent); }

.hero { padding: 5rem 0 3.5rem; display: grid; grid-template-columns: 1fr 420px; gap: 4rem; align-items: center; }
@media (max-width: 880px) { .hero { grid-template-columns: 1fr; gap: 2.5rem; padding: 3.5rem 0 2.5rem; } }
.hero-eyebrow { display: inline-flex; align-items: center; gap: 10px; font-family: var(--font-display); font-size: 10px; font-weight: 700; letter-spacing: 0.2em; text-transform: uppercase; color: var(--accent-bright); background: rgba(8,51,68,0.3); border: 1px solid var(--accent-border); padding: 6px 15px; border-radius: 9999px; margin-bottom: 1.75rem; animation: badge-pulse 2s infinite; }
.hero-eyebrow::before { content: ''; width: 8px; height: 8px; border-radius: 9999px; background: var(--accent); box-shadow: 0 0 10px #06b6d4; animation: blink 2s ease-in-out infinite; }
@keyframes blink { 0%,100% { opacity: 1; } 50% { opacity: 0.35; } }
@keyframes badge-pulse {
  0%   { border-color: rgba(6,182,212,0.2); box-shadow: 0 0 0 0 rgba(6,182,212,0.2); }
  50%  { border-color: rgba(6,182,212,0.6); box-shadow: 0 0 20px 0 rgba(6,182,212,0.1); }
  100% { border-color: rgba(6,182,212,0.2); box-shadow: 0 0 0 0 rgba(6,182,212,0.2); }
}
{{-- The gradient wordmark, the italic uppercase display weight and the mono
   eyebrow / meta lines are the Accelerator hero's signature. Keep them in step. --}}
.hero-wordmark { font-family: var(--font-display); font-size: clamp(4.5rem, 13vw, 8rem); font-weight: 700; font-style: italic; text-transform: uppercase; line-height: 0.88; letter-spacing: -0.055em; margin-bottom: 0.9rem; background: linear-gradient(to right, #22d3ee, #3b82f6, #22d3ee); -webkit-background-clip: text; background-clip: text; color: transparent; animation: fadeUp 0.6s 0.1s ease both; }
.hero-title { font-family: var(--font-mono); font-size: 11px; font-weight: 500; letter-spacing: 0.22em; text-transform: uppercase; color: var(--muted); margin-bottom: 1.5rem; animation: fadeUp 0.6s 0.15s ease both; }
.hero-tagline { font-family: var(--font-display); font-size: clamp(1.5rem, 3.6vw, 2.1rem); font-weight: 700; color: #fff; line-height: 1.18; letter-spacing: -0.02em; margin-bottom: 1.75rem; animation: fadeUp 0.6s 0.2s ease both; }
.hero-tagline em { font-style: normal; border-bottom: 2px solid var(--accent); }
.hero-meta { display: flex; flex-wrap: wrap; gap: 0.6rem; margin-bottom: 2rem; animation: fadeUp 0.6s 0.25s ease both; }
.meta-chip { display: flex; align-items: center; gap: 8px; font-family: var(--font-mono); font-size: 11px; letter-spacing: 0.04em; color: #a1a1aa; padding: 7px 13px; border: 1px solid #27272a; border-radius: 6px; background: rgba(24,24,27,0.6); }
.meta-chip svg { opacity: 0.7; color: var(--accent); }
.hero-proof { font-size: 13px; color: var(--muted); line-height: 1.7; animation: fadeUp 0.6s 0.3s ease both; }
.hero-proof strong { color: var(--accent-bright); font-weight: 500; }

{{-- -- Readiness strip -------------------------------------------------
   The Accelerator hero carries a schematic of the workflow architecture
   students build. TAAB deliberately does NOT borrow that one: this is a
   clarity session, not a build session, and a "come build bots" promise
   draws the wrong room (CLAUDE.md section 2). So the same visual language -
   nodes, wires, a packet travelling left to right - carries TAAB's real
   promise instead: five dimensions in, one verdict out.

   The five are the scorecard's actual dimensions, so this diagram and
   /taab/scorecard cannot drift apart. Drawn in CSS: no images, no network,
   and it reads correctly with motion off. --}}
.readiness { padding: 0.5rem 0 3.5rem; }
.readiness-caption { font-family: var(--font-mono); font-size: 10px; text-transform: uppercase; letter-spacing: 0.18em; color: var(--faint); text-align: center; margin-bottom: 1.5rem; }
.readiness-strip { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.6rem; }
@media (min-width: 880px) { .readiness-strip { display: flex; align-items: stretch; justify-content: center; gap: 0; } }
.r-node, .r-verdict { background: rgba(24,24,27,0.55); border: 1px solid #27272a; border-radius: 10px; padding: 0.85rem 0.6rem; text-align: center; }
@media (min-width: 880px) { .r-node, .r-verdict { flex: 0 0 auto; min-width: 104px; } }
.r-node { animation: node-fire 5.4s ease-in-out infinite; animation-delay: calc(var(--i) * 0.9s); }
@keyframes node-fire {
  0%, 100% { border-color: #27272a; box-shadow: none; }
  8%  { border-color: rgba(6,182,212,0.55); box-shadow: 0 0 24px 0 rgba(6,182,212,0.16); }
  24% { border-color: #27272a; box-shadow: none; }
}
.r-verdict { border-color: var(--accent-border); background: rgba(8,51,68,0.3); }
{{-- Five nodes in a 2-up grid would leave the last one next to a hole, so it and
   the verdict each take the full row. Both revert to flex items above 880px. --}}
.r-node-last, .r-verdict { grid-column: span 2; }
@media (min-width: 880px) { .r-node-last, .r-verdict { grid-column: auto; } }
.r-kicker { font-family: var(--font-mono); font-size: 9px; letter-spacing: 0.16em; text-transform: uppercase; color: var(--faint); margin-bottom: 0.3rem; }
.r-verdict .r-kicker { color: var(--accent); }
.r-label { font-family: var(--font-display); font-size: 12px; font-weight: 700; letter-spacing: 0.02em; color: #e4e4e7; text-transform: uppercase; }
.r-verdict .r-label { color: var(--accent-bright); }
.r-wire { display: none; }
@media (min-width: 880px) { .r-wire { display: block; position: relative; align-self: center; height: 2px; flex: 1 1 1.5rem; max-width: 2.75rem; background: #27272a; } }
.r-packet { position: absolute; top: 50%; left: 0; width: 6px; height: 6px; margin-top: -3px; border-radius: 9999px; background: var(--accent); box-shadow: 0 0 10px #06b6d4; opacity: 0; animation: flow-travel 5.4s linear infinite; animation-delay: var(--d, 0s); }
@keyframes flow-travel {
  0%   { left: 0; opacity: 0; }
  5%   { opacity: 1; }
  16%  { left: 100%; opacity: 1; }
  22%  { opacity: 0; }
  100% { left: 100%; opacity: 0; }
}
@media (prefers-reduced-motion: reduce) {
  .hero-eyebrow, .hero-eyebrow::before, .r-node, .r-packet,
  .hero-wordmark, .hero-title, .hero-tagline, .hero-meta, .hero-proof, .form-card { animation: none; }
  .r-packet { opacity: 1; left: 50%; }
  .reveal { opacity: 1; transform: none; transition: none; }
}

.form-card { background: var(--surface); border: 1px solid var(--border); border-radius: 20px; padding: 2rem; animation: fadeUp 0.6s 0.2s ease both; position: relative; overflow: hidden; }
.form-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; background: var(--accent); }
.form-card-header { margin-bottom: 1.5rem; }
.form-card-label { font-family: var(--font-display); font-size: 10px; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; color: var(--accent); margin-bottom: 0.4rem; }
.form-card-title { font-family: var(--font-display); font-size: 1.25rem; font-weight: 700; color: var(--text); line-height: 1.3; }
.form-card-sub { font-size: 12px; color: var(--muted); margin-top: 0.3rem; font-weight: 300; }
.form-group { margin-bottom: 1rem; }
.form-label { display: block; font-size: 12px; font-weight: 500; color: var(--muted); margin-bottom: 6px; font-family: var(--font-display); letter-spacing: 0.03em; }
.form-input, .form-select { width: 100%; background: var(--bg); border: 1px solid var(--faint); border-radius: 8px; padding: 12px 14px; font-family: var(--font-body); font-size: 14px; color: var(--text); outline: none; transition: border-color 0.2s; -webkit-appearance: none; }
.form-input::placeholder { color: #52525b; }
.form-input:focus, .form-select:focus { border-color: rgba(6,182,212,0.4); }
.form-select { cursor: pointer; }
.form-select option { background: #18181b; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; }
@media (max-width: 480px) { .form-row { grid-template-columns: 1fr; } }
.form-submit { width: 100%; background: var(--accent); color: var(--bg); border: none; border-radius: 10px; padding: 14px; font-family: var(--font-display); font-size: 14px; font-weight: 800; letter-spacing: 0.04em; text-transform: uppercase; cursor: pointer; transition: all 0.2s; margin-top: 0.5rem; }
.form-submit:hover { opacity: 0.9; transform: translateY(-1px); }
.form-submit:active { transform: translateY(0); }
.form-submit:disabled { opacity: 0.6; cursor: not-allowed; }
.form-disclaimer { font-size: 11px; color: var(--muted); font-weight: 300; text-align: center; margin-top: 0.75rem; line-height: 1.5; }
.form-success { display: none; text-align: center; padding: 2rem 1rem; }
.form-success .success-icon { font-size: 3rem; margin-bottom: 1rem; }
.form-success h3 { font-family: var(--font-display); font-size: 1.2rem; font-weight: 700; color: var(--accent); margin-bottom: 0.5rem; }
.form-success p { font-size: 13px; color: var(--muted); line-height: 1.6; }

.section-divider { border: none; border-top: 1px solid var(--border); margin: 5rem 0; }
.section-eyebrow { font-family: var(--font-display); font-size: 10px; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase; color: var(--muted); margin-bottom: 0.5rem; }
.section-title { font-family: var(--font-display); font-size: clamp(1.6rem, 3.5vw, 2.4rem); font-weight: 800; color: var(--text); line-height: 1.15; letter-spacing: -0.02em; margin-bottom: 3rem; }
.section-title em { color: var(--accent); font-style: normal; }

.sessions-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(min(100%, 280px), 1fr)); gap: 1px; background: var(--faint); border: 1px solid var(--faint); border-radius: var(--radius); overflow: hidden; margin-bottom: 5rem; }
.session-card { background: var(--surface); padding: 1.75rem; transition: background 0.2s; }
.session-card:hover { background: var(--surface2); }
.session-num { font-family: var(--font-display); font-size: 11px; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; color: var(--accent); margin-bottom: 0.75rem; }
.session-title { font-family: var(--font-display); font-size: 1rem; font-weight: 700; color: var(--text); margin-bottom: 0.5rem; line-height: 1.3; }
.session-desc { font-size: 13px; color: var(--muted); font-weight: 300; line-height: 1.6; }

.takeaways-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 5rem; }
@media (max-width: 700px) { .takeaways-grid { grid-template-columns: 1fr; } }
.takeaway-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); padding: 1.5rem; position: relative; overflow: hidden; display: block; text-decoration: none; color: inherit; }
.takeaway-card::after { content: ''; position: absolute; bottom: 0; left: 0; right: 0; height: 2px; background: var(--accent); transform: scaleX(0); transform-origin: left; transition: transform 0.3s; }
.takeaway-card:hover::after { transform: scaleX(1); }
.takeaway-num { font-family: var(--font-display); font-size: 2.2rem; font-weight: 800; color: var(--accent); opacity: 0.25; line-height: 1; margin-bottom: 1rem; }
.takeaway-title { font-family: var(--font-display); font-size: 0.95rem; font-weight: 700; color: var(--text); margin-bottom: 0.5rem; }
.takeaway-desc { font-size: 12.5px; color: var(--muted); font-weight: 300; line-height: 1.6; }
.takeaway-open { font-family: var(--font-display); font-size: 11px; font-weight: 700; letter-spacing: 0.04em; color: var(--accent); margin-top: 0.85rem; }

.for-who { background: var(--surface); border: 1px solid var(--border); border-radius: 20px; padding: 3rem; margin-bottom: 5rem; display: grid; grid-template-columns: 1fr 1fr; gap: 3rem; }
@media (max-width: 700px) { .for-who { grid-template-columns: 1fr; gap: 2rem; padding: 2rem; } }
.for-who-col-title { font-family: var(--font-display); font-size: 11px; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; margin-bottom: 1.25rem; }
.for-who-col-title.yes { color: var(--accent); }
.for-who-col-title.no { color: var(--red); }
.for-item { display: flex; align-items: flex-start; gap: 10px; font-size: 13.5px; font-weight: 300; color: var(--text); margin-bottom: 0.85rem; line-height: 1.5; }
.for-item-icon { font-size: 14px; margin-top: 1px; flex-shrink: 0; }

.facilitator { display: flex; gap: 2rem; align-items: flex-start; margin-bottom: 5rem; }
@media (max-width: 600px) { .facilitator { flex-direction: column; gap: 1.5rem; } }
.facilitator-avatar { width: 80px; height: 80px; min-width: 80px; border-radius: 50%; background: var(--accent-dim); border: 2px solid var(--accent-border); display: flex; align-items: center; justify-content: center; font-family: var(--font-display); font-size: 1.6rem; font-weight: 800; color: var(--accent); }
.facilitator-label { font-family: var(--font-display); font-size: 10px; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; color: var(--muted); margin-bottom: 0.35rem; }
.facilitator-name { font-family: var(--font-display); font-size: 1.2rem; font-weight: 800; color: var(--text); margin-bottom: 0.5rem; }
.facilitator-bio { font-size: 14px; color: var(--muted); font-weight: 300; line-height: 1.7; max-width: 560px; }

.bottom-cta { background: var(--accent); border-radius: 20px; padding: 4rem 3rem; text-align: center; margin-bottom: 4rem; position: relative; overflow: hidden; }
.bottom-cta::before { content: 'TAAB'; position: absolute; font-family: var(--font-display); font-size: 14rem; font-weight: 800; color: rgba(0,0,0,0.06); top: 50%; left: 50%; transform: translate(-50%, -50%); pointer-events: none; white-space: nowrap; letter-spacing: -0.04em; }
.bottom-cta-date { font-family: var(--font-display); font-size: 11px; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase; color: rgba(0,0,0,0.5); margin-bottom: 0.75rem; }
.bottom-cta-title { font-family: var(--font-display); font-size: clamp(1.4rem, 4vw, 2.2rem); font-weight: 800; color: var(--bg); margin-bottom: 0.5rem; line-height: 1.2; position: relative; }
.bottom-cta-sub { font-size: 15px; color: rgba(0,0,0,0.55); margin-bottom: 2rem; font-weight: 300; }
.bottom-cta-btn { display: inline-block; background: var(--bg); color: var(--accent); font-family: var(--font-display); font-size: 13px; font-weight: 800; letter-spacing: 0.06em; text-transform: uppercase; padding: 14px 36px; border-radius: 100px; text-decoration: none; transition: transform 0.2s, opacity 0.2s; }
.bottom-cta-btn:hover { transform: translateY(-2px); opacity: 0.9; }

footer { text-align: center; padding: 2rem 0 3rem; font-size: 12px; color: var(--muted); border-top: 1px solid var(--border); font-weight: 300; }
footer a { color: var(--muted); text-decoration: none; }
footer a:hover { color: var(--accent); }

@keyframes fadeUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
.reveal { opacity: 0; transform: translateY(24px); transition: opacity 0.6s ease, transform 0.6s ease; }
.reveal.visible { opacity: 1; transform: translateY(0); }

/* ── Mobile polish ─────────────────────────────────────────────── */
@media (max-width: 640px) {
  .page-wrap { padding: 0 1.15rem; }

  /* Header + CTA: tighter, comfortably tappable, no crowding */
  .site-nav-inner { padding: 0.8rem 1.15rem; }
  .nav-cta { padding: 9px 16px; letter-spacing: 0.04em; }

  /* Hero */
  .hero { padding: 2.75rem 0 2.5rem; gap: 2.5rem; }
  .hero-eyebrow { margin-bottom: 1.25rem; }
  .hero-tagline { margin-bottom: 1.5rem; }
  .hero-meta { gap: 0.6rem; margin-bottom: 1.75rem; }
  .form-card { padding: 1.5rem; }

  /* Tighter vertical rhythm between sections */
  .section-divider { margin: 3rem 0; }
  .section-title { margin-bottom: 1.75rem; }
  .sessions-grid, .takeaways-grid, .for-who, .facilitator { margin-bottom: 3rem; }

  /* Cards */
  .session-card { padding: 1.4rem; }
  .takeaway-card { padding: 1.35rem; }

  /* Who it's for */
  .for-who { padding: 1.5rem; gap: 1.75rem; }

  /* Bottom CTA */
  .bottom-cta { padding: 2.5rem 1.5rem; margin-bottom: 2.5rem; }
  .bottom-cta::before { font-size: 8rem; }
  .bottom-cta-sub { font-size: 14px; margin-bottom: 1.5rem; }

  footer { padding: 1.5rem 0 2.5rem; }
}

/* Very small screens: keep the header on one tidy line */
@media (max-width: 400px) {
  .nav-by { display: none; }
}
</style>
@endpush

{{-- The nav sits OUTSIDE .page-wrap on purpose: a sticky bar inside a
     max-width padded wrapper cannot span the viewport, and the Accelerator's
     header spans it. .site-nav-inner re-applies the same max-width. --}}
<nav class="site-nav">
  <div class="site-nav-inner">
    {{-- Brand is AJBuilds AI: this page sends people to ajbuildai.com/accelerator, so the
         nav, the footer and the enrolment destination all have to be the same name. --}}
    <div class="nav-logo">TAAB.<span>BOOTCAMP</span></div>
    <div class="nav-right">
      <span class="nav-by">by AJBuilds AI</span>
      <a href="#register" class="nav-cta">Reserve my spot</a>
    </div>
  </div>
</nav>

<div class="page-wrap">

  <!-- Hero -->
  <section class="hero">
    <div class="hero-left">
      <div class="hero-eyebrow">Free 2-hour live session</div>
      <div class="hero-wordmark">TAAB</div>
      {{-- The three facts below live in the meta chips, not here - don't repeat
           them in this line or the hero says the date twice. --}}
      <div class="hero-title">The AI Automation Bootcamp</div>
      <div class="hero-tagline">Clarity <em>before</em> you commit.<br>Everything you need to decide.</div>

      <div class="hero-meta">
        <div class="meta-chip">
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
          {{ $dateLong }}
        </div>
        <div class="meta-chip">
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
          {{ $mc['time'] }}
        </div>
        <div class="meta-chip">
          <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
          {{ $mc['location'] }}
        </div>
      </div>

      <div class="hero-proof">
        Hosted by <strong>{{ $mc['host'] }}</strong> - AI Automation Engineer, 7+ years building real client systems. Founder of Repetigo.
      </div>
    </div>

    <!-- Registration form -->
    <div id="register">
      @if(\App\Support\Masterclass::registrationOpen())
      <div class="form-card">
        <div class="form-card-header">
          <div class="form-card-label">Secure your seat</div>
          <div class="form-card-title">Join the bootcamp</div>
          <div class="form-card-sub">{{ $dateLong }} · Limited spots available</div>
        </div>

        <form id="reg-form">
          <div class="form-row">
            <div class="form-group">
              <label class="form-label" for="fname">First name</label>
              <input class="form-input" type="text" id="fname" name="first_name" placeholder="Adebayo" value="{{ $prefill['first_name'] ?? '' }}" required>
            </div>
            <div class="form-group">
              <label class="form-label" for="lname">Last name</label>
              <input class="form-input" type="text" id="lname" name="last_name" placeholder="Okafor" value="{{ $prefill['last_name'] ?? '' }}" required>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label" for="email">Email address</label>
            <input class="form-input" type="email" id="email" name="email" placeholder="you@email.com" value="{{ $prefill['email'] ?? '' }}" required>
          </div>

          <div class="form-group">
            <label class="form-label" for="whatsapp">WhatsApp number</label>
            <input class="form-input" type="tel" id="whatsapp" name="whatsapp" placeholder="+234 800 000 0000" value="{{ $prefill['whatsapp'] ?? '' }}">
          </div>

          <div class="form-group">
            <label class="form-label" for="background">Your current background</label>
            @php $bg = $prefill['background'] ?? ''; @endphp
            <select class="form-select" id="background" name="background" required>
              <option value="" disabled @selected($bg === '')>Select one</option>
              @foreach([
                'Complete beginner - no tech background',
                'Developer / technical background',
                'Business owner / entrepreneur',
                'Freelancer / consultant',
                'Student',
                'Other',
              ] as $opt)
              <option @selected($bg === $opt)>{{ $opt }}</option>
              @endforeach
            </select>
          </div>

          <div class="form-group">
            <label class="form-label" for="goal">What's your #1 goal for attending?</label>
            <select class="form-select" id="goal" name="goal" required>
              <option value="" disabled selected>Select one</option>
              <option>Find out if AI automation is right for me</option>
              <option>Understand what tools and skills I need</option>
              <option>Know how much it costs to get started</option>
              <option>Find out how long before I see income</option>
              <option>All of the above</option>
            </select>
          </div>

          <button type="submit" class="form-submit">Reserve my seat →</button>
          <div class="form-disclaimer">Free to attend. No spam. Google Meet link sent to your email 24hrs before.</div>
        </form>

        <div class="form-success" id="form-success">
          <div class="success-icon">🎯</div>
          <h3>You're in. See you {{ $mcDate ? $mcDate->translatedFormat('l') : 'soon' }}.</h3>
          <p>Check your inbox for a confirmation email. Google Meet link arrives 24hrs before the event. We'll also send the link to join the attendee WhatsApp group.</p>
        </div>
      </div>
      @else
      <div class="form-card">
        <div class="form-card-header">
          <div class="form-card-label">Next cohort</div>
          <div class="form-card-title">Registration is closed</div>
          <div class="form-card-sub">The {{ $dateLong }} session has closed. Leave your details and you'll be first to know when the next date opens.</div>
        </div>

        <form id="wait-form">
          <div class="form-group">
            <label class="form-label" for="w-name">Full name</label>
            <input class="form-input" type="text" id="w-name" name="name" placeholder="Adebayo Okafor" required>
          </div>
          <div class="form-group">
            <label class="form-label" for="w-email">Email address</label>
            <input class="form-input" type="email" id="w-email" name="email" placeholder="you@email.com" required>
          </div>
          <div class="form-group">
            <label class="form-label" for="w-whatsapp">WhatsApp number</label>
            <input class="form-input" type="tel" id="w-whatsapp" name="whatsapp" placeholder="+234 800 000 0000">
          </div>
          <button type="submit" class="form-submit">Notify me about the next session →</button>
          <div class="form-disclaimer">No spam. Just a heads-up when the next bootcamp date is announced.</div>
        </form>

        <div class="form-success" id="wait-success">
          <div class="success-icon">✅</div>
          <h3>You're on the list.</h3>
          <p>We'll email you the moment the next TAAB session is scheduled.</p>
        </div>
      </div>
      @endif
    </div>
  </section>

  {{-- Five dimensions in, one verdict out. The labels below are the scorecard's
       real dimensions - if you add or rename one in taab/scorecard.blade.php,
       change it here too. TaabBrandTest asserts they match. --}}
  @php
      $dimensions = [
          ['01', 'Skills'],
          ['02', 'Time'],
          ['03', 'Setup'],
          ['04', 'Mindset'],
          ['05', 'Market'],
      ];
  @endphp
  <div class="readiness reveal">
    <div class="readiness-caption">What two hours actually resolves</div>
    <div class="readiness-strip">
      @foreach($dimensions as $i => $dim)
        <div class="r-node @if($loop->last) r-node-last @endif" style="--i: {{ $i }}">
          <div class="r-kicker">{{ $dim[0] }}</div>
          <div class="r-label">{{ $dim[1] }}</div>
        </div>
        <div class="r-wire"><span class="r-packet" style="--d: {{ $i * 0.22 }}s"></span></div>
      @endforeach
      <div class="r-verdict">
        <div class="r-kicker">Outcome</div>
        <div class="r-label">Your verdict</div>
      </div>
    </div>
  </div>

  <hr class="section-divider">

  <!-- Sessions -->
  <div class="reveal">
    <div class="section-eyebrow">The agenda</div>
    <div class="section-title">5 sessions. <em>One clear answer.</em></div>
  </div>

  <div class="sessions-grid reveal">
    <div class="session-card"><div class="session-num">Session 01</div><div class="session-title">What AI automation actually is</div><div class="session-desc">Not just ChatGPT. Not just Zapier. A real definition, the 3 business models, and a live workflow running on screen.</div></div>
    <div class="session-card"><div class="session-num">Session 02</div><div class="session-title">Entry requirements & the tool landscape</div><div class="session-desc">The skill spectrum from no-code to developer. The tool map - n8n, Make, Zapier. An honest look at the learning curve.</div></div>
    <div class="session-card"><div class="session-num">Session 03</div><div class="session-title">The cost of entry & ROI reality</div><div class="session-desc">Real monthly numbers. How long to first client. Pricing frameworks. Your personalised break-even calculation - live.</div></div>
    <div class="session-card"><div class="session-num">Session 04</div><div class="session-title">Opportunities & where to play</div><div class="session-desc">The 5 highest-demand use cases. Industries spending now. The Nigerian SME market. Challenges you will face - honestly.</div></div>
    <div class="session-card"><div class="session-num">Session 05</div><div class="session-title">Readiness assessment + open Q&A</div><div class="session-desc">A live scorecard across 5 dimensions. Your outcome bucket. And an open floor - no agenda, attendees drive it.</div></div>
    <div class="session-card" style="background: var(--accent-dim); border-color: var(--accent-border);"><div class="session-num" style="color: var(--accent);">Take away</div><div class="session-title">3 tools you keep forever</div><div class="session-desc" style="color: rgba(34,211,238,0.75);">Readiness Scorecard · ROI Calculator · Tool Stack Guide. Use them live to get clarity - yours to keep after.</div></div>
  </div>

  <!-- Takeaways -->
  <div class="reveal">
    <div class="section-eyebrow">What you walk away with</div>
    <div class="section-title">Not slides. <em>Actual tools.</em></div>
  </div>

  <div class="takeaways-grid reveal">
    <a class="takeaway-card" href="{{ route('taab.scorecard') }}">
      <div class="takeaway-num">01</div>
      <div class="takeaway-title">Readiness Scorecard</div>
      <div class="takeaway-desc">10 questions across 5 dimensions. A personal score out of 100. A verdict and a specific action plan based on where you land.</div>
      <div class="takeaway-open">Try it now →</div>
    </a>
    <a class="takeaway-card" href="{{ route('taab.roi') }}">
      <div class="takeaway-num">02</div>
      <div class="takeaway-title">ROI Calculator</div>
      <div class="takeaway-desc">Your real numbers - tool costs, learning investment, break-even month, 12-month revenue projection. Filled in live during Session 3.</div>
      <div class="takeaway-open">Try it now →</div>
    </a>
    <a class="takeaway-card" href="{{ route('taab.tools') }}">
      <div class="takeaway-num">03</div>
      <div class="takeaway-title">Tool Stack Guide</div>
      <div class="takeaway-desc">20 tools across 3 levels - beginner, intermediate, advanced - with honest costs and 4 recommended stacks for real client scenarios.</div>
      <div class="takeaway-open">Try it now →</div>
    </a>
  </div>

  <!-- For who -->
  <div class="reveal">
    <div class="section-eyebrow">Who it's for</div>
    <div class="section-title">The right room <em>matters.</em></div>
  </div>

  <div class="for-who reveal">
    <div>
      <div class="for-who-col-title yes">✓ &nbsp;This bootcamp is for you if…</div>
      <div class="for-item"><span class="for-item-icon">✓</span> You've heard about AI automation and genuinely want to know if it's worth pursuing</div>
      <div class="for-item"><span class="for-item-icon">✓</span> You want to understand the real costs before spending money on tools and courses</div>
      <div class="for-item"><span class="for-item-icon">✓</span> You want to know if your skills, time, and budget are enough to get started</div>
      <div class="for-item"><span class="for-item-icon">✓</span> You want to see what real client automation work looks like - not a tutorial, a real system</div>
      <div class="for-item"><span class="for-item-icon">✓</span> You're a developer, freelancer, entrepreneur, or student in Nigeria or anywhere in Africa</div>
    </div>
    <div>
      <div class="for-who-col-title no">✗ &nbsp;This bootcamp is NOT for you if…</div>
      <div class="for-item"><span class="for-item-icon" style="color:var(--red)">✗</span> You're already earning from AI automation and want advanced technical training</div>
      <div class="for-item"><span class="for-item-icon" style="color:var(--red)">✗</span><span>You want a step-by-step "build this exact workflow" tutorial (that's what the <a href="{{ config('taab.accelerator_url') }}" style="color:var(--accent);text-decoration:none">Accelerator</a> is for)</span></div>
      <div class="for-item"><span class="for-item-icon" style="color:var(--red)">✗</span> You're looking for a get-rich-quick shortcut - this bootcamp is about honest clarity, not hype</div>
      <div class="for-item"><span class="for-item-icon" style="color:var(--red)">✗</span> You can't commit to the full 2 hours (the live sessions build on each other)</div>
    </div>
  </div>

  <!-- Facilitator -->
  <div class="reveal">
    <div class="section-eyebrow">Your facilitator</div>
    <div class="section-title">Who's running the room</div>
  </div>

  <div class="facilitator reveal">
    <div class="facilitator-avatar">AJ</div>
    <div>
      <div class="facilitator-label">Facilitator</div>
      <div class="facilitator-name">{{ $mc['host'] }}</div>
      <div class="facilitator-bio">
        AI Automation Engineer and Backend Developer based in Abuja, Nigeria, with 7+ years building production systems. Founder of Repetigo - a B2B AI automation consultancy - and creator of the AI Automation Accelerator cohort programme. AJ has built live client systems including WhatsApp lead qualifiers, booking automation workflows, and AI sales chatbots. He runs this bootcamp because he's watched too many people jump in blind and burn out. This is the day he wishes had existed when he started.
      </div>
    </div>
  </div>

  <!-- Bottom CTA -->
  <div class="bottom-cta reveal">
    <div class="bottom-cta-date">{{ $dateLong }} · {{ $mc['time'] }}</div>
    <div class="bottom-cta-title">Two hours. One decision. Finally made.</div>
    <div class="bottom-cta-sub">Free to attend. Limited spots.@if($closes) Registration closes {{ $closes }}.@endif</div>
    <a href="#register" class="bottom-cta-btn">Reserve your seat now</a>
  </div>

  <footer>
    <p>© {{ date('Y') }} AJBuilds AI · <a href="https://ajbuildai.com">ajbuildai.com</a> · Questions? Reach out on WhatsApp or email hello@ajbuildai.com</p>
  </footer>

</div>

@push('scripts')
<script>
// Scroll reveal
const observer = new IntersectionObserver(entries => {
  entries.forEach((entry, i) => {
    if (entry.isIntersecting) {
      setTimeout(() => entry.target.classList.add('visible'), i * 80);
      observer.unobserve(entry.target);
    }
  });
}, { threshold: 0.08 });
document.querySelectorAll('.reveal').forEach(el => observer.observe(el));

// Registration form → /taab/register
const form = document.getElementById('reg-form');
const success = document.getElementById('form-success');
if (form) {
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = form.querySelector('.form-submit');
    btn.textContent = 'Sending…'; btn.disabled = true;

    const payload = {
      first_name: form.first_name.value.trim(),
      last_name: form.last_name.value.trim(),
      email: form.email.value.trim(),
      whatsapp: form.whatsapp.value.trim(),
      background: form.background.value,
      goal: form.goal.value,
    };

    try {
      const res = await fetch('{{ route('taab.register') }}', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify(payload),
      });
      if (res.ok) {
        form.style.display = 'none';
        success.style.display = 'block';
        if (typeof fbq === 'function') fbq('track', 'Lead', { content_name: 'taab_masterclass' });
      } else {
        btn.textContent = 'Check your details - try again';
        btn.disabled = false;
      }
    } catch {
      btn.textContent = 'Network error - please try again';
      btn.disabled = false;
    }
  });
}

// Waitlist form (shown when registration is closed) → /taab/waitlist
const waitForm = document.getElementById('wait-form');
if (waitForm) {
  const waitSuccess = document.getElementById('wait-success');
  waitForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = waitForm.querySelector('.form-submit');
    btn.textContent = 'Sending…'; btn.disabled = true;

    const payload = {
      name: document.getElementById('w-name').value.trim(),
      email: document.getElementById('w-email').value.trim(),
      whatsapp: document.getElementById('w-whatsapp').value.trim(),
    };

    try {
      const res = await fetch('{{ route('taab.waitlist') }}', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify(payload),
      });
      if (res.ok) {
        waitForm.style.display = 'none';
        waitSuccess.style.display = 'block';
        if (typeof fbq === 'function') fbq('track', 'Lead', { content_name: 'taab_waitlist' });
      } else {
        btn.textContent = 'Check your details - try again';
        btn.disabled = false;
      }
    } catch {
      btn.textContent = 'Network error - please try again';
      btn.disabled = false;
    }
  });
}

// Smooth scroll to the form
document.querySelectorAll('a[href="#register"]').forEach(link => {
  link.addEventListener('click', e => {
    e.preventDefault();
    document.getElementById('register').scrollIntoView({ behavior: 'smooth', block: 'center' });
  });
});
</script>
@endpush
</x-layouts.taab>
