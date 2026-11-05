<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
@include('partials.icons')
<meta name="description" content="Every step and command to self-host n8n on Google Cloud's free tier, on your own domain with HTTPS.">
<title>n8n on Google Cloud - every command</title>

@include('guides.partials.chrome-css')
{{-- Public page, so unlike the paid guides it carries the pixel. --}}
@include('partials.meta-pixel')
</head>
<body>

<div id="progress"></div>

<header class="topbar">
  <a href="/" class="brand" style="text-decoration:none;color:inherit">AJBUILDS<b> AI</b></a>
  <button class="themetoggle" id="tt" aria-label="Toggle colour theme">◐ Theme</button>
</header>

<div class="wrap">
  <nav class="toc" aria-label="Contents">
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
        <span class="eyebrow">n8n on Google Cloud</span>
        <h1>Every step. <em>Every command.</em></h1>
        <p class="lede">Your own n8n on your own domain, with HTTPS, on Google Cloud's free tier. Work from top to bottom and paste each command exactly as written.</p>
      </div>

      <!-- 00 -->
      <section id="start">
        <div class="part-h"><span class="num">00</span><h2>Before you start</h2></div>
        <ul class="clean check">
          <li>A <strong>Google account</strong>.</li>
          <li>A <strong>bank card</strong>. Depending on your card and country, Google may ask for a one-time prepayment ($30 in Nigeria).</li>
          <li>About <strong>$6-11 a year</strong> for a domain.</li>
          <li>A computer with a browser.</li>
        </ul>
      </section>

      <!-- 01 -->
      <section id="domain">
        <div class="part-h"><span class="num">01</span><h2>Get a domain name</h2></div>
        <p class="part-sub">Any registrar works. I use Hostinger.</p>
        <ol class="steps">
          <li>Go to <a class="link" href="https://www.hostinger.com?REFERRALCODE=1AJ9770" target="_blank" rel="noopener sponsored">hostinger.com</a> and search for a domain.</li>
          <li>Add it to the cart, check out and pay.</li>
        </ol>
        <p style="color:var(--muted);font-size:.85rem">This is an affiliate link. If you buy through it, I may earn a commission at no extra cost to you.</p>
      </section>

      <!-- 02 -->
      <section id="server">
        <div class="part-h"><span class="num">02</span><h2>Create the server</h2></div>
        <p class="part-sub">Google renames menus often. If a button has moved, look for the same word nearby.</p>

        <h3><span class="k">2.1</span> Create a Google Cloud account</h3>
        <ol class="steps">
          <li>Go to <a class="link" href="https://console.cloud.google.com" target="_blank" rel="noopener">console.cloud.google.com</a> and sign in.</li>
          <li>Accept the terms and click <strong>Start free / Activate</strong>.</li>
          <li>Enter your details and card, and choose <strong>Individual</strong>. Pay the prepayment if Google asks for it.</li>
        </ol>

        <h3><span class="k">2.2</span> Create a project</h3>
        <ol class="steps">
          <li>Top-left, click the <strong>project dropdown</strong>.</li>
          <li><strong>New Project</strong> → name it <code class="inl">n8n-automation</code> → <strong>Create</strong>.</li>
          <li>Make sure the dropdown shows your <strong>n8n-automation</strong> project.</li>
        </ol>

        <h3><span class="k">2.3</span> Set a budget alert</h3>
        <ol class="steps">
          <li>In the top search bar, open <strong>Budgets &amp; alerts</strong>.</li>
          <li><strong>Create budget</strong> → name it <code class="inl">zero</code> → set <strong>Target amount</strong> to <strong>$1</strong> → alert at 50% and 100% → <strong>Finish</strong>.</li>
        </ol>

        <h3><span class="k">2.4</span> Create the server (VM)</h3>
        <ol class="steps">
          <li>Search <strong>VM instances</strong> and open <strong>Compute Engine → VM instances</strong>. If asked, click <strong>Enable</strong> and wait about a minute.</li>
          <li>Click <strong>Create instance</strong> and match these exactly. They keep it free.</li>
        </ol>
        <ul class="clean">
          <li><strong>Name:</strong> <code class="inl">n8n-server</code></li>
          <li><strong>Region:</strong> one of <code class="inl">us-west1</code>, <code class="inl">us-central1</code> or <code class="inl">us-east1</code></li>
          <li><strong>Series</strong> <code class="inl">E2</code> → <strong>Machine type</strong> <code class="inl">e2-micro</code></li>
          <li><strong>Boot disk → Change:</strong> Ubuntu → <code class="inl">Ubuntu 22.04 LTS Minimal</code> → <strong>Standard persistent disk</strong> → <strong>30 GB</strong> → Select</li>
          <li><strong>Firewall:</strong> tick both ✅ Allow HTTP and ✅ Allow HTTPS</li>
        </ul>
        <ol class="steps" start="3" style="counter-reset:s 2">
          <li>Click <strong>Create</strong> and wait for the <strong>External IP</strong> to appear.</li>
        </ol>

        <h3><span class="k">2.5</span> Make the IP permanent</h3>
        <ol class="steps">
          <li>Search <strong>IP addresses</strong> and open <strong>VPC network → IP addresses</strong>.</li>
          <li>On the <code class="inl">n8n-server</code> row, choose <strong>Promote to static</strong> (or "Reserve"), name it <code class="inl">n8n-ip</code> → <strong>Reserve</strong>.</li>
          <li>Write down this IP. You need it next.</li>
        </ol>
      </section>

      <!-- 03 -->
      <section id="dns">
        <div class="part-h"><span class="num">03</span><h2>Point your domain at the server</h2></div>
        <p class="part-sub">In Hostinger (<strong>hPanel</strong>). On another registrar, add the same record there.</p>
        <ol class="steps">
          <li>Go to <strong>Domains</strong> and click <strong>Manage</strong> on your domain.</li>
          <li>Open <strong>DNS / Nameservers → DNS records</strong>. Under <strong>Add new record</strong>, set:</li>
        </ol>
        <div class="tablewrap"><table>
          <thead><tr><th>Field</th><th>Value</th></tr></thead>
          <tbody>
            <tr><td>Type</td><td><code class="inl">A</code></td></tr>
            <tr><td>Name</td><td><code class="inl">n8n</code> &nbsp;<span style="color:var(--muted)">← makes <code class="inl">n8n.yourdomain.com</code></span></td></tr>
            <tr><td>Points to</td><td>your static IP from 2.5</td></tr>
            <tr><td>TTL</td><td>leave the default</td></tr>
          </tbody>
        </table></div>
        <ol class="steps" start="3" style="counter-reset:s 2">
          <li>Click <strong>Add record</strong>.</li>
          <li>If another <code class="inl">A</code> record already uses the <code class="inl">n8n</code> name, delete it and keep your new one.</li>
        </ol>
        <p>DNS takes 5 minutes to a couple of hours. Carry on meanwhile. You check it in 7.1.</p>
      </section>

      <!-- 04 -->
      <section id="connect">
        <div class="part-h"><span class="num">04</span><h2>Connect to your server</h2></div>
        <ol class="steps">
          <li>Google Cloud → <strong>Compute Engine → VM instances</strong>.</li>
          <li>On the <code class="inl">n8n-server</code> row, click <strong>SSH</strong>. If asked, click <strong>Authorize</strong>. A black terminal window opens.</li>
        </ol>
        <p>Paste with right-click → Paste, or <kbd>Ctrl</kbd>+<kbd>V</kbd>, then press <kbd>Enter</kbd>. Test it:</p>
        <div class="console cmd"><div class="chead"><span class="tag"><i></i> terminal</span><button class="copybtn">Copy</button></div>
