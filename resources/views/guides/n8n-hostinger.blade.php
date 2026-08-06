<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="Deploy your own n8n on a Hostinger VPS in one click — the alternative route when Google Cloud won't verify your account. From the AI Automation Accelerator by AJBuildAI.">
<title>Set up n8n on Hostinger — the one-click deploy guide</title>

@include('guides.partials.chrome-css')
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
    <a href="#choose"><span class="n">↯</span> Which guide?</a>
    <a href="#need"><span class="n">00</span> Before you start</a>
    <a href="#buy"><span class="n">01</span> Buy the VPS</a>
    <a href="#install"><span class="n">02</span> One-click install</a>
    <a href="#live"><span class="n">03</span> Go live</a>
    <a href="#run"><span class="n">04</span> Keep it running</a>
    <a href="#trouble"><span class="n">05</span> Troubleshooting</a>
    <a href="#cost"><span class="n">06</span> What it costs</a>
    <a href="#domain"><span class="n">07</span> Your own domain</a>
  </nav>

  <main>
    <div class="col">

      <div class="hero" id="top">
        <span class="eyebrow">Self-hosting guide · the no-Google-Cloud route</span>
        <h1>Google Cloud won't verify you? <em>One click instead.</em></h1>
        <p class="lede">The alternative path to your own private n8n: buy a small server, let Hostinger install n8n for you, and get a real HTTPS address in about ten minutes. No terminal required to get running.</p>
        <div class="meta">
          <span class="chip">⏱ <b>10–15 min</b></span>
          <span class="chip">💳 from <b>$6.49/mo</b></span>
          <span class="chip">🔒 HTTPS <b>automatic</b></span>
          <span class="chip">🖱 <b>no terminal</b> to go live</span>
        </div>

        <div class="term-mock" aria-hidden="true">
          <div class="bar"><i style="background:#f26d5b"></i><i style="background:#f5bf4f"></i><i style="background:#64c97b"></i><span class="u">hPanel · VPS · n8n template</span></div>
          <div class="body">
<span class="c">›</span> <span class="m"># nothing to type — Hostinger builds it for you</span><br>
<span class="m">[+] Installing 4/4 ✔ ubuntu  ✔ docker  ✔ traefik  ✔ n8n</span><br>
<span class="c">›</span> <span class="m"># open https://n8n.srv123456.hstgr.cloud →</span> <span class="g">🔒 live</span>
          </div>
        </div>
      </div>

      <p style="color:var(--muted)">You end up in exactly the same place as the Google Cloud guide — <strong>your own private n8n, on a real web address, with a padlock</strong> — but you pay for it instead of building it by hand.</p>

      <div class="console"><div class="chead"><span class="tag"><i></i> what you're building</span></div>
<pre><code>https://n8n.srv123456.hstgr.cloud   ← free address, works immediately
   (or your own n8n.yourdomain.com — Part 07, optional)
        │
        ▼
