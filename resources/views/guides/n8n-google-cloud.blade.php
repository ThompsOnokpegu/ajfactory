<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="A copy-paste, beginner-proof guide to self-hosting n8n on Google Cloud's free tier — from the AI Automation Accelerator by AJBuildAI.">
<title>Set up n8n on Google Cloud — the free-hosting guide</title>

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
    <a href="#watch"><span class="n">▶</span> Watch it first</a>
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
        <h1>Your own n8n. Live. <em>$0 a month.</em></h1>
        <p class="lede">A private n8n on your own web address, on Google Cloud's Always-Free server, with a real HTTPS padlock. You copy and paste, exactly as written — every step spelled out, nothing assumed, nothing skipped.</p>
        <div class="meta">
          <span class="chip">⏱ <b>45–60 min</b> first time</span>
          <span class="chip">💳 hosting <b>$0/mo</b></span>
          <span class="chip">🌐 domain ~<b>$6-11/yr</b></span>
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

      @php
        // Resolved from config/curriculum.php rather than hardcoded, so a re-record
        // that changes the lesson's video_id reaches this page automatically. The
        // lesson inherits its module's library_id, falling back to the Bunny default,
        // exactly as the dashboard player does.
        $walkthrough = collect(config('curriculum.core', []))
          ->flatMap(fn ($m) => collect($m['videos'] ?? [])
            ->map(fn ($v) => $v + ['library_id' => $v['library_id'] ?? ($m['library_id'] ?? null)]))
          ->firstWhere('id', 'module-02-v5');

        $libraryId = $walkthrough['library_id'] ?? config('services.bunny.library_id');
        // No id, no library, no embed — render nothing rather than a broken player.
        $watchUrl = ($walkthrough && ! empty($walkthrough['video_id']) && $libraryId)
          ? "https://iframe.mediadelivery.net/embed/{$libraryId}/{$walkthrough['video_id']}?autoplay=false&loop=false&muted=false&preload=false&responsive=true"
          : null;
      @endphp

      @if($watchUrl)
      <section id="watch">
        <div class="part-h"><span class="num">▶</span><h2>Watch it first (optional)</h2></div>
        <p class="part-sub">The same build, start to finish, so you know what's coming.</p>
        <div class="videobox">
          <div class="frame">
            <iframe src="{{ $watchUrl }}" loading="lazy" allow="accelerometer;gyroscope;encrypted-media;picture-in-picture;fullscreen" allowfullscreen title="{{ $walkthrough['title'] }}"></iframe>
          </div>
          <div class="cap">
            <span><b>{{ $walkthrough['title'] }}</b></span>
            @if(!empty($walkthrough['duration']))<span>⏱ {{ $walkthrough['duration'] }}</span>@endif
            <span>Accelerator · Module 02</span>
          </div>
        </div>
        <div class="call note"><div class="h">↳ how to use both</div><p>Watch once to see it happen, then <strong>follow the written steps below</strong> at your own pace — copying and pasting beats retyping from a video, which is where mistakes creep in. You only need <em>one</em> hosting route: this one, or <a class="link" href="/guides/n8n-on-hostinger">the Hostinger route</a> if Google won't verify your account.</p></div>
      </section>
      @endif

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
          <li>A <strong>debit/credit card</strong> — Google requires one to verify you're human. On the Always-Free server <strong>you won't be charged</strong>; we also set a $1 budget alert as a safety net in Part 02.</li>
          <li>About <strong>$6-11</strong> for a domain name (yearly).</li>
          <li>A computer with a browser — Windows, Mac, or Linux. Everything happens in the browser.</li>
        </ul>
      </section>

      <!-- 01 -->
      <section id="domain">
        <div class="part-h"><span class="num">01</span><h2>Get a domain name</h2></div>
        <p class="part-sub">Your web address. Any registrar works — <strong>Hostinger</strong> is used here.</p>
        <ol class="steps">
          <li>Go to <a class="link" href="https://www.hostinger.com?REFERRALCODE=1AJ9770" target="_blank" rel="noopener">hostinger.com</a> and search for a domain (e.g. <code class="inl">yourbrand.com</code>).</li>
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

        <div class="call warn"><div class="h">⚠ stuck at verification?</div><p>This is where people get blocked — Google rejects some cards and won't always say why. Don't lose a week to it. There's a <a class="link" href="/guides/n8n-on-hostinger">one-click Hostinger route</a> that gets you the same private n8n with automatic HTTPS in about ten minutes. It costs money (this one is free hosting), but a working n8n beats a free one you can't create.</p></div>

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
        <p class="part-sub">Back in Hostinger (<strong>hPanel</strong>).</p>
        <ol class="steps">
          <li>Go to <strong>Domains</strong> and click <strong>Manage</strong> on your domain.</li>
          <li>Open <strong>DNS / Nameservers → DNS records</strong>. Under <strong>Add new record</strong>, set:</li>
        </ol>
        <div class="tablewrap"><table>
          <thead><tr><th>Field</th><th>Value</th></tr></thead>
          <tbody>
            <tr><td>Type</td><td><code class="inl">A</code></td></tr>
            <tr><td>Name</td><td><code class="inl">n8n</code> &nbsp;<span style="color:var(--muted)">← makes <code class="inl">n8n.yourdomain.com</code></span></td></tr>
            <tr><td>Points to</td><td>your static IP from Part 2.5 (e.g. <code class="inl">34.121.x.x</code>)</td></tr>
            <tr><td>TTL</td><td>leave the default</td></tr>
          </tbody>
        </table></div>
        <ol class="steps" start="3" style="counter-reset:s 2">
          <li>Click <strong>Add record</strong>.</li>
          <li>If there's already an <code class="inl">A</code> record for the <code class="inl">n8n</code> name (or a conflicting one), delete it — keep your new one.</li>
        </ol>
        <div class="call note"><div class="h">↳ using Hostinger's nameservers?</div><p>DNS records only work if the domain uses <strong>Hostinger nameservers</strong> (the default when you buy the domain at Hostinger). If you pointed it elsewhere, add the A record there instead.</p></div>
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
        <div class="call note"><div class="h">↳ still free?</div><p>Google Cloud → <strong>Billing</strong>. As long as it's the <code class="inl">e2-micro</code> in a free region with a 30 GB standard disk, hosting stays $0. Watch only for large <strong>network egress</strong> — normal automation traffic is tiny.</p></div>
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
            <tr><td>Google Cloud server (e2-micro, free region)</td><td><b>$0 / month</b></td></tr>
            <tr><td>SSL certificate (Caddy + Let's Encrypt)</td><td><b>$0</b></td></tr>
            <tr><td>Domain name</td><td>~$6-11 / year</td></tr>
            <tr><td>Your own production n8n</td><td><b>done ✓</b></td></tr>
          </tbody>
        </table></div>
        <p style="color:var(--muted)">You now own your automation infrastructure — no monthly n8n subscription, no trial expiry, full control. Back it up, keep it updated, and build.</p>
      </div>

      <div class="foot">AJBuildAI · self-hosting guide · n8n on Google Cloud</div>
    </div>
  </main>
</div>

@include('guides.partials.chrome-js')
</body>
</html>