<pre><code>whoami &amp;&amp; echo "Connected 🎉"</code></pre></div>
        <p>You should see your username and <code class="inl">Connected 🎉</code>.</p>
        <div class="call warn"><div class="h">⚠ if sudo fails with a strange error</div><p>A new server takes a minute or two to finish setting up. Close the SSH window, wait 30 seconds, click <strong>SSH</strong> again and re-run the command.</p></div>
      </section>

      <!-- 05 -->
      <section id="prep">
        <div class="part-h"><span class="num">05</span><h2>Prepare the server</h2></div>
        <p class="part-sub">Run each block on its own. Wait for the prompt to come back before the next.</p>

        <h3><span class="k">5.1</span> Update the server</h3>
        <div class="console cmd"><div class="chead"><span class="tag"><i></i> terminal</span><button class="copybtn">Copy</button></div>
<pre><code>sudo apt-get update &amp;&amp; sudo apt-get upgrade -y</code></pre></div>
        <p>If it asks a yes/no question, press <kbd>Enter</kbd>. If it lists services and asks which to restart, type the number for <strong>none of the above</strong>, then press <kbd>Enter</kbd>.</p>

        <h3><span class="k">5.2</span> Add swap memory</h3>
        <div class="console cmd"><div class="chead"><span class="tag"><i></i> terminal</span><button class="copybtn">Copy</button></div>
<pre><code>sudo fallocate -l 2G /swapfile
sudo chmod 600 /swapfile
sudo mkswap /swapfile
sudo swapon /swapfile
echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab</code></pre></div>
        <p>Check it. The <code class="inl">Swap:</code> line should show <code class="inl">2.0Gi</code>.</p>
        <div class="console cmd"><div class="chead"><span class="tag"><i></i> terminal</span><button class="copybtn">Copy</button></div>