Your Hostinger VPS
        ├─ Traefik  → automatic SSL + forwards traffic
        └─ n8n      → your automation tool, in Docker</code></pre></div>

      <div class="call warn"><div class="h">⚠ Screens change</div><p>Hostinger renames menus from time to time. If a button isn't <em>exactly</em> where this says, look for the same <strong>word</strong> nearby — the steps and the commands themselves don't change.</p></div>

      <!-- CHOOSE -->
      <section id="choose">
        <div class="part-h"><span class="num">↯</span><h2>Which guide should you use?</h2></div>
        <p class="part-sub">Read this before spending anything.</p>
        <div class="tablewrap"><table>
          <thead><tr><th></th><th>Google Cloud (free)</th><th>Hostinger (this guide)</th></tr></thead>
          <tbody>
            <tr><td>Hosting cost</td><td><b>₦0/month</b> forever</td><td>Paid — from <b>$6.49/mo</b></td></tr>
            <tr><td>Sign-up</td><td>Needs a card <strong>and</strong> Google's verification — where a lot of people get stuck</td><td>A normal online checkout</td></tr>
            <tr><td>Setup</td><td>~45–60 min, copy-paste terminal commands</td><td>~10–15 min, mostly clicking</td></tr>
            <tr><td>Server power</td><td>1 GB RAM (tight — needs a swap workaround)</td><td>4 GB RAM on the entry plan</td></tr>
            <tr><td>HTTPS</td><td>You set it up (Caddy)</td><td>Automatic</td></tr>
            <tr><td>Domain</td><td>You buy one (~₦8–15k/yr)</td><td>Free Hostinger address instantly; free domain for 1 year included</td></tr>
          </tbody>
        </table></div>
        <div class="call note"><div class="h">↳ our advice</div><p>Try <a class="link" href="/guides/n8n-on-google-cloud">the Google Cloud guide</a> first — it's free, and free is hard to beat. Come here if Google won't verify you, or if you'd rather pay to skip the terminal.</p></div>
        <div class="call"><div class="h">disclosure</div><p>The Hostinger links on this page are referral links. It costs you nothing extra, and it's the same host the Google Cloud guide uses for domains.</p></div>
      </section>

      <!-- 00 -->
      <section id="need">
        <div class="part-h"><span class="num">00</span><h2>Before you start</h2></div>
        <p class="part-sub">Three things — and no cloud account to get approved.</p>
        <ul class="clean check">
          <li>A <strong>debit/credit card</strong> that works for online international payments.</li>
          <li>An <strong>email address</strong>.</li>
          <li>A computer with a browser. There's <strong>no software to install</strong>.</li>
        </ul>
        <p style="color:var(--muted)">No cloud verification, and you don't need to own a domain to get started.</p>
      </section>

      <!-- 01 -->
      <section id="buy">
        <div class="part-h"><span class="num">01</span><h2>Buy the VPS</h2></div>
        <p class="part-sub">A VPS is just a small server that's yours alone.</p>
        <ol class="steps">
          <li>Go to <a class="link" href="https://www.hostinger.com/vps-hosting?REFERRALCODE=1AJ9770" target="_blank" rel="noopener">hostinger.com/vps</a> and choose a <strong>KVM</strong> plan.</li>
          <li>Pick your billing term and check out.</li>
          <li>Hostinger asks a few setup questions after payment. If it offers to install an application, you can pick <strong>n8n</strong> there and skip Part 02. Not sure? Choose plain <strong>Ubuntu</strong> — Part 02 installs n8n properly either way.</li>
        </ol>

        <div class="tablewrap"><table>
          <thead><tr><th>Plan</th><th>Specs</th><th>Good for</th></tr></thead>
          <tbody>
            <tr><td><b>KVM 1</b></td><td>1 vCPU · 4 GB RAM · 50 GB</td><td><strong>Start here.</strong> Plenty for this course and normal automations.</td></tr>
            <tr><td><b>KVM 2</b></td><td>2 vCPU · 8 GB RAM · 100 GB</td><td>Heavier use, many workflows running at once.</td></tr>
          </tbody>
        </table></div>

        <div class="call warn"><div class="h">⚠ know what you're paying</div><p>At the time of writing Hostinger lists <strong>KVM 1 at $6.49/mo</strong> and <strong>KVM 2 at $8.79/mo</strong> — but those are promotional rates that need a <strong>long term paid upfront</strong>, and they <strong>renew higher</strong> ($11.99 and $14.99/mo respectively). Prices are in <strong>US dollars</strong>; your bank sets the naira rate. Check the total <em>and</em> the renewal price on the checkout page before paying — these numbers change.</p></div>

        <div class="call note"><div class="h">↳ free domain</div><p>VPS plans include a free domain for the first year. You don't need it for n8n to work — you get a free Hostinger web address either way — but it's worth claiming.</p></div>
      </section>

      <!-- 02 -->
      <section id="install">
        <div class="part-h"><span class="num">02</span><h2>Install n8n — the one-click bit</h2></div>
        <p class="part-sub">Hostinger ships a ready-made n8n template. You're picking it from a list.</p>
        <ol class="steps">
          <li>In <strong>hPanel</strong>, go to <strong>VPS</strong> in the left menu.</li>
          <li>Click <strong>Manage</strong> on your server.</li>
          <li>Open <strong>OS &amp; Panel → Operating System</strong>.</li>
          <li>In the template search box, type <code class="inl">n8n</code> and select the <strong>n8n</strong> template.</li>
          <li>Click <strong>Change OS</strong>.</li>
          <li>Tick the box acknowledging that <strong>all files will be deleted</strong>, then <strong>Next</strong>.</li>
          <li>Set a <strong>root password</strong> and <strong>save it in a password manager</strong> — you need it if you ever use the terminal (Parts 04 and 07).</li>
          <li>Click <strong>Confirm</strong>. A progress bar appears; it takes a couple of minutes.</li>
        </ol>
        <div class="call warn"><div class="h">⚠ "Change OS" wipes the server</div><p>On a brand-new VPS that's exactly what you want — there's nothing on it yet. <strong>Never</strong> run this on a server that already holds websites or data you care about.</p></div>
      </section>

      <!-- 03 -->
      <section id="live">
        <div class="part-h"><span class="num">03</span><h2>Go live</h2></div>
        <p class="part-sub">Open it, claim it, done.</p>
        <ol class="steps">
          <li>When the install finishes, go back to the <strong>VPS Overview</strong> page.</li>
          <li>Click <strong>Manage App</strong>. Your n8n opens in a new tab at an address like <code class="inl">https://n8n.srv123456.hstgr.cloud</code> (your own number). Note the <strong>padlock</strong> — HTTPS is already done. <strong>Write this address down.</strong></li>
          <li>n8n shows a <strong>"Set up owner account"</strong> screen. Enter your email and a strong password.</li>
        </ol>
        <div class="call check"><div class="h">✓ you're live</div><p>That's a real, private, always-on n8n — no trial timer, no monthly n8n subscription. <strong>This address is fully usable:</strong> webhooks work, the course projects work, nothing about it is second-class. Part 07 (your own domain) is optional polish.</p></div>
      </section>

      <!-- 04 -->
      <section id="run">
        <div class="part-h"><span class="num">04</span><h2>Keeping it running</h2></div>
        <p class="part-sub">Update it now and then, and keep a copy of your work.</p>
        <p>Everything here runs in hPanel's built-in terminal: <strong>VPS → Manage → Terminal</strong>. Log in as <code class="inl">root</code> with the password from Part 02.</p>

        <h3><span class="k">4.1</span> Update n8n (every few weeks)</h3>
        <div class="console cmd"><div class="chead"><span class="tag"><i></i> terminal</span><button class="copybtn">Copy</button></div>
