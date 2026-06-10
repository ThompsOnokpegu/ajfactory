@php
    $mc = config('taab.masterclass');
    $mcDate = !empty($mc['date']) ? \Illuminate\Support\Carbon::parse($mc['date']) : null;
    $dateLong = $mcDate ? $mcDate->translatedFormat('l j F') : 'Date to be announced';
    $closes = !empty($mc['registration_closes']) ? \Illuminate\Support\Carbon::parse($mc['registration_closes'])->translatedFormat('l j F') : null;
@endphp
<x-layouts.taab
    title="The AI Automation Bootcamp — Register | TAAB"
    description="A one-day live bootcamp to get clarity before you commit to AI automation. {{ $dateLong }}, on Zoom. Free to attend.">

@push('styles')
<style>
:root {
  --bg: #0a0a10;
  --surface: #111118;
  --surface2: #18181f;
  --border: rgba(255,255,255,0.07);
  --lime: #c8f064;
  --lime-dim: rgba(200,240,100,0.1);
  --lime-border: rgba(200,240,100,0.25);
  --text: #f0ede8;
  --muted: #888096;
  --faint: #3a3848;
  --purple: #7c5cfc;
  --radius: 12px;
}
body { font-family: 'DM Sans', sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; overflow-x: hidden; }
body::before {
  content: ''; position: fixed; inset: 0;
  background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 512 512' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.75' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.04'/%3E%3C/svg%3E");
  pointer-events: none; z-index: 0; opacity: 0.6;
}
.bg-glow { position: fixed; inset: 0; pointer-events: none; z-index: 0; overflow: hidden; }
.bg-glow::before { content: ''; position: absolute; width: 800px; height: 800px; border-radius: 50%; background: radial-gradient(circle, rgba(200,240,100,0.04) 0%, transparent 70%); top: -200px; left: -200px; animation: drift1 20s ease-in-out infinite alternate; }
.bg-glow::after { content: ''; position: absolute; width: 600px; height: 600px; border-radius: 50%; background: radial-gradient(circle, rgba(124,92,252,0.05) 0%, transparent 70%); bottom: -100px; right: -100px; animation: drift2 25s ease-in-out infinite alternate; }
@keyframes drift1 { to { transform: translate(120px, 80px); } }
@keyframes drift2 { to { transform: translate(-80px, -120px); } }

.page-wrap { position: relative; z-index: 1; max-width: 1100px; margin: 0 auto; padding: 0 1.5rem; }

nav { display: flex; align-items: center; justify-content: space-between; padding: 1.5rem 0; border-bottom: 1px solid var(--border); }
.nav-logo { font-family: 'Syne', sans-serif; font-size: 1.1rem; font-weight: 800; color: var(--lime); letter-spacing: -0.01em; }
.nav-logo span { color: var(--muted); font-weight: 400; }
.nav-cta { font-family: 'Syne', sans-serif; font-size: 12px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: var(--bg); background: var(--lime); padding: 8px 20px; border-radius: 100px; text-decoration: none; transition: opacity 0.15s; }
.nav-cta:hover { opacity: 0.88; }

.hero { padding: 6rem 0 4rem; display: grid; grid-template-columns: 1fr 420px; gap: 5rem; align-items: center; }
@media (max-width: 820px) { .hero { grid-template-columns: 1fr; gap: 3rem; padding: 4rem 0 3rem; } }
.hero-eyebrow { display: inline-flex; align-items: center; gap: 8px; font-family: 'Syne', sans-serif; font-size: 11px; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; color: var(--lime); background: var(--lime-dim); border: 1px solid var(--lime-border); padding: 5px 14px; border-radius: 100px; margin-bottom: 1.75rem; animation: fadeUp 0.6s ease both; }
.hero-eyebrow::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: var(--lime); animation: blink 2s ease-in-out infinite; }
@keyframes blink { 0%,100%{opacity:1} 50%{opacity:0.3} }
.hero-wordmark { font-family: 'Syne', sans-serif; font-size: clamp(5rem, 14vw, 9rem); font-weight: 800; line-height: 0.9; letter-spacing: -0.04em; color: var(--lime); margin-bottom: 1rem; animation: fadeUp 0.6s 0.1s ease both; }
.hero-title { font-family: 'Syne', sans-serif; font-size: clamp(1.1rem, 2.5vw, 1.4rem); font-weight: 400; color: var(--muted); margin-bottom: 1.5rem; line-height: 1.5; animation: fadeUp 0.6s 0.15s ease both; }
.hero-tagline { font-family: 'Syne', sans-serif; font-size: clamp(1.4rem, 3.5vw, 2rem); font-weight: 700; color: var(--text); line-height: 1.25; margin-bottom: 2rem; animation: fadeUp 0.6s 0.2s ease both; }
.hero-meta { display: flex; flex-wrap: wrap; gap: 1rem; margin-bottom: 2.5rem; animation: fadeUp 0.6s 0.25s ease both; }
.meta-chip { display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--muted); padding: 7px 14px; border: 1px solid var(--border); border-radius: 100px; background: var(--surface); }
.meta-chip svg { opacity: 0.6; }
.hero-proof { font-size: 13px; color: var(--muted); font-weight: 300; animation: fadeUp 0.6s 0.3s ease both; }
.hero-proof strong { color: var(--lime); font-weight: 500; }