<pre><code>free -h</code></pre></div>

        <h3><span class="k">5.3</span> Install Docker</h3>
        <div class="console cmd"><div class="chead"><span class="tag"><i></i> terminal</span><button class="copybtn">Copy</button></div>
<pre><code>curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh</code></pre></div>
        <p>Check it. Both should print a version number.</p>
        <div class="console cmd"><div class="chead"><span class="tag"><i></i> terminal</span><button class="copybtn">Copy</button></div>
<pre><code>sudo docker --version &amp;&amp; sudo docker compose version</code></pre></div>
      </section>

      <!-- 06 -->
      <section id="install">
        <div class="part-h"><span class="num">06</span><h2>Install n8n with automatic HTTPS</h2></div>

        <h3><span class="k">6.1</span> Make a folder</h3>
        <div class="console cmd"><div class="chead"><span class="tag"><i></i> terminal</span><button class="copybtn">Copy</button></div>
<pre><code>mkdir ~/n8n &amp;&amp; cd ~/n8n</code></pre></div>

        <h3><span class="k">6.2</span> Set your domain</h3>
        <p><strong>Change the address</strong> to your subdomain, then press <kbd>Enter</kbd>:</p>
        <div class="console cmd"><div class="chead"><span class="tag"><i></i> terminal</span><button class="copybtn">Copy</button></div>
<pre><code>DOMAIN=n8n.yourdomain.com   <span class="cmt"># ← change to YOUR subdomain</span></code></pre></div>
        <p>Check it. This should print your real subdomain.</p>
        <div class="console cmd"><div class="chead"><span class="tag"><i></i> terminal</span><button class="copybtn">Copy</button></div>
<pre><code>echo "Using domain: $DOMAIN"</code></pre></div>

        <h3><span class="k">6.3</span> Settings file (<code class="inl">.env</code>)</h3>
        <p>This generates your encryption key and writes the file:</p>
        <div class="console cmd"><div class="chead"><span class="tag"><i></i> terminal</span><button class="copybtn">Copy</button></div>
<pre><code>cat &gt; .env &lt;&lt;EOF
DOMAIN=$DOMAIN
N8N_ENCRYPTION_KEY=$(openssl rand -hex 24)
EOF</code></pre></div>
        <p>Show the file, then save the <code class="inl">N8N_ENCRYPTION_KEY=…</code> line in a password manager:</p>
        <div class="console cmd"><div class="chead"><span class="tag"><i></i> terminal</span><button class="copybtn">Copy</button></div>
<pre><code>cat .env</code></pre></div>
        <div class="call warn"><div class="h">⚠ save this</div><p>Without this key, a restored backup can't recover your saved credentials.</p></div>

        <h3><span class="k">6.4</span> Web-server file (<code class="inl">Caddyfile</code>)</h3>
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
        <p>Not in Lagos? Put your timezone in place of <code class="inl">America/New_York</code>, then press <kbd>Enter</kbd>.</p>
        <div class="console cmd"><div class="chead"><span class="tag"><i></i> terminal</span><button class="copybtn">Copy</button></div>
<pre><code>sed -i 's#Africa/Lagos#America/New_York#' docker-compose.yml</code></pre></div>
        <p>Check it. You should see <code class="inl">.env</code>, <code class="inl">Caddyfile</code> and <code class="inl">docker-compose.yml</code>.</p>
        <div class="console cmd"><div class="chead"><span class="tag"><i></i> terminal</span><button class="copybtn">Copy</button></div>
<pre><code>ls -a</code></pre></div>
      </section>

      <!-- 07 -->
      <section id="golive">
        <div class="part-h"><span class="num">07</span><h2>Go live</h2></div>

        <h3><span class="k">7.1</span> Check the domain points here</h3>
        <p>Use your own subdomain:</p>
        <div class="console cmd"><div class="chead"><span class="tag"><i></i> terminal</span><button class="copybtn">Copy</button></div>
<pre><code>dig +short n8n.yourdomain.com</code></pre></div>
        <p>If it says <code class="inl">dig: command not found</code>, install it, then run the check again. If the restart list comes up, choose <strong>none of the above</strong>.</p>
        <div class="console cmd"><div class="chead"><span class="tag"><i></i> terminal</span><button class="copybtn">Copy</button></div>