<pre><code>cd $(dirname $(find /root /docker -name docker-compose.yml 2>/dev/null | head -1))
docker compose pull
docker compose down
docker compose up -d</code></pre></div>

        <h3><span class="k">4.2</span> Check it's running</h3>
        <div class="console cmd"><div class="chead"><span class="tag"><i></i> terminal</span><button class="copybtn">Copy</button></div>
<pre><code>docker ps</code></pre></div>

        <h3><span class="k">4.3</span> Back it up — two layers</h3>
        <ul class="clean">
          <li><strong>The server:</strong> hPanel → <strong>VPS → Manage → Backups &amp; Monitoring</strong>. Weekly backups are included. You can also take a <strong>snapshot</strong> before risky changes — but a snapshot is one slot only, replaced by the next one and expiring after about a day, so treat it as an undo button, not a backup.</li>
          <li><strong>Your workflows:</strong> inside n8n, use <strong>Download</strong> on a workflow to save its JSON. Keep the important ones in a folder on your computer. This is the copy that survives anything.</li>
        </ul>
      </section>

      <!-- 05 -->
      <section id="trouble">
        <div class="part-h"><span class="num">05</span><h2>Troubleshooting</h2></div>
        <p class="part-sub">The things that actually go wrong.</p>
        <div class="tablewrap"><table>
          <thead><tr><th>Problem</th><th>Fix</th></tr></thead>
          <tbody>
            <tr><td><strong>Manage App</strong> does nothing / n8n won't load</td><td>The template is probably still installing. Check VPS Overview says <strong>Running</strong>, wait 2–3 minutes, refresh.</td></tr>
            <tr><td>Forgot the <strong>root</strong> password</td><td>Reset it from the VPS management screen in hPanel, under the server's settings.</td></tr>
            <tr><td>Forgot your <strong>n8n</strong> login</td><td>No reset email — it's your own server. In the terminal, run <code class="inl">docker ps</code> to find the n8n container name, then <code class="inl">docker exec -it &lt;container&gt; n8n user-management:reset</code> and <code class="inl">docker restart &lt;container&gt;</code>. This clears <strong>accounts only</strong> — your workflows are untouched — and the next visit shows the setup screen again.</td></tr>
            <tr><td>Custom domain shows "not secure"</td><td>DNS hasn't spread yet. The certificate can only be issued once the domain resolves to your VPS — check with <code class="inl">dig +short n8n.yourdomain.com</code>, wait, retry.</td></tr>
            <tr><td>Webhooks still fire at the old address after a domain change</td><td><code class="inl">WEBHOOK_URL</code> wasn't updated, or the containers weren't restarted. Re-do Part 07 from 7.5.</td></tr>
            <tr><td>Anything hosting-level (billing, server won't boot)</td><td>Hostinger has 24/7 chat support in hPanel. You're paying for it — use it.</td></tr>
          </tbody>
        </table></div>
      </section>

      <!-- 06 -->
      <section id="cost">
        <div class="part-h"><span class="num">06</span><h2>What this cost you</h2></div>
        <div class="tablewrap"><table>
          <thead><tr><th>Item</th><th>Cost</th></tr></thead>
          <tbody>
            <tr><td>VPS (KVM 1, promotional rate)</td><td>from <b>$6.49 / month</b></td></tr>
            <tr><td>SSL certificate (automatic)</td><td><b>$0</b></td></tr>
            <tr><td>Web address (<code class="inl">…hstgr.cloud</code>)</td><td><b>$0</b></td></tr>
            <tr><td>Domain name</td><td>free for year 1 with the plan</td></tr>
            <tr><td>Your own production n8n</td><td><b>done ✓</b></td></tr>
          </tbody>
        </table></div>
        <p style="color:var(--muted)">No n8n subscription, no trial expiry, full control — you paid for convenience instead of an hour in a terminal. Keep it updated, keep your workflows backed up, and build.</p>
      </section>

      <!-- 07 -->
      <section id="domain">
        <div class="part-h"><span class="num">07</span><h2>Optional — use your own domain</h2></div>
        <p class="part-sub">Everything already works. Skip this if you're keen to start building.</p>
        <p>Come back when you'd rather have <code class="inl">n8n.yourdomain.com</code> than <code class="inl">n8n.srv123456.hstgr.cloud</code>. This part <strong>does</strong> need the terminal.</p>

        <h3><span class="k">7.1</span> Point the domain at your server</h3>
        <ol class="steps">
          <li>Find your VPS <strong>IP address</strong> on the hPanel VPS Overview page.</li>
          <li>At your domain registrar, open the <strong>DNS records</strong> for your domain and add — <strong>Type:</strong> <code class="inl">A</code>, <strong>Name:</strong> <code class="inl">n8n</code> (this makes <code class="inl">n8n.yourdomain.com</code>), <strong>Points to:</strong> your VPS IP, <strong>TTL:</strong> default.</li>
          <li>Save. DNS takes <strong>5 minutes to a couple of hours</strong> to spread.</li>
        </ol>

        <h3><span class="k">7.2</span> Wait until DNS is actually ready</h3>
        <p>Open <strong>VPS → Manage → Terminal</strong> and run this with your domain:</p>
        <div class="console cmd"><div class="chead"><span class="tag"><i></i> terminal</span><button class="copybtn">Copy</button></div>
<pre><code>dig +short n8n.yourdomain.com</code></pre></div>
        <div class="call warn"><div class="h">⚠ don't skip this</div><p><strong>Don't continue until that prints your VPS IP.</strong> The certificate can't be issued before it does.</p></div>

        <h3><span class="k">7.3</span> Find the config folder</h3>
        <p>Hostinger has used more than one location for this, so let the server find it:</p>
        <div class="console cmd"><div class="chead"><span class="tag"><i></i> terminal</span><button class="copybtn">Copy</button></div>
<pre><code>cd $(dirname $(find /root /docker -name docker-compose.yml 2>/dev/null | head -1))
pwd && ls</code></pre></div>
        <p>You should see <code class="inl">docker-compose.yml</code> and <code class="inl">.env</code>.</p>

        <h3><span class="k">7.4</span> Make a safety copy</h3>
        <div class="console cmd"><div class="chead"><span class="tag"><i></i> terminal</span><button class="copybtn">Copy</button></div>
<pre><code>cp docker-compose.yml docker-compose.yml.bak && cp .env .env.bak</code></pre></div>
        <p>If anything goes wrong, <code class="inl">cp docker-compose.yml.bak docker-compose.yml</code> puts it back.</p>

        <h3><span class="k">7.5</span> Set your domain</h3>
        <p>Type your subdomain once and the next commands reuse it. <strong>Change the address:</strong></p>
        <div class="console cmd"><div class="chead"><span class="tag"><i></i> terminal</span><button class="copybtn">Copy</button></div>
<pre><code>DOMAIN=n8n.yourdomain.com   <span class="cmt"># ← change to YOUR subdomain</span></code></pre></div>
        <div class="console cmd"><div class="chead"><span class="tag"><i></i> terminal</span><button class="copybtn">Copy</button></div>
<pre><code>echo "Using domain: $DOMAIN"</code></pre></div>
        <p>If that still says <code class="inl">n8n.yourdomain.com</code>, you didn't change it — run it again.</p>

        <h3><span class="k">7.6</span> Apply the change</h3>
        <p>Three edits. Paste each block exactly as it is:</p>
        <div class="console cmd"><div class="chead"><span class="tag"><i></i> terminal</span><button class="copybtn">Copy</button></div>
<pre><code>sed -i "s|^TRAEFIK_HOST=.*|TRAEFIK_HOST=$DOMAIN|" .env</code></pre></div>
        <div class="console cmd"><div class="chead"><span class="tag"><i></i> terminal</span><button class="copybtn">Copy</button></div>
<pre><code>sed -i 's|https://${COMPOSE_PROJECT_NAME}.${TRAEFIK_HOST}/|https://${TRAEFIK_HOST}/|' docker-compose.yml</code></pre></div>
        <div class="console cmd"><div class="chead"><span class="tag"><i></i> terminal</span><button class="copybtn">Copy</button></div>
<pre><code>sed -i 's|Host(`${COMPOSE_PROJECT_NAME}.${TRAEFIK_HOST}`)|Host(`${TRAEFIK_HOST}`)|' docker-compose.yml</code></pre></div>
        <p>Check they landed:</p>
        <div class="console cmd"><div class="chead"><span class="tag"><i></i> terminal</span><button class="copybtn">Copy</button></div>
<pre><code>grep TRAEFIK_HOST .env && grep -n "WEBHOOK_URL\|routers" docker-compose.yml</code></pre></div>
        <p>You want <code class="inl">TRAEFIK_HOST=n8n.yourdomain.com</code>, a <code class="inl">WEBHOOK_URL</code> of <code class="inl">https://${TRAEFIK_HOST}/</code>, and a router rule of <code class="inl">Host(`${TRAEFIK_HOST}`)</code> — none of them should still mention <code class="inl">${COMPOSE_PROJECT_NAME}</code>.</p>

        <h3><span class="k">7.7</span> Restart</h3>
        <div class="console cmd"><div class="chead"><span class="tag"><i></i> terminal</span><button class="copybtn">Copy</button></div>
<pre><code>docker compose down && docker compose up -d</code></pre></div>
        <p>Give it a minute to fetch the certificate, then open <strong><code class="inl">https://n8n.yourdomain.com</code></strong>. Padlock, your workflows, your domain.</p>
        <div class="call note"><div class="h">↳ heads up</div><p>Your workflows are safe — this changes the address, not the data. But any webhook URL you've already pasted into another service still points at the old address, so update those.</p></div>
      </section>

      <div class="foot">AJBuildAI · self-hosting guide · n8n on Hostinger</div>
    </div>
  </main>
</div>

@include('guides.partials.chrome-js')
</body>
</html>