.form-card { background: var(--surface); border: 1px solid var(--border); border-radius: 20px; padding: 2rem; animation: fadeUp 0.6s 0.2s ease both; position: relative; overflow: hidden; }
.form-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; background: var(--lime); }
.form-card-header { margin-bottom: 1.5rem; }
.form-card-label { font-family: 'Syne', sans-serif; font-size: 10px; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; color: var(--lime); margin-bottom: 0.4rem; }
.form-card-title { font-family: 'Syne', sans-serif; font-size: 1.25rem; font-weight: 700; color: var(--text); line-height: 1.3; }
.form-card-sub { font-size: 12px; color: var(--muted); margin-top: 0.3rem; font-weight: 300; }
.form-group { margin-bottom: 1rem; }
.form-label { display: block; font-size: 12px; font-weight: 500; color: var(--muted); margin-bottom: 6px; font-family: 'Syne', sans-serif; letter-spacing: 0.03em; }
.form-input, .form-select { width: 100%; background: var(--bg); border: 1px solid var(--faint); border-radius: 8px; padding: 12px 14px; font-family: 'DM Sans', sans-serif; font-size: 14px; color: var(--text); outline: none; transition: border-color 0.2s; -webkit-appearance: none; }
.form-input::placeholder { color: #444355; }
.form-input:focus, .form-select:focus { border-color: rgba(200,240,100,0.4); }
.form-select { cursor: pointer; }
.form-select option { background: #111118; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; }
@media (max-width: 480px) { .form-row { grid-template-columns: 1fr; } }
.form-submit { width: 100%; background: var(--lime); color: var(--bg); border: none; border-radius: 10px; padding: 14px; font-family: 'Syne', sans-serif; font-size: 14px; font-weight: 800; letter-spacing: 0.04em; text-transform: uppercase; cursor: pointer; transition: all 0.2s; margin-top: 0.5rem; }
.form-submit:hover { opacity: 0.9; transform: translateY(-1px); }
.form-submit:active { transform: translateY(0); }
.form-submit:disabled { opacity: 0.6; cursor: not-allowed; }
.form-disclaimer { font-size: 11px; color: var(--muted); font-weight: 300; text-align: center; margin-top: 0.75rem; line-height: 1.5; }
.form-success { display: none; text-align: center; padding: 2rem 1rem; }
.form-success .success-icon { font-size: 3rem; margin-bottom: 1rem; }
.form-success h3 { font-family: 'Syne', sans-serif; font-size: 1.2rem; font-weight: 700; color: var(--lime); margin-bottom: 0.5rem; }
.form-success p { font-size: 13px; color: var(--muted); line-height: 1.6; }

.section-divider { border: none; border-top: 1px solid var(--border); margin: 5rem 0; }
.section-eyebrow { font-family: 'Syne', sans-serif; font-size: 10px; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase; color: var(--muted); margin-bottom: 0.5rem; }
.section-title { font-family: 'Syne', sans-serif; font-size: clamp(1.6rem, 3.5vw, 2.4rem); font-weight: 800; color: var(--text); line-height: 1.15; letter-spacing: -0.02em; margin-bottom: 3rem; }
.section-title em { color: var(--lime); font-style: normal; }

.sessions-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1px; background: var(--faint); border: 1px solid var(--faint); border-radius: var(--radius); overflow: hidden; margin-bottom: 5rem; }
.session-card { background: var(--surface); padding: 1.75rem; transition: background 0.2s; }
.session-card:hover { background: var(--surface2); }
.session-num { font-family: 'Syne', sans-serif; font-size: 11px; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; color: var(--lime); margin-bottom: 0.75rem; }
.session-title { font-family: 'Syne', sans-serif; font-size: 1rem; font-weight: 700; color: var(--text); margin-bottom: 0.5rem; line-height: 1.3; }
.session-desc { font-size: 13px; color: var(--muted); font-weight: 300; line-height: 1.6; }

.takeaways-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 5rem; }
@media (max-width: 700px) { .takeaways-grid { grid-template-columns: 1fr; } }
.takeaway-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); padding: 1.5rem; position: relative; overflow: hidden; display: block; text-decoration: none; color: inherit; }
.takeaway-card::after { content: ''; position: absolute; bottom: 0; left: 0; right: 0; height: 2px; background: var(--lime); transform: scaleX(0); transform-origin: left; transition: transform 0.3s; }
.takeaway-card:hover::after { transform: scaleX(1); }
.takeaway-num { font-family: 'Syne', sans-serif; font-size: 2.2rem; font-weight: 800; color: var(--lime); opacity: 0.25; line-height: 1; margin-bottom: 1rem; }
.takeaway-title { font-family: 'Syne', sans-serif; font-size: 0.95rem; font-weight: 700; color: var(--text); margin-bottom: 0.5rem; }
.takeaway-desc { font-size: 12.5px; color: var(--muted); font-weight: 300; line-height: 1.6; }
.takeaway-open { font-family: 'Syne', sans-serif; font-size: 11px; font-weight: 700; letter-spacing: 0.04em; color: var(--lime); margin-top: 0.85rem; }

