<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
@include('partials.icons')
<meta name="description" content="Self-host n8n on your own domain with real HTTPS - a copy-paste written guide. Free for AI Automation Accelerator students.">
<title>Self-hosting guide - AJBuilds AI</title>

@include('guides.partials.chrome-css')
<style>
  /* The shared guide chrome turns .wrap into "15rem sidebar + content" at >=1040px.
     This page has no sidebar, so <main> landed in the 15rem track and the whole sales
     page rendered as a narrow strip on desktop. One column here, chrome untouched. */
  @media (min-width: 1040px) { .wrap { grid-template-columns: minmax(0, 1fr); } }
</style>
{{-- Public sales page only. The paid guides share chrome-css but are for logged-in students, so the pixel is NOT in that partial. --}}
@include('partials.meta-pixel')
</head>
<body>

<div id="progress"></div>

<header class="topbar">
  <a href="/" class="brand" style="text-decoration:none;color:inherit">AJBUILDS<b> AI</b></a>
  <button class="themetoggle" id="tt" aria-label="Toggle colour theme">◐ Theme</button>
</header>

<div class="wrap">
  <main>
    <div class="col">

      <div class="hero" id="top">
        <span class="eyebrow">Self-hosting guide</span>
        <h1>Your own n8n. Live. <em>₦0 a month.</em></h1>
        <p class="lede">A private n8n on your own web address, on a free-tier server, with a real HTTPS padlock. Copy-paste steps, nothing assumed, nothing skipped - plus the Hostinger route if Google won't verify your account.</p>
        <div class="meta">
          <span class="chip">⏱ <b>45–60 min</b> first time</span>
          <span class="chip">💳 hosting <b>₦0/mo</b></span>
          <span class="chip">🧑‍💻 <b>no coding</b></span>
          <span class="chip">🎥 <b>video walkthrough</b> included</span>
        </div>
      </div>

      <div class="call note">
        <div class="h">one guide, both routes</div>
        <p>Buy once and you get <strong>both</strong> the Google Cloud build and the Hostinger fallback, so an account Google won't verify can't leave you stuck. It is also included <strong>free with the AI Automation Accelerator</strong>.</p>
      </div>

      {{-- Buy first. This page is the sales page for the guide, not a lock screen -
           the sign-in and recovery paths sit at the bottom, where they do not
           interrupt someone deciding whether to buy. --}}
      <p style="display:flex;flex-wrap:wrap;gap:.7rem;margin:1.4rem 0 2rem;">
        @if($resource)
          <a class="chip" style="text-decoration:none;padding:.6rem 1.1rem;background:var(--accent);color:#000;border-color:var(--accent);font-weight:600;"
             href="{{ route('resource.buy', $resource) }}">Buy the guide - ₦{{ number_format((float) $resource->price) }}</a>
        @endif
        <a class="chip" style="text-decoration:none;padding:.6rem 1.1rem;" href="/accelerator">See the Accelerator →</a>
      </p>

      <div class="part-h"><span class="num">01</span><h2>What's inside</h2></div>
      <p class="part-sub">The whole build, in the order you do it.</p>
      <ul class="clean">
        <li>Buying a domain and pointing it at your server.</li>
        <li>Creating the free-tier server and connecting to it.</li>
        <li>Installing n8n in Docker behind a real HTTPS certificate.</li>
        <li>Going live, keeping it running, and what to do when it breaks.</li>
        <li>A second route on a small paid host, for anyone Google won't verify.</li>
      </ul>


      {{-- Kept, but small and last: a buyer who lost their access link and an enrolled
           student arriving logged out both land here, and neither has another route. --}}
      <p style="font-size:.85rem;color:var(--muted);margin:2.5rem 0 0;">
        Already bought this, or enrolled in the Accelerator?
        <a class="link" href="{{ route('login') }}">Sign in</a>, or open the access link from your email.
        Lost it? <a class="link" href="mailto:hello@ajbuildai.com">Email us</a> and we'll resend.
      </p>

      <div class="foot">
        AJBuilds AI · <a class="link" href="/accelerator">AI Automation Accelerator</a> · <a class="link" href="mailto:hello@ajbuildai.com">hello@ajbuildai.com</a>
      </div>

    </div>
  </main>
</div>

@include('guides.partials.chrome-js')
</body>
</html>
