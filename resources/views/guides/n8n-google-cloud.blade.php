<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="A copy-paste, beginner-proof guide to self-hosting n8n on Google Cloud's free tier — from the AI Automation Accelerator by AJBuildAI.">
<title>Set up n8n on Google Cloud — the free-hosting guide</title>

<style>
  :root {
    --font-sans: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    --font-mono: ui-monospace, "SF Mono", "JetBrains Mono", "Cascadia Code", Menlo, Consolas, monospace;

    /* light (default) — cool paper */
    --bg: #eef2f6;
    --surface: #ffffff;
    --surface-2: #f5f8fb;
    --text: #0d1720;
    --muted: #56697b;
    --faint: #8497a8;
    --border: rgba(13,35,56,0.12);
    --border-strong: rgba(13,35,56,0.20);
    --accent: #0e7490;      /* cyan, darkened for contrast on paper */
    --accent-soft: rgba(14,116,144,0.10);
    --ok: #15803d;
    --ok-soft: rgba(21,128,61,0.10);
    --warn: #b45309;
    --warn-soft: rgba(180,83,9,0.10);

    /* terminal console — always dark, both themes */
    --con-bg: #0b1220;
    --con-head: #0f1a2b;
    --con-border: rgba(120,170,210,0.16);
    --con-text: #d7e2ee;
    --con-prompt: #4dd6ee;
    --con-muted: #6f8199;

    --maxw: 46rem;
  }
  @media (prefers-color-scheme: dark) {
    :root {
      --bg: #080c13;
      --surface: #0f1722;
      --surface-2: #0c141f;
      --text: #e6edf3;
      --muted: #8b9aad;
      --faint: #647589;
      --border: rgba(120,165,205,0.12);
      --border-strong: rgba(120,165,205,0.22);
      --accent: #34d3ee;
      --accent-soft: rgba(52,211,238,0.10);
      --ok: #4ade80;
      --ok-soft: rgba(74,222,128,0.10);
      --warn: #fbbf24;
      --warn-soft: rgba(251,191,36,0.10);
    }
  }
  :root[data-theme="light"] {
    --bg: #eef2f6; --surface: #ffffff; --surface-2: #f5f8fb; --text: #0d1720;
    --muted: #56697b; --faint: #8497a8; --border: rgba(13,35,56,0.12); --border-strong: rgba(13,35,56,0.20);
    --accent: #0e7490; --accent-soft: rgba(14,116,144,0.10); --ok: #15803d; --ok-soft: rgba(21,128,61,0.10);
    --warn: #b45309; --warn-soft: rgba(180,83,9,0.10);
  }
  :root[data-theme="dark"] {
    --bg: #080c13; --surface: #0f1722; --surface-2: #0c141f; --text: #e6edf3;
    --muted: #8b9aad; --faint: #647589; --border: rgba(120,165,205,0.12); --border-strong: rgba(120,165,205,0.22);
    --accent: #34d3ee; --accent-soft: rgba(52,211,238,0.10); --ok: #4ade80; --ok-soft: rgba(74,222,128,0.10);
    --warn: #fbbf24; --warn-soft: rgba(251,191,36,0.10);
  }

  * { box-sizing: border-box; }
  html { scroll-behavior: smooth; scroll-padding-top: 5rem; }
  @media (prefers-reduced-motion: reduce) { html { scroll-behavior: auto; } * { animation: none !important; transition: none !important; } }
  body {
    margin: 0; background: var(--bg); color: var(--text);
    font-family: var(--font-sans); font-size: 17px; line-height: 1.68;
    -webkit-font-smoothing: antialiased; text-rendering: optimizeLegibility;
  }
  ::selection { background: var(--accent); color: #04121a; }

  /* scroll progress */
  #progress { position: fixed; top: 0; left: 0; height: 2px; width: 0%; background: var(--accent); z-index: 50; transition: width .1s linear; }

  /* top bar */
  .topbar {
    position: sticky; top: 0; z-index: 40; backdrop-filter: blur(10px);
    background: color-mix(in srgb, var(--bg) 82%, transparent);
    border-bottom: 1px solid var(--border);
    display: flex; align-items: center; justify-content: space-between;
    padding: .7rem clamp(1rem, 4vw, 2rem);
  }
  .brand { font-weight: 800; letter-spacing: -.02em; font-size: 1rem; text-transform: uppercase; font-style: italic; }
  .brand b { color: var(--accent); }
  .themetoggle {
    font-family: var(--font-mono); font-size: .7rem; letter-spacing: .12em; text-transform: uppercase;
    background: transparent; color: var(--muted); border: 1px solid var(--border-strong);
    padding: .4rem .7rem; border-radius: 7px; cursor: pointer;
  }
  .themetoggle:hover { color: var(--accent); border-color: var(--accent); }

  .wrap { display: grid; grid-template-columns: 1fr; max-width: 78rem; margin: 0 auto; padding: 0 clamp(1rem,4vw,2rem); }
  @media (min-width: 1040px) { .wrap { grid-template-columns: 15rem minmax(0,1fr); gap: 3rem; } }

  /* table of contents */
  nav.toc { display: none; }
  @media (min-width: 1040px) {
    nav.toc { display: block; position: sticky; top: 4.4rem; align-self: start; height: calc(100vh - 5rem); overflow-y: auto; padding: 2rem 0; }
  }
  nav.toc .lbl { font-family: var(--font-mono); font-size: .66rem; letter-spacing: .18em; text-transform: uppercase; color: var(--faint); margin-bottom: 1rem; }
  nav.toc a {
    display: flex; gap: .7rem; align-items: baseline; text-decoration: none; color: var(--muted);
    font-size: .84rem; padding: .32rem 0; border-left: 2px solid transparent; padding-left: .9rem; margin-left: -2px;
  }
  nav.toc a .n { font-family: var(--font-mono); font-size: .72rem; color: var(--faint); font-variant-numeric: tabular-nums; }
  nav.toc a:hover { color: var(--text); }
  nav.toc a.active { color: var(--accent); border-left-color: var(--accent); }
  nav.toc a.active .n { color: var(--accent); }

  main { padding: 2.5rem 0 5rem; min-width: 0; }
  .col { max-width: var(--maxw); }

  /* hero */
  .hero { padding: 1.5rem 0 2.5rem; border-bottom: 1px solid var(--border); margin-bottom: 2.5rem; }
  .eyebrow { font-family: var(--font-mono); font-size: .72rem; letter-spacing: .16em; text-transform: uppercase; color: var(--accent); display: inline-flex; align-items: center; gap: .5rem; }
  .eyebrow::before { content: "▍"; }
  h1 { font-size: clamp(2rem, 5vw, 3rem); line-height: 1.05; letter-spacing: -.03em; font-weight: 800; margin: 1rem 0 .8rem; text-wrap: balance; }
  h1 em { font-style: normal; color: var(--accent); }
  .lede { font-size: 1.12rem; color: var(--muted); max-width: 38rem; }
  .meta { display: flex; flex-wrap: wrap; gap: .6rem; margin-top: 1.6rem; }
  .chip { font-family: var(--font-mono); font-size: .74rem; letter-spacing: .04em; color: var(--text); background: var(--surface); border: 1px solid var(--border-strong); border-radius: 999px; padding: .38rem .8rem; }
  .chip b { color: var(--accent); }

  /* outcome terminal mock */
  .term-mock { margin-top: 1.8rem; border: 1px solid var(--con-border); border-radius: 12px; overflow: hidden; background: var(--con-bg); box-shadow: 0 24px 50px -30px rgba(0,0,0,.6); }
  .term-mock .bar { background: var(--con-head); padding: .6rem .9rem; display: flex; align-items: center; gap: .45rem; border-bottom: 1px solid var(--con-border); }
  .term-mock .bar i { width: 11px; height: 11px; border-radius: 50%; display: inline-block; }
  .term-mock .bar .u { margin-left: .6rem; font-family: var(--font-mono); font-size: .72rem; color: var(--con-muted); }
  .term-mock .body { padding: 1rem 1.1rem; font-family: var(--font-mono); font-size: .82rem; line-height: 1.85; color: var(--con-text); }
  .term-mock .body .c { color: var(--con-prompt); } .term-mock .body .g { color: #6ee7a8; } .term-mock .body .m { color: var(--con-muted); }

  /* sections */
  section { padding-top: 2.5rem; scroll-margin-top: 5rem; }
  .part-h { display: flex; align-items: baseline; gap: 1rem; margin-bottom: .4rem; }
  .part-h .num { font-family: var(--font-mono); font-size: .8rem; color: var(--accent); letter-spacing: .1em; font-variant-numeric: tabular-nums; padding-top: .35rem; }
  h2 { font-size: clamp(1.5rem, 3.4vw, 2rem); letter-spacing: -.02em; font-weight: 800; margin: 0; text-wrap: balance; }
  .part-sub { color: var(--muted); margin: .1rem 0 1.4rem; }
  h3 { font-size: 1.12rem; font-weight: 700; letter-spacing: -.01em; margin: 2rem 0 .6rem; }
  h3 .k { font-family: var(--font-mono); font-size: .78rem; color: var(--accent); margin-right: .5rem; }
  p { margin: 0 0 1rem; }
  a.link { color: var(--accent); text-decoration: none; border-bottom: 1px solid var(--accent-soft); }
  a.link:hover { border-bottom-color: var(--accent); }
  strong { font-weight: 700; }
  code.inl { font-family: var(--font-mono); font-size: .86em; background: var(--surface-2); border: 1px solid var(--border); padding: .08em .4em; border-radius: 5px; color: var(--text); }
  kbd { font-family: var(--font-mono); font-size: .8em; background: var(--surface); border: 1px solid var(--border-strong); border-bottom-width: 2px; border-radius: 5px; padding: .1em .45em; }

  ul.clean { list-style: none; padding: 0; margin: 0 0 1.2rem; }
  ul.clean > li { position: relative; padding-left: 1.5rem; margin: .5rem 0; }
  ul.clean > li::before { content: "›"; position: absolute; left: .2rem; color: var(--accent); font-weight: 700; }
  ul.check > li::before { content: "☐"; color: var(--faint); }
  ol.steps { list-style: none; counter-reset: s; padding: 0; margin: 0 0 1.2rem; }
  ol.steps > li { counter-increment: s; position: relative; padding-left: 2.4rem; margin: .85rem 0; }
  ol.steps > li::before { content: counter(s); position: absolute; left: 0; top: .05rem; width: 1.6rem; height: 1.6rem; border-radius: 7px; background: var(--accent-soft); color: var(--accent); font-family: var(--font-mono); font-size: .82rem; font-weight: 700; display: grid; place-items: center; }

  /* console / code blocks */
  .console { margin: 1.1rem 0 1.3rem; border: 1px solid var(--con-border); border-radius: 10px; overflow: hidden; background: var(--con-bg); }
  .console .chead { display: flex; align-items: center; justify-content: space-between; background: var(--con-head); border-bottom: 1px solid var(--con-border); padding: .4rem .75rem; }
  .console .chead .tag { font-family: var(--font-mono); font-size: .68rem; letter-spacing: .12em; text-transform: uppercase; color: var(--con-muted); display: flex; align-items: center; gap: .5rem; }
  .console .chead .tag i { width: 9px; height: 9px; border-radius: 50%; background: #f26d5b; box-shadow: 16px 0 0 #f5bf4f, 32px 0 0 #64c97b; }
  .copybtn { font-family: var(--font-mono); font-size: .66rem; letter-spacing: .1em; text-transform: uppercase; color: var(--con-muted); background: transparent; border: 1px solid var(--con-border); border-radius: 6px; padding: .28rem .55rem; cursor: pointer; }
  .copybtn:hover { color: var(--con-prompt); border-color: var(--con-prompt); }
  .copybtn.done { color: #6ee7a8; border-color: #6ee7a8; }
  .console pre { margin: 0; padding: .9rem 1rem; overflow-x: auto; }
  .console code { font-family: var(--font-mono); font-size: .84rem; line-height: 1.7; color: var(--con-text); white-space: pre; }
  .console.cmd code::before { content: "$ "; color: var(--con-prompt); }
  .console .cmt { color: var(--con-muted); }

  /* callouts */
  .call { border: 1px solid var(--border); border-left-width: 3px; border-radius: 8px; padding: .9rem 1.1rem; margin: 1.2rem 0; background: var(--surface); font-size: .96rem; }
  .call .h { font-family: var(--font-mono); font-size: .7rem; letter-spacing: .12em; text-transform: uppercase; display: flex; align-items: center; gap: .5rem; margin-bottom: .35rem; }
  .call p:last-child { margin-bottom: 0; }
  .call.check { border-left-color: var(--ok); background: var(--ok-soft); } .call.check .h { color: var(--ok); }
  .call.warn { border-left-color: var(--warn); background: var(--warn-soft); } .call.warn .h { color: var(--warn); }
  .call.note { border-left-color: var(--accent); background: var(--accent-soft); } .call.note .h { color: var(--accent); }

  /* tables */
  .tablewrap { overflow-x: auto; margin: 1.2rem 0; border: 1px solid var(--border); border-radius: 10px; }
  table { border-collapse: collapse; width: 100%; font-size: .92rem; }
  th, td { text-align: left; padding: .7rem .9rem; border-bottom: 1px solid var(--border); vertical-align: top; }
  thead th { font-family: var(--font-mono); font-size: .68rem; letter-spacing: .1em; text-transform: uppercase; color: var(--muted); background: var(--surface-2); }
  tbody tr:last-child td { border-bottom: 0; }
  td b { color: var(--accent); }

  .foot { margin-top: 3rem; padding-top: 1.6rem; border-top: 1px solid var(--border); color: var(--faint); font-size: .85rem; font-family: var(--font-mono); }

  a:focus-visible, button:focus-visible { outline: 2px solid var(--accent); outline-offset: 2px; border-radius: 4px; }
</style>
</head>
<body>

<div id="progress"></div>

<header class="topbar">
  <a href="/" class="brand" style="text-decoration:none;color:inherit">AJBUILD<b>AI</b></a>
  <button class="themetoggle" id="tt" aria-label="Toggle colour theme">◐ Theme</button>
</header>

<div class="wrap">
  <nav class="toc" aria-label="Guide contents">
    <div class="lbl">The build</div>
    <a href="#start"><span class="n">00</span> Before you start</a>
    <a href="#domain"><span class="n">01</span> Get a domain</a>
    <a href="#server"><span class="n">02</span> Create the server</a>
    <a href="#dns"><span class="n">03</span> Point the domain</a>
    <a href="#connect"><span class="n">04</span> Connect (SSH)</a>
    <a href="#prep"><span class="n">05</span> Prepare the server</a>
    <a href="#install"><span class="n">06</span> Install n8n + HTTPS</a>
    <a href="#golive"><span class="n">07</span> Go live</a>
    <a href="#run"><span class="n">08</span> Keep it running</a>
    <a href="#trouble"><span class="n">09</span> Troubleshooting</a>
  </nav>

  <main>
    <div class="col">

      <div class="hero" id="top">
        <span class="eyebrow">Self-hosting guide · no code required</span>
        <h1>Your own n8n. Live. <em>₦0 a month.</em></h1>
        <p class="lede">A private n8n on your own web address, on Google Cloud's Always-Free server, with a real HTTPS padlock. You copy and paste, exactly as written — every step spelled out, nothing assumed, nothing skipped.</p>
        <div class="meta">
          <span class="chip">⏱ <b>45–60 min</b> first time</span>
          <span class="chip">💳 hosting <b>₦0/mo</b></span>
          <span class="chip">🌐 domain ~<b>₦8–15k/yr</b></span>
          <span class="chip">🧑‍💻 <b>no coding</b></span>
        </div>

        <div class="term-mock" aria-hidden="true">
          <div class="bar"><i style="background:#f26d5b"></i><i style="background:#f5bf4f"></i><i style="background:#64c97b"></i><span class="u">you@n8n-server: ~/n8n</span></div>
          <div class="body">
<span class="c">$</span> sudo docker compose up -d<br>
<span class="m">[+] Running 4/4 ✔ n8n  ✔ caddy  ✔ networks  ✔ volumes</span><br>
<span class="c">$</span> <span class="m"># open https://n8n.yourdomain.com →</span> <span class="g">🔒 live</span>
          </div>
        </div>
      </div>

      <p style="color:var(--muted)">You'll move between three places: your <strong>domain registrar</strong>, the <strong>Google Cloud Console</strong>, and a <strong>black terminal window</strong> on your server. The diagram below is what the steps quietly build — you don't need to understand it now.</p>

      <div class="console"><div class="chead"><span class="tag"><i></i> what you're building</span></div>
<pre><code>Your domain  (n8n.yourdomain.com)
      │   DNS points it to…
      ▼
Google Cloud server  (free e2-micro)
      ├─ Caddy  → free SSL + forwards traffic
      └─ n8n    → your automation tool, in Docker</code></pre></div>

      <div class="call warn"><div class="h">⚠ Screens change</div><p>Google Cloud and domain sites rename menus often. If a button isn't <em>exactly</em> where this says, look for the same <strong>word</strong> nearby — the steps and the commands themselves don't change.</p></div>

      <!-- 00 -->
      <section id="start">
        <div class="part-h"><span class="num">00</span><h2>Before you start</h2></div>
        <p class="part-sub">Four things to have ready.</p>
        <ul class="clean check">
          <li>A <strong>Google account</strong> (a normal Gmail is fine).</li>
          <li>A <strong>debit/credit card</strong> — Google requires one to verify you're human. On the Always-Free server <strong>you won't be charged</strong>; we also set a ₦0 budget alert as a safety net in Part 02.</li>
          <li>About <strong>₦8,000–15,000</strong> for a domain name (yearly).</li>
          <li>A computer with a browser — Windows, Mac, or Linux. Everything happens in the browser.</li>
        </ul>
      </section>

      <!-- 01 -->
      <section id="domain">
        <div class="part-h"><span class="num">01</span><h2>Get a domain name</h2></div>
        <p class="part-sub">Your web address. Any registrar works — <strong>Namecheap</strong> is used here.</p>
        <ol class="steps">
          <li>Go to <a class="link" href="https://www.namecheap.com" target="_blank" rel="noopener">namecheap.com</a> and search for a domain (e.g. <code class="inl">yourbrand.com</code>).</li>
          <li>Add it to the cart and check out. Turn on <strong>free domain privacy</strong> if offered — it hides your personal details.</li>
          <li>Pay. You now own the domain. <strong>Keep this tab open</strong> — you return to it in Part 03.</li>
        </ol>
        <div class="call note"><div class="h">↳ tip</div><p>You'll use a <strong>subdomain</strong> — <code class="inl">n8n.yourdomain.com</code> — so the main domain stays free for a website later. You add that in Part 03, not now.</p></div>
      </section>

      <!-- 02 -->
      <section id="server">
        <div class="part-h"><span class="num">02</span><h2>Create your free Google Cloud server</h2></div>
        <p class="part-sub">Account → project → budget alert → the machine → a permanent IP.</p>

        <h3><span class="k">2.1</span> Create a Google Cloud account</h3>
        <ol class="steps">
          <li>Go to <a class="link" href="https://console.cloud.google.com" target="_blank" rel="noopener">console.cloud.google.com</a> and sign in.</li>
          <li>First time? Accept the terms and click <strong>Start free / Activate</strong>.</li>
          <li>Enter your details and the <strong>card</strong> for verification. Choose <strong>Individual</strong>. Our server stays in the <strong>Always Free</strong> tier regardless of any trial credits.</li>
        </ol>

        <h3><span class="k">2.2</span> Create a project</h3>
        <p>A "project" is just a folder for your stuff.</p>
        <ol class="steps">
          <li>Top-left, click the <strong>project dropdown</strong> (may say "My First Project").</li>
          <li><strong>New Project</strong> → name it <code class="inl">n8n</code> → <strong>Create</strong>.</li>
          <li>Make sure the dropdown now shows your <strong>n8n</strong> project selected.</li>
        </ol>

        <h3><span class="k">2.3</span> Set a budget alert (safety net)</h3>
        <p>So you're warned the instant anything tries to cost money.</p>
        <ol class="steps">
          <li>In the top search bar type <strong>Budgets &amp; alerts</strong> and open it.</li>
          <li><strong>Create budget</strong> → name it <code class="inl">zero</code> → set <strong>Target amount</strong> to a small number like <strong>$1</strong> → alert at 50% and 100% → <strong>Finish</strong>.</li>
          <li>You'll now get an email if spending ever nears $1. (It shouldn't.)</li>
        </ol>

        <h3><span class="k">2.4</span> Create the server (VM)</h3>
        <ol class="steps">
          <li>Search <strong>VM instances</strong> → open <strong>Compute Engine → VM instances</strong>. If asked, click <strong>Enable</strong> and wait ~1 min.</li>
          <li>Click <strong>Create instance</strong> and match these <em>exactly</em> — they're what keep it free:</li>
        </ol>
        <ul class="clean">
          <li><strong>Name:</strong> <code class="inl">n8n-server</code></li>
          <li><strong>Region:</strong> pick <strong>one</strong> of <code class="inl">us-west1</code>, <code class="inl">us-central1</code>, <code class="inl">us-east1</code> — only these qualify for the free e2-micro.</li>
          <li><strong>Series</strong> <code class="inl">E2</code> → <strong>Machine type</strong> <code class="inl">e2-micro</code> (1 GB memory). This is the free one.</li>
          <li><strong>Boot disk → Change:</strong> Ubuntu → <code class="inl">Ubuntu 22.04 LTS</code> → <strong>Standard persistent disk</strong> → <strong>30 GB</strong> → Select.</li>
          <li><strong>Firewall:</strong> tick <strong>both</strong> ✅ Allow HTTP and ✅ Allow HTTPS.</li>
        </ul>
        <ol class="steps" start="3" style="counter-reset:s 2">
          <li>Click <strong>Create</strong>. After ~30s you'll see an <strong>External IP</strong> (e.g. <code class="inl">34.121.x.x</code>). Leave this tab open.</li>
        </ol>

        <h3><span class="k">2.5</span> Make the IP permanent (static)</h3>
        <p>By default the IP can change on reboot, which would break your domain. Lock it.</p>
        <ol class="steps">
          <li>Search <strong>IP addresses</strong> → open <strong>VPC network → IP addresses</strong>.</li>
          <li>On the <code class="inl">n8n-server</code> external IP row, choose <strong>Promote to static</strong> (a.k.a. "Reserve"), name it <code class="inl">n8n-ip</code> → <strong>Reserve</strong>.</li>
          <li><strong>Write down this IP.</strong> You need it next.</li>
        </ol>
      </section>

      <!-- 03 -->
      <section id="dns">
        <div class="part-h"><span class="num">03</span><h2>Point your domain at the server</h2></div>
        <p class="part-sub">Back in your registrar (Namecheap).</p>
        <ol class="steps">
          <li><strong>Domain List → Manage</strong> → <strong>Advanced DNS</strong> tab.</li>
          <li>Under <strong>Host Records → Add New Record</strong>:</li>
        </ol>
        <div class="tablewrap"><table>
          <thead><tr><th>Field</th><th>Value</th></tr></thead>
          <tbody>
            <tr><td>Type</td><td><code class="inl">A Record</code></td></tr>
            <tr><td>Host</td><td><code class="inl">n8n</code> &nbsp;<span style="color:var(--muted)">← makes <code class="inl">n8n.yourdomain.com</code></span></td></tr>
            <tr><td>Value</td><td>your static IP from Part 2.5 (e.g. <code class="inl">34.121.x.x</code>)</td></tr>
            <tr><td>TTL</td><td><code class="inl">Automatic</code></td></tr>
          </tbody>
        </table></div>
        <ol class="steps" start="3" style="counter-reset:s 2">
          <li><strong>Save</strong> (the green tick).</li>
          <li><strong>Delete</strong> any default "parking" record that would conflict — keep your new A record.</li>
        </ol>
        <div class="call note"><div class="h">↳ heads up</div><p>DNS takes <strong>5 minutes to a couple of hours</strong> to spread. Carry on with the next parts meanwhile — we check it in Part 07.</p></div>
      </section>

      <!-- 04 -->
      <section id="connect">
        <div class="part-h"><span class="num">04</span><h2>Connect to your server</h2></div>
        <p class="part-sub">Open the command line — in your browser.</p>
        <ol class="steps">
          <li>Google Cloud → <strong>Compute Engine → VM instances</strong>.</li>
          <li>On the <code class="inl">n8n-server</code> row, click the <strong>SSH</strong> button. A <strong>black terminal window</strong> opens.</li>
          <li>If asked, click <strong>Authorize</strong>. You're now "inside" your server.</li>
        </ol>
        <p>From here it's <strong>copy → paste into the black window → Enter</strong>. To paste: right-click → Paste, or <kbd>Ctrl</kbd>+<kbd>V</kbd>. Test it:</p>
        <div class="console cmd"><div class="chead"><span class="tag"><i></i> terminal</span><button class="copybtn">Copy</button></div>
<pre><code>whoami &amp;&amp; echo "Connected 🎉"</code></pre></div>
        <div class="call check"><div class="h">✓ checkpoint</div><p>You should see your username and <code class="inl">Connected 🎉</code>.</p></div>
        <div class="call warn"><div class="h">⚠ fresh-server hiccup</div><p>For a minute or two after a new server boots, a <code class="inl">sudo</code> command can fail with a strange permissions error (people have seen everything up to a cheeky <em>"I'm sorry, I'm afraid I can't do that"</em>). It just means the server is still finishing setup. <strong>Fix:</strong> close the SSH window, wait ~30s, click <strong>SSH</strong> again, re-run the command.</p></div>
      </section>

      <!-- 05 -->
      <section id="prep">
        <div class="part-h"><span class="num">05</span><h2>Prepare the server</h2></div>
        <p class="part-sub">Run each block one at a time; wait for the prompt to return before the next.</p>

        <h3><span class="k">5.1</span> Update the server</h3>
        <div class="console cmd"><div class="chead"><span class="tag"><i></i> terminal</span><button class="copybtn">Copy</button></div>
<pre><code>sudo apt-get update &amp;&amp; sudo apt-get upgrade -y</code></pre></div>
        <p>If it asks a yes/no question, press <kbd>Enter</kbd> to accept the default.</p>

        <h3><span class="k">5.2</span> Add swap memory <span style="color:var(--warn);font-size:.8rem;font-family:var(--font-mono)">· important</span></h3>
        <p>The free server has only 1 GB of RAM — n8n can run out of memory and crash. Swap prevents that.</p>
        <div class="console cmd"><div class="chead"><span class="tag"><i></i> terminal</span><button class="copybtn">Copy</button></div>
<pre><code>sudo fallocate -l 2G /swapfile
sudo chmod 600 /swapfile
sudo mkswap /swapfile
sudo swapon /swapfile
echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab</code></pre></div>
        <div class="call check"><div class="h">✓ verify</div><p>Run <code class="inl">free -h</code> — you should see a <code class="inl">Swap:</code> line showing <code class="inl">2.0Gi</code>.</p></div>

        <h3><span class="k">5.3</span> Install Docker</h3>
        <p>Docker runs n8n in a tidy, self-contained box. We use Docker's <strong>official one-line installer</strong> — simplest and always current:</p>
        <div class="console cmd"><div class="chead"><span class="tag"><i></i> terminal</span><button class="copybtn">Copy</button></div>
<pre><code>curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh</code></pre></div>
        <p>Takes ~1–2 minutes and installs Docker <em>and</em> the Compose plugin.</p>
        <div class="call check"><div class="h">✓ verify</div><p>Run <code class="inl">sudo docker --version &amp;&amp; sudo docker compose version</code> — both should print a version number. (Already have your own Docker method? Fine — just make sure <code class="inl">docker compose version</code> works; we need the built-in Compose plugin.)</p></div>
      </section>

      <!-- 06 -->
      <section id="install">
        <div class="part-h"><span class="num">06</span><h2>Install n8n with automatic HTTPS</h2></div>
        <p class="part-sub">Three tiny files, then one start command. Caddy fetches a free SSL certificate for you.</p>

        <div class="call note"><div class="h">↳ no text editor needed</div><p>Every file below is made with one "paste this whole block" command — nothing to edit, nothing to get wrong. Prefer an editor? If <code class="inl">nano</code> says <em>command not found</em>, install it: <code class="inl">sudo apt-get install -y nano</code>.</p></div>

        <h3><span class="k">6.1</span> Make a folder</h3>
        <div class="console cmd"><div class="chead"><span class="tag"><i></i> terminal</span><button class="copybtn">Copy</button></div>
<pre><code>mkdir ~/n8n &amp;&amp; cd ~/n8n</code></pre></div>

        <h3><span class="k">6.2</span> Remember your domain</h3>
        <p>Type your subdomain <strong>once</strong> and the next commands reuse it. <strong>Change the address</strong>, then Enter:</p>
        <div class="console cmd"><div class="chead"><span class="tag"><i></i> terminal</span><button class="copybtn">Copy</button></div>
<pre><code>DOMAIN=n8n.yourdomain.com   <span class="cmt"># ← change to YOUR subdomain</span></code></pre></div>
        <div class="call check"><div class="h">✓ verify</div><p>Run <code class="inl">echo "Using domain: $DOMAIN"</code>. If it still says <code class="inl">n8n.yourdomain.com</code>, you forgot to change it — run the line again with your real subdomain.</p></div>

        <h3><span class="k">6.3</span> Settings file — key made for you</h3>
        <p>n8n needs a secret key to encrypt saved passwords. <strong>You don't create it yourself</strong> — this generates a random one and writes the file:</p>
        <div class="console cmd"><div class="chead"><span class="tag"><i></i> terminal</span><button class="copybtn">Copy</button></div>
<pre><code>cat &gt; .env &lt;&lt;EOF
DOMAIN=$DOMAIN
N8N_ENCRYPTION_KEY=$(openssl rand -hex 24)
EOF</code></pre></div>
        <div class="call warn"><div class="h">⚠ save this</div><p>Run <code class="inl">cat .env</code>, copy the <code class="inl">N8N_ENCRYPTION_KEY=…</code> line, and keep it in a password manager. You only need it if you move servers or restore a backup — but if it's lost then, saved credentials can't be recovered. Day to day you never touch it.</p></div>

        <h3><span class="k">6.4</span> Web-server file (<code class="inl">Caddyfile</code>)</h3>
        <p>Paste the whole block — the quoted <code class="inl">'EOF'</code> keeps <code class="inl">{$DOMAIN}</code> literal so Caddy fills it from <code class="inl">.env</code>:</p>
        <div class="console cmd"><div class="chead"><span class="tag"><i></i> terminal</span><button class="copybtn">Copy</button></div>
<pre><code>cat &gt; Caddyfile &lt;&lt;'EOF'
{$DOMAIN} {
    reverse_proxy n8n:5678
}
EOF</code></pre></div>

        <h3><span class="k">6.5</span> Main file (<code class="inl">docker-compose.yml</code>)</h3>
        <div class="console cmd"><div class="chead"><span class="tag"><i></i> terminal</span><button class="copybtn">Copy</button></div>
<pre><code>cat &gt; docker-compose.yml &lt;&lt;'EOF'
services:
  n8n:
    image: docker.n8n.io/n8nio/n8n
    restart: unless-stopped
    env_file: .env
    environment:
      - N8N_HOST=${DOMAIN}
      - N8N_PORT=5678
      - N8N_PROTOCOL=https
      - WEBHOOK_URL=https://${DOMAIN}/
      - N8N_PROXY_HOPS=1
      - N8N_SECURE_COOKIE=true
      - GENERIC_TIMEZONE=Africa/Lagos
    volumes:
      - n8n_data:/home/node/.n8n
    expose:
      - 5678

  caddy:
    image: caddy:2
    restart: unless-stopped
    env_file: .env
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./Caddyfile:/etc/caddy/Caddyfile
      - caddy_data:/data
      - caddy_config:/config

volumes:
  n8n_data:
  caddy_data:
  caddy_config:
EOF</code></pre></div>
        <div class="call check"><div class="h">✓ verify</div><p>Run <code class="inl">ls -a</code> — you should see <code class="inl">.env</code>, <code class="inl">Caddyfile</code>, and <code class="inl">docker-compose.yml</code>.</p></div>
      </section>

      <!-- 07 -->
      <section id="golive">
        <div class="part-h"><span class="num">07</span><h2>Go live</h2></div>

        <h3><span class="k">7.1</span> Check the domain points here</h3>
        <div class="console cmd"><div class="chead"><span class="tag"><i></i> terminal</span><button class="copybtn">Copy</button></div>
<pre><code>dig +short n8n.yourdomain.com</code></pre></div>
        <div class="call warn"><div class="h">⚠ must match first</div><p>It should print <strong>your static IP</strong>. Nothing, or a different IP, means DNS hasn't finished — wait 10–15 min and try again. <strong>Don't start n8n until this matches</strong>, or Caddy can't get the certificate.</p></div>

        <h3><span class="k">7.2</span> Start everything</h3>
        <div class="console cmd"><div class="chead"><span class="tag"><i></i> terminal</span><button class="copybtn">Copy</button></div>
<pre><code>sudo docker compose up -d</code></pre></div>
        <p>First run downloads n8n and Caddy (~1–2 min). <code class="inl">-d</code> keeps it running in the background even after you close the window. Watch the certificate arrive (optional): <code class="inl">sudo docker compose logs -f caddy</code> — look for <code class="inl">certificate obtained</code>, then <kbd>Ctrl</kbd>+<kbd>C</kbd> to stop watching (this does <em>not</em> stop the server).</p>

        <h3><span class="k">7.3</span> Open your n8n</h3>
        <p>In your normal browser go to <strong><code class="inl">https://n8n.yourdomain.com</code></strong>.</p>
        <ul class="clean">
          <li>You should see a <strong>padlock</strong> and the n8n <strong>"Set up owner account"</strong> screen.</li>
          <li>Enter your email + a strong password — that's your n8n login.</li>
        </ul>
        <div class="call check"><div class="h">✓ you're live 🎉</div><p>First load can take 10–20s while the certificate finishes. A brief security warning? Wait a minute and refresh — Caddy is still fetching the cert.</p></div>
      </section>

      <!-- 08 -->
      <section id="run">
        <div class="part-h"><span class="num">08</span><h2>Keeping it running</h2></div>
        <p class="part-sub">All from inside the server — run <code class="inl">cd ~/n8n</code> first.</p>

        <div class="tablewrap"><table>
          <thead><tr><th>Task</th><th>Command</th></tr></thead>
          <tbody>
            <tr><td>See it's running</td><td><code class="inl">sudo docker compose ps</code></td></tr>
            <tr><td>Update n8n (every few weeks)</td><td><code class="inl">sudo docker compose pull &amp;&amp; sudo docker compose up -d</code></td></tr>
            <tr><td>Restart</td><td><code class="inl">sudo docker compose restart</code></td></tr>
            <tr><td>Stop / start</td><td><code class="inl">sudo docker compose down</code> &nbsp;/&nbsp; <code class="inl">sudo docker compose up -d</code></td></tr>
          </tbody>
        </table></div>

        <h3>Back up your workflows <span style="color:var(--muted);font-size:.85rem;font-family:var(--font-mono)">· before any update</span></h3>
        <p>Saves all your n8n data to a file in your home folder:</p>
        <div class="console cmd"><div class="chead"><span class="tag"><i></i> terminal</span><button class="copybtn">Copy</button></div>
<pre><code>sudo docker run --rm -v n8n_n8n_data:/data -v ~:/backup alpine tar czf /backup/n8n-backup-$(date +%F).tar.gz -C /data .</code></pre></div>
        <p>Then download that <code class="inl">.tar.gz</code> from the SSH window (top-right <strong>⚙ → Download file</strong>) and keep it safe.</p>
        <div class="call note"><div class="h">↳ still free?</div><p>Google Cloud → <strong>Billing</strong>. As long as it's the <code class="inl">e2-micro</code> in a free region with a 30 GB standard disk, hosting stays ₦0. Watch only for large <strong>network egress</strong> — normal automation traffic is tiny.</p></div>
      </section>

      <!-- 09 -->
      <section id="trouble">
        <div class="part-h"><span class="num">09</span><h2>Troubleshooting</h2></div>
        <div class="tablewrap"><table>
          <thead><tr><th>Problem</th><th>Fix</th></tr></thead>
          <tbody>
            <tr><td>Site won't load / "can't reach"</td><td>DNS not ready or wrong IP. Re-check 7.1 — <code class="inl">dig +short …</code> must equal your static IP. Confirm HTTP+HTTPS firewall was ticked (2.4).</td></tr>
            <tr><td>"Not secure" / certificate error</td><td>Caddy hasn't got the cert yet — needs DNS correct <em>and</em> ports 80/443 open. Wait 2–3 min, refresh. Check <code class="inl">sudo docker compose logs caddy</code>.</td></tr>
            <tr><td>n8n loads then crashes / restarts</td><td>Out of memory. Confirm swap is on (<code class="inl">free -h</code> shows 2 Gi). Re-do 5.2 if not.</td></tr>
            <tr><td><code class="inl">sudo</code> fails weirdly right after connecting</td><td>Server still provisioning. Close the SSH window, wait ~30s, reopen with <strong>SSH</strong>, retry.</td></tr>
            <tr><td><code class="inl">docker: command not found</code></td><td>Docker didn't install — re-run 5.3. Always use <code class="inl">sudo docker …</code>.</td></tr>
            <tr><td><code class="inl">nano: command not found</code></td><td>You don't need nano here (Part 06 uses paste-blocks). Want it? <code class="inl">sudo apt-get install -y nano</code>.</td></tr>
            <tr><td>Forgot the encryption key</td><td>It's in <code class="inl">~/n8n/.env</code> — <code class="inl">cat ~/n8n/.env</code>. Save it in a password manager.</td></tr>
            <tr><td>Webhooks from n8n don't arrive</td><td>Make sure <code class="inl">WEBHOOK_URL=https://your-domain/</code> in <code class="inl">.env</code>, then <code class="inl">sudo docker compose up -d</code> again.</td></tr>
          </tbody>
        </table></div>
      </section>

      <div class="col" style="margin-top:3rem">
        <div class="part-h"><span class="num" style="color:var(--ok)">✓</span><h2>What this cost you</h2></div>
        <div class="tablewrap"><table>
          <thead><tr><th>Item</th><th>Cost</th></tr></thead>
          <tbody>
            <tr><td>Google Cloud server (e2-micro, free region)</td><td><b>₦0 / month</b></td></tr>
            <tr><td>SSL certificate (Caddy + Let's Encrypt)</td><td><b>₦0</b></td></tr>
            <tr><td>Domain name</td><td>~₦8,000–15,000 / year</td></tr>
            <tr><td>Your own production n8n</td><td><b>done ✓</b></td></tr>
          </tbody>
        </table></div>
        <p style="color:var(--muted)">You now own your automation infrastructure — no monthly n8n subscription, no trial expiry, full control. Back it up, keep it updated, and build.</p>
      </div>

      <div class="foot">AJBuildAI · self-hosting guide · n8n on Google Cloud</div>
    </div>
  </main>
</div>

<script>
  // theme toggle (root gets data-theme; overrides the media query both ways)
  (function () {
    var tt = document.getElementById('tt');
    tt.addEventListener('click', function () {
      var cur = document.documentElement.getAttribute('data-theme');
      if (!cur) { cur = matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'; }
      document.documentElement.setAttribute('data-theme', cur === 'dark' ? 'light' : 'dark');
    });
  })();

  // copy buttons
  document.querySelectorAll('.console').forEach(function (box) {
    var btn = box.querySelector('.copybtn'); if (!btn) return;
    var code = box.querySelector('code');
    btn.addEventListener('click', function () {
      var text = code.innerText.replace(/^\$ /gm, '');
      navigator.clipboard.writeText(text).then(function () {
        btn.textContent = 'Copied ✓'; btn.classList.add('done');
        setTimeout(function () { btn.textContent = 'Copy'; btn.classList.remove('done'); }, 1600);
      });
    });
  });

  // scroll progress + active TOC
  var prog = document.getElementById('progress');
  var links = Array.from(document.querySelectorAll('nav.toc a'));
  var map = {}; links.forEach(function (a) { map[a.getAttribute('href').slice(1)] = a; });
  function onScroll() {
    var h = document.documentElement;
    var pct = (h.scrollTop) / (h.scrollHeight - h.clientHeight) * 100;
    prog.style.width = Math.min(100, Math.max(0, pct)) + '%';
  }
  document.addEventListener('scroll', onScroll, { passive: true }); onScroll();
  if ('IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (e.isIntersecting && map[e.target.id]) {
          links.forEach(function (l) { l.classList.remove('active'); });
          map[e.target.id].classList.add('active');
        }
      });
    }, { rootMargin: '-20% 0px -70% 0px' });
    document.querySelectorAll('section[id]').forEach(function (s) { io.observe(s); });
  }
</script>
</body>
</html>