.for-who { background: var(--surface); border: 1px solid var(--border); border-radius: 20px; padding: 3rem; margin-bottom: 5rem; display: grid; grid-template-columns: 1fr 1fr; gap: 3rem; }
@media (max-width: 700px) { .for-who { grid-template-columns: 1fr; gap: 2rem; padding: 2rem; } }
.for-who-col-title { font-family: 'Syne', sans-serif; font-size: 11px; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; margin-bottom: 1.25rem; }
.for-who-col-title.yes { color: var(--lime); }
.for-who-col-title.no { color: #e05555; }
.for-item { display: flex; align-items: flex-start; gap: 10px; font-size: 13.5px; font-weight: 300; color: var(--text); margin-bottom: 0.85rem; line-height: 1.5; }
.for-item-icon { font-size: 14px; margin-top: 1px; flex-shrink: 0; }

.facilitator { display: flex; gap: 2rem; align-items: flex-start; margin-bottom: 5rem; }
@media (max-width: 600px) { .facilitator { flex-direction: column; gap: 1.5rem; } }
.facilitator-avatar { width: 80px; height: 80px; min-width: 80px; border-radius: 50%; background: var(--lime-dim); border: 2px solid var(--lime-border); display: flex; align-items: center; justify-content: center; font-family: 'Syne', sans-serif; font-size: 1.6rem; font-weight: 800; color: var(--lime); }
.facilitator-label { font-family: 'Syne', sans-serif; font-size: 10px; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; color: var(--muted); margin-bottom: 0.35rem; }
.facilitator-name { font-family: 'Syne', sans-serif; font-size: 1.2rem; font-weight: 800; color: var(--text); margin-bottom: 0.5rem; }
.facilitator-bio { font-size: 14px; color: var(--muted); font-weight: 300; line-height: 1.7; max-width: 560px; }

.bottom-cta { background: var(--lime); border-radius: 20px; padding: 4rem 3rem; text-align: center; margin-bottom: 4rem; position: relative; overflow: hidden; }
.bottom-cta::before { content: 'TAAB'; position: absolute; font-family: 'Syne', sans-serif; font-size: 14rem; font-weight: 800; color: rgba(0,0,0,0.06); top: 50%; left: 50%; transform: translate(-50%, -50%); pointer-events: none; white-space: nowrap; letter-spacing: -0.04em; }
.bottom-cta-date { font-family: 'Syne', sans-serif; font-size: 11px; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase; color: rgba(0,0,0,0.5); margin-bottom: 0.75rem; }
.bottom-cta-title { font-family: 'Syne', sans-serif; font-size: clamp(1.4rem, 4vw, 2.2rem); font-weight: 800; color: var(--bg); margin-bottom: 0.5rem; line-height: 1.2; position: relative; }
.bottom-cta-sub { font-size: 15px; color: rgba(0,0,0,0.55); margin-bottom: 2rem; font-weight: 300; }
.bottom-cta-btn { display: inline-block; background: var(--bg); color: var(--lime); font-family: 'Syne', sans-serif; font-size: 13px; font-weight: 800; letter-spacing: 0.06em; text-transform: uppercase; padding: 14px 36px; border-radius: 100px; text-decoration: none; transition: transform 0.2s, opacity 0.2s; }
.bottom-cta-btn:hover { transform: translateY(-2px); opacity: 0.9; }

footer { text-align: center; padding: 2rem 0 3rem; font-size: 12px; color: var(--muted); border-top: 1px solid var(--border); font-weight: 300; }
footer a { color: var(--muted); text-decoration: none; }
footer a:hover { color: var(--lime); }

@keyframes fadeUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
.reveal { opacity: 0; transform: translateY(24px); transition: opacity 0.6s ease, transform 0.6s ease; }
.reveal.visible { opacity: 1; transform: translateY(0); }
</style>
@endpush

<div class="bg-glow"></div>

<div class="page-wrap">

  <nav>
    <div class="nav-logo">TAAB <span>by Repetigo</span></div>
    <a href="#register" class="nav-cta">Reserve my spot</a>
  </nav>

  <!-- Hero -->
  <section class="hero">
    <div class="hero-left">
      <div class="hero-eyebrow">{{ $dateLong }} · Virtual · One day only</div>
      <div class="hero-wordmark">TAAB</div>
      <div class="hero-title">The AI Automation Bootcamp</div>
      <div class="hero-tagline">Clarity before you commit.<br>Everything you need to decide.</div>

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
        Hosted by <strong>{{ $mc['host'] }}</strong> — AI Automation Engineer, 7+ years building real client systems. Founder of Repetigo.
      </div>
    </div>

    <!-- Registration form -->
    <div id="register">
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
              <input class="form-input" type="text" id="fname" name="first_name" placeholder="Adebayo" required>
            </div>
            <div class="form-group">
              <label class="form-label" for="lname">Last name</label>
              <input class="form-input" type="text" id="lname" name="last_name" placeholder="Okafor" required>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label" for="email">Email address</label>
            <input class="form-input" type="email" id="email" name="email" placeholder="you@email.com" required>
          </div>

          <div class="form-group">
            <label class="form-label" for="whatsapp">WhatsApp number</label>
            <input class="form-input" type="tel" id="whatsapp" name="whatsapp" placeholder="+234 800 000 0000">
          </div>

          <div class="form-group">
            <label class="form-label" for="background">Your current background</label>
            <select class="form-select" id="background" name="background" required>
              <option value="" disabled selected>Select one</option>
              <option>Complete beginner — no tech background</option>
              <option>Developer / technical background</option>
              <option>Business owner / entrepreneur</option>
              <option>Freelancer / consultant</option>
              <option>Student</option>
              <option>Other</option>
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
          <div class="form-disclaimer">Free to attend. No spam. Zoom link sent to your email 24hrs before.</div>
        </form>

        <div class="form-success" id="form-success">
          <div class="success-icon">🎯</div>
          <h3>You're in. See you {{ $mcDate ? $mcDate->translatedFormat('l') : 'soon' }}.</h3>
          <p>Check your inbox for a confirmation email. Zoom link arrives 24hrs before the event. We'll also send the link to join the attendee WhatsApp group.</p>
        </div>
      </div>
    </div>
  </section>

  <hr class="section-divider">

  <!-- Sessions -->
  <div class="reveal">
    <div class="section-eyebrow">The agenda</div>
    <div class="section-title">5 sessions. <em>One clear answer.</em></div>
  </div>

  <div class="sessions-grid reveal">
    <div class="session-card"><div class="session-num">Session 01</div><div class="session-title">What AI automation actually is</div><div class="session-desc">Not just ChatGPT. Not just Zapier. A real definition, the 3 business models, and a live workflow running on screen.</div></div>
    <div class="session-card"><div class="session-num">Session 02</div><div class="session-title">Entry requirements & the tool landscape</div><div class="session-desc">The skill spectrum from no-code to developer. The tool map — n8n, Make, Zapier. An honest look at the learning curve.</div></div>
    <div class="session-card"><div class="session-num">Session 03</div><div class="session-title">The cost of entry & ROI reality</div><div class="session-desc">Real monthly numbers. How long to first client. Pricing frameworks. Your personalised break-even calculation — live.</div></div>
    <div class="session-card"><div class="session-num">Session 04</div><div class="session-title">Opportunities & where to play</div><div class="session-desc">The 5 highest-demand use cases. Industries spending now. The Nigerian SME market. Challenges you will face — honestly.</div></div>
    <div class="session-card"><div class="session-num">Session 05</div><div class="session-title">Readiness assessment + open Q&A</div><div class="session-desc">A live scorecard across 5 dimensions. Your outcome bucket. And an open floor — no agenda, attendees drive it.</div></div>
    <div class="session-card" style="background: var(--lime-dim); border-color: var(--lime-border);"><div class="session-num" style="color: var(--lime);">Take away</div><div class="session-title">3 tools you keep forever</div><div class="session-desc" style="color: rgba(200,240,100,0.7);">Readiness Scorecard · ROI Calculator · Tool Stack Guide. Built during the session. Yours to use after.</div></div>
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
      <div class="takeaway-desc">Your real numbers — tool costs, learning investment, break-even month, 12-month revenue projection. Filled in live during Session 3.</div>
      <div class="takeaway-open">Try it now →</div>
    </a>
    <a class="takeaway-card" href="{{ route('taab.tools') }}">
      <div class="takeaway-num">03</div>
      <div class="takeaway-title">Tool Stack Guide</div>
      <div class="takeaway-desc">20 tools across 3 levels — beginner, intermediate, advanced — with honest costs and 4 recommended stacks for real client scenarios.</div>
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
      <div class="for-item"><span class="for-item-icon">✓</span> You want to see what real client automation work looks like — not a tutorial, a real system</div>
      <div class="for-item"><span class="for-item-icon">✓</span> You're a developer, freelancer, entrepreneur, or student in Nigeria or anywhere in Africa</div>
    </div>
    <div>
      <div class="for-who-col-title no">✗ &nbsp;This bootcamp is NOT for you if…</div>
      <div class="for-item"><span class="for-item-icon" style="color:#e05555">✗</span> You're already earning from AI automation and want advanced technical training</div>
      <div class="for-item"><span class="for-item-icon" style="color:#e05555">✗</span> You want a step-by-step "build this exact workflow" tutorial (that's what the <a href="{{ config('taab.accelerator_url') }}" style="color:var(--lime);text-decoration:none">Accelerator</a> is for)</div>
      <div class="for-item"><span class="for-item-icon" style="color:#e05555">✗</span> You're looking for a get-rich-quick shortcut — this bootcamp is about honest clarity, not hype</div>
      <div class="for-item"><span class="for-item-icon" style="color:#e05555">✗</span> You can't commit to being present for the full day (the live sessions build on each other)</div>
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
        AI Automation Engineer and Backend Developer based in Abuja, Nigeria, with 7+ years building production systems. Founder of Repetigo — a B2B AI automation consultancy — and creator of the AI Automation Accelerator cohort programme. AJ has built live client systems including WhatsApp lead qualifiers, booking automation workflows, and AI sales chatbots. He runs this bootcamp because he's watched too many people jump in blind and burn out. This is the day he wishes had existed when he started.
      </div>
    </div>
  </div>

  <!-- Bottom CTA -->
  <div class="bottom-cta reveal">
    <div class="bottom-cta-date">{{ $dateLong }} · {{ $mc['time'] }}</div>
    <div class="bottom-cta-title">One day. One decision. Finally made.</div>
    <div class="bottom-cta-sub">Free to attend. Limited spots.@if($closes) Registration closes {{ $closes }}.@endif</div>
    <a href="#register" class="bottom-cta-btn">Reserve your seat now</a>
  </div>

  <footer>
    <p>© {{ date('Y') }} Repetigo · <a href="https://repetigo.co">repetigo.co</a> · Questions? Reach out on WhatsApp or email hello@repetigo.co</p>
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
form.addEventListener('submit', async (e) => {
  e.preventDefault();
  const btn = form.querySelector('.form-submit');
  const original = btn.textContent;
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
    } else {
      btn.textContent = 'Check your details — try again';
      btn.disabled = false;
    }
  } catch {
    btn.textContent = 'Network error — please try again';
    btn.disabled = false;
  }
});

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
