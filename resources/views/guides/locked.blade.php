<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="Self-host n8n on your own domain with real HTTPS - a copy-paste written guide. Free for AI Automation Accelerator students.">
<title>Self-hosting guide — AJBuildAI</title>

@include('guides.partials.chrome-css')
</head>
<body>

<div id="progress"></div>

<header class="topbar">
  <a href="/" class="brand" style="text-decoration:none;color:inherit">AJBUILD<b>AI</b></a>
  <button class="themetoggle" id="tt" aria-label="Toggle colour theme">◐ Theme</button>
</header>

<div class="wrap">
  <main>
    <div class="col">

      <div class="hero" id="top">
        <span class="eyebrow">Self-hosting guide · members only</span>
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
        <div class="h">🔒 this guide is for members</div>
        <p>It's included <strong>free with the AI Automation Accelerator</strong>. If you're already enrolled, just sign in.</p>
      </div>

      <p style="display:flex;flex-wrap:wrap;gap:.7rem;margin:1.4rem 0 2rem;">
        <a class="chip" style="text-decoration:none;padding:.6rem 1.1rem;border-color:var(--accent);color:var(--accent);" href="{{ route('login') }}">Sign in →</a>
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

      <div class="call warn">
        <div class="h">⚠ already bought it?</div>
        <p>Open the access link we emailed you and click through from there - that's what unlocks these pages in your browser. Lost it? Reply to that email and we'll resend.</p>
      </div>

      <div class="foot">
        AJBuildAI · <a class="link" href="/accelerator">AI Automation Accelerator</a> · <a class="link" href="mailto:hello@ajbuildai.com">hello@ajbuildai.com</a>
      </div>

    </div>
  </main>
</div>

@include('guides.partials.chrome-js')
</body>
</html>