<pre><code>sudo apt update &amp;&amp; sudo apt install -y dnsutils</code></pre></div>
        <div class="call warn"><div class="h">⚠ must match first</div><p>It should print <strong>your static IP</strong>. If it doesn't, wait 10-15 minutes and try again. <strong>Don't start n8n until it matches</strong>, or Caddy can't get the certificate.</p></div>

        <h3><span class="k">7.2</span> Start everything</h3>
        <div class="console cmd"><div class="chead"><span class="tag"><i></i> terminal</span><button class="copybtn">Copy</button></div>
<pre><code>sudo docker compose up -d</code></pre></div>
        <p>The first run takes 1-2 minutes. To watch the certificate arrive (optional):</p>
        <div class="console cmd"><div class="chead"><span class="tag"><i></i> terminal</span><button class="copybtn">Copy</button></div>
<pre><code>sudo docker compose logs -f caddy</code></pre></div>
        <p>Look for <code class="inl">certificate obtained</code>, then press <kbd>Ctrl</kbd>+<kbd>C</kbd>. That doesn't stop the server.</p>

        <h3><span class="k">7.3</span> Open your n8n</h3>
        <ol class="steps">
          <li>In your normal browser, go to <code class="inl">https://n8n.yourdomain.com</code>.</li>
          <li>On the <strong>Set up owner account</strong> screen, enter your email and a strong password. That's your n8n login.</li>
        </ol>
        <p>A security warning means Caddy is still fetching the certificate. Wait a minute and refresh.</p>
      </section>

      <!-- 08 -->
      <section id="run">
        <div class="part-h"><span class="num">08</span><h2>Keep it running</h2></div>
        <p class="part-sub">Run <code class="inl">cd ~/n8n</code> first.</p>

        <div class="console cmd"><div class="chead"><span class="tag"><i></i> see it's running</span><button class="copybtn">Copy</button></div>
<pre><code>sudo docker compose ps</code></pre></div>
        <div class="console cmd"><div class="chead"><span class="tag"><i></i> update, every few weeks</span><button class="copybtn">Copy</button></div>
<pre><code>sudo docker compose pull &amp;&amp; sudo docker compose up -d</code></pre></div>
        <div class="console cmd"><div class="chead"><span class="tag"><i></i> restart</span><button class="copybtn">Copy</button></div>
<pre><code>sudo docker compose restart</code></pre></div>
        <div class="console cmd"><div class="chead"><span class="tag"><i></i> stop</span><button class="copybtn">Copy</button></div>
<pre><code>sudo docker compose down</code></pre></div>
        <div class="console cmd"><div class="chead"><span class="tag"><i></i> start</span><button class="copybtn">Copy</button></div>
<pre><code>sudo docker compose up -d</code></pre></div>
        <div class="console cmd"><div class="chead"><span class="tag"><i></i> back up, before any update</span><button class="copybtn">Copy</button></div>
<pre><code>sudo docker run --rm -v n8n_n8n_data:/data -v ~:/backup alpine tar czf /backup/n8n-backup-$(date +%F).tar.gz -C /data .</code></pre></div>
        <p>Then download the <code class="inl">.tar.gz</code> from the SSH window (top-right <strong>⚙ → Download file</strong>).</p>
      </section>

      <!-- 09 -->
      <section id="trouble">
        <div class="part-h"><span class="num">09</span><h2>Troubleshooting</h2></div>
        <div class="tablewrap"><table>
          <thead><tr><th>Problem</th><th>Fix</th></tr></thead>
          <tbody>
            <tr><td>Site won't load</td><td>Re-check 7.1: <code class="inl">dig +short …</code> must equal your static IP. Confirm HTTP and HTTPS were ticked in 2.4.</td></tr>
            <tr><td>Certificate error</td><td>Caddy needs correct DNS and ports 80/443 open. Wait 2-3 minutes and refresh. Check <code class="inl">sudo docker compose logs caddy</code>.</td></tr>
            <tr><td>n8n crashes or restarts</td><td>Out of memory. <code class="inl">free -h</code> must show 2 Gi of swap. Re-do 5.2 if not.</td></tr>
            <tr><td><code class="inl">docker: command not found</code></td><td>Re-run 5.3. Always use <code class="inl">sudo docker …</code>.</td></tr>
            <tr><td>Forgot the encryption key</td><td>Run <code class="inl">cat ~/n8n/.env</code>.</td></tr>
            <tr><td>Webhooks don't arrive</td><td><code class="inl">DOMAIN</code> in <code class="inl">~/n8n/.env</code> must be your real subdomain. Fix it, then run <code class="inl">sudo docker compose up -d</code>.</td></tr>
          </tbody>
        </table></div>
      </section>

      <div class="foot">AJBuilds AI · n8n on Google Cloud</div>
    </div>
  </main>
</div>

@include('guides.partials.chrome-js')
</body>
</html>
