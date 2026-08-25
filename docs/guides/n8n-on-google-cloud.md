# Set up your own n8n on Google Cloud — free hosting, step by step

This gets you a **private n8n running on your own web address** (like
`https://n8n.yourdomain.com`), hosted on Google Cloud's **Always Free** server,
with a proper **padlock (HTTPS)** — for **$0/month** in hosting. The only real
cost is a domain name (~$6-11/year).

You do **not** need to know how to code. You'll copy and paste commands exactly as
written. Every step is spelled out — nothing assumed, nothing skipped.

> **Time:** about 45–60 minutes the first time.
> **You'll switch between three websites:** your domain registrar, the Google
> Cloud Console, and a black "terminal" window on your server.

> ⚠️ **Screens change.** Google Cloud and domain sites tweak their menus often. If a
> button isn't *exactly* where this says, look for the same *word* nearby — the
> steps and commands themselves don't change.

---

## Watch it first (optional)

There's a **video walkthrough of this exact build** in the Accelerator, Module 02:
*Self-Hosting n8n on Google Cloud (Walkthrough)* (23:54). The live page embeds it at
the top; here it's the same lesson in `config/curriculum.php` under lesson id
`module-02-v5`.

Watch it once to see the whole thing happen, then **follow the written steps below** at
your own pace. Copying and pasting beats retyping from a video, which is where the
mistakes creep in.

You only need **one** hosting route: this one, or the
[Hostinger route](n8n-on-hostinger.md) if Google won't verify your account.

> The page reads the video id from `config/curriculum.php` rather than hardcoding it, so
> a re-record reaches the guide automatically. If the lesson id `module-02-v5` is ever
> renamed, the embed silently disappears — the page renders without it rather than
> showing a broken player.

---

## What you'll end up with

```
Your domain (n8n.yourdomain.com)
        │   (DNS points it to…)
        ▼
Google Cloud server (free e2-micro)
        │
        ├─ Caddy  → gets a free SSL certificate + forwards traffic
        └─ n8n    → your automation tool, running in Docker
```

You don't need to understand that diagram — the steps build it for you.

---

## Part 0 — What you need before you start

- [ ] A **Google account** (a normal Gmail is fine).
- [ ] A **debit/credit card** — Google requires one to verify you're human. On the
      Always-Free server **you will not be charged**; we'll also set a $1 budget
      alert as a safety net (Part 2).
- [ ] About **$6-11** for a domain name (yearly).
- [ ] A computer (Windows, Mac, or Linux). Everything is done in a browser.

---

## Part 1 — Get a domain name

A domain is your web address. You can use any registrar; **Hostinger** is used here.

1. Go to **[hostinger.com](https://www.hostinger.com?REFERRALCODE=1AJ9770)** and search
   for a domain you like (e.g. `yourbrand.com`).
2. Add it to the cart and check out. Turn on **free domain privacy** if offered (hides
   your personal details) — it's usually free.
3. Pay. You now own the domain. **Keep this browser tab open** — you'll come back to
   it in Part 3.

> You'll actually use a **subdomain** — `n8n.yourdomain.com` — so the main domain
> stays free for a website later. You don't set that up now; you'll add it in Part 3.

---

## Part 2 — Create your free Google Cloud server

### 2.1 — Create a Google Cloud account

1. Go to **console.cloud.google.com** and sign in with your Google account.
2. If it's your first time, accept the terms and click **Start free / Activate**.
3. Enter your details and the **card** for verification. Choose **Individual** account
   type. Google gives new users free credits too, but our server stays in the
   **Always Free** tier regardless.

> ⚠️ **Stuck at verification?** This is where people get blocked — Google rejects some
> cards and won't always say why. Don't lose a week to it: the
> [one-click Hostinger route](/guides/n8n-on-hostinger) gets you the same private n8n
> with automatic HTTPS in about ten minutes. It costs money (this route is free
> hosting), but a working n8n beats a free one you can't create.

### 2.2 — Create a project

A "project" is just a folder for your stuff.

1. Top-left, click the **project dropdown** (it may say "My First Project").
2. Click **New Project** → name it `n8n` → **Create**.
3. Make sure the dropdown now shows your **n8n** project selected.

### 2.3 — Set a budget alert (safety net)

So you're warned the instant anything tries to cost money.

1. In the top search bar, type **Budgets & alerts** and open it.
2. **Create budget** → name it `zero` → set **Target amount** to a small number like
   **$1** → set alert thresholds at 50% and 100% → **Finish**.
3. You'll now get an email if spending ever approaches $1. (It shouldn't.)

### 2.4 — Create the server (VM)

1. In the top search bar, type **VM instances** and open **Compute Engine → VM
   instances**. If prompted, click **Enable** (Compute Engine API) and wait ~1 min.
2. Click **Create instance**. Fill it in **exactly** like this — these settings are
   what keep it free:
   - **Name:** `n8n-server`
   - **Region:** pick **one** of these free regions — `us-west1`, `us-central1`, or
     `us-east1`. (Only these qualify for the free e2-micro.)
   - **Zone:** leave the default.
   - **Machine configuration → Series:** `E2` → **Machine type:** `e2-micro`
     (0.25–2 vCPU, **1 GB memory**). This is the free one.
   - **Boot disk:** click **Change** → **Operating system:** Ubuntu →
     **Version:** `Ubuntu 22.04 LTS` → **Boot disk type:** `Standard persistent
     disk` → **Size:** `30` GB (the free limit) → **Select**.
   - **Firewall:** tick **both** ✅ **Allow HTTP traffic** and ✅ **Allow HTTPS
     traffic**.
3. Click **Create**. After ~30 seconds you'll see your instance with an **External
   IP** (e.g. `34.121.x.x`). Leave this tab open.

### 2.5 — Make the IP address permanent (static)

By default the IP can change on reboot, which would break your domain. Lock it.

1. Search **IP addresses** → open **VPC network → IP addresses**.
2. Find the row for `n8n-server`'s external IP. In its **⋮** menu (or the "Type"
   column), choose **Promote to static** (a.k.a. "Reserve"). Give it a name like
   `n8n-ip` → **Reserve**.
3. **Write down this IP address.** You need it in the next part.

---

## Part 3 — Point your domain at the server

Back in Hostinger (**hPanel**):

1. Go to **Domains** and click **Manage** on your domain.
2. Open **DNS / Nameservers → DNS records**. Under **Add new record**, enter:
   - **Type:** `A`
   - **Name:** `n8n`   ← this makes `n8n.yourdomain.com`
   - **Points to:** *your static IP from Part 2.5* (e.g. `34.121.x.x`)
   - **TTL:** leave the default
3. Click **Add record**.
4. If there's already an `A` record for the `n8n` name (or any conflicting record),
   delete it — leave your new one.

> DNS records only take effect if the domain is using **Hostinger's nameservers**
> (the default when you buy the domain there). If you've pointed the domain
> elsewhere, add the `A` record at that provider instead.

> DNS can take anywhere from **5 minutes to a couple of hours** to spread. You can
> continue with the next parts while it does; we'll check it in Part 7.

---

## Part 4 — Connect to your server

1. Back in Google Cloud → **Compute Engine → VM instances**.
2. On the `n8n-server` row, click the **SSH** button (under "Connect"). A **black
   terminal window** opens in your browser — this is your server's command line.
3. If it asks to authorize, click **Authorize**. You're now "inside" your server.

Everything from here is **copy → paste into this black window → press Enter**. To
paste in the Google SSH window: right-click → Paste, or `Ctrl+V`.

Test you're connected — paste this and press Enter:

```bash
whoami && echo "Connected 🎉"
```

You should see your username and `Connected 🎉`.

> ⚠️ **Fresh-server hiccup:** for the first minute or two after a brand-new server
> boots, a command starting with `sudo` can fail with a strange permissions error
> (people have seen everything up to a cheeky *"I'm sorry, I'm afraid I can't do
> that"*). It just means the server is still finishing setup and hasn't granted you
> admin rights yet. **Fix:** close the black SSH window, wait ~30 seconds, click
> **SSH** again to reopen it, and re-run the command. It'll work.

---

## Part 5 — Prepare the server

Run each block one at a time. Wait for the prompt to come back before the next.

### 5.1 — Update the server

```bash
sudo apt-get update && sudo apt-get upgrade -y
```

If it asks any yes/no question, press Enter to accept the default.

### 5.2 — Add swap memory (important — 1 GB RAM is tight)

n8n can run out of memory on the tiny free server and crash. Swap prevents that.

```bash
sudo fallocate -l 2G /swapfile
sudo chmod 600 /swapfile
sudo mkswap /swapfile
sudo swapon /swapfile
echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab
```

Verify — this should show a `Swap:` line with `2.0Gi`:

```bash
free -h
```

### 5.3 — Install Docker

Docker runs n8n in a tidy, self-contained box. We use **Docker's official one-line
installer** — it's the simplest and always up to date. Paste these two lines:

```bash
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh
```

That downloads Docker's installer and runs it. It takes ~1–2 minutes and installs
both Docker and the Compose plugin for you.

Verify — this should print two version numbers:

```bash
sudo docker --version && sudo docker compose version
```

> If both print a version, you're done — skip ahead. (If you already use a Docker
> install method you trust, that's fine too; just make sure `docker compose version`
> works, since we need the built-in **Compose plugin**, not the old `docker-compose`.)

---

## Part 6 — Install n8n with automatic HTTPS

You'll create a folder with **three small files**, then start everything with one
command. Caddy will fetch a free SSL certificate for you automatically.

### 6.1 — Make a folder

```bash
mkdir ~/n8n && cd ~/n8n
```

> **We won't use a text editor here.** Each file below is created with a single
> "paste this whole block" command, so there's no editing and nothing to get wrong.
> (Prefer to edit by hand? If you type `nano` and get `nano: command not found`,
> install it first with `sudo apt-get install -y nano`.)

### 6.2 — Remember your domain

Type your subdomain here **once** and the next commands reuse it. **Change the
address**, then press Enter:

```bash
DOMAIN=n8n.yourdomain.com   # ← change to YOUR subdomain from Part 3
```

Check it took:

```bash
echo "Using domain: $DOMAIN"
```

If it still says `n8n.yourdomain.com`, you forgot to change it — run the line above
again with your real subdomain.

### 6.3 — Create the settings file (`.env`) — key made for you

n8n needs a secret key to encrypt your saved passwords. **You don't have to create
the key yourself** — this command generates a random one and writes the whole file:

```bash
cat > .env <<EOF
DOMAIN=$DOMAIN
N8N_ENCRYPTION_KEY=$(openssl rand -hex 24)
EOF
```

Now **see it and save it** somewhere safe (a password manager):

```bash
cat .env
```

Copy the `N8N_ENCRYPTION_KEY=…` line and keep it. You only ever need it if you move
servers or restore a backup — but if you lose it then, your saved credentials can't
be recovered. (Day to day, you never touch it again.)

### 6.4 — Create the web-server file (`Caddyfile`)

Paste this whole block (the quoted `'EOF'` keeps `{$DOMAIN}` literal so Caddy fills
it from `.env`):

```bash
cat > Caddyfile <<'EOF'
{$DOMAIN} {
    reverse_proxy n8n:5678
}
EOF
```

### 6.5 — Create the main file (`docker-compose.yml`)

Paste this whole block:

```bash
cat > docker-compose.yml <<'EOF'
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
EOF
```

Double-check all three files exist:

```bash
ls -a
```

You should see `.env`, `Caddyfile`, and `docker-compose.yml` in the list.

---

## Part 7 — Go live

### 7.1 — Check your domain is pointing at the server

Replace with your subdomain:

```bash
dig +short n8n.yourdomain.com
```

It should print **your static IP** (from Part 2.5). If it prints nothing or a
different IP, DNS hasn't finished — wait 10–15 minutes and try again. **Don't start
n8n until this matches** (Caddy needs it correct to get the SSL certificate).

### 7.2 — Start everything

```bash
sudo docker compose up -d
```

The first run downloads n8n and Caddy (~1–2 minutes). `-d` means it keeps running in
the background, even after you close the window.

Watch it get the certificate (optional):

```bash
sudo docker compose logs -f caddy
```

Look for a line about `certificate obtained`. Press **`Ctrl+C`** to stop watching
(this does **not** stop the server).

### 7.3 — Open your n8n

In your normal browser, go to **`https://n8n.yourdomain.com`**.

- You should see a **padlock** and the **n8n "Set up owner account"** screen.
- Enter your email + a strong password → this is your n8n login. **You're live.** 🎉

> First load may take 10–20 seconds while the certificate finishes. If you see a
> security warning, wait a minute and refresh — Caddy is still fetching the cert.

---

## Part 8 — Keeping it running

Run these from inside the server (`cd ~/n8n` first).

**See it's running:**
```bash
sudo docker compose ps
```

**Update n8n to the latest version** (do this every few weeks):
```bash
cd ~/n8n && sudo docker compose pull && sudo docker compose up -d
```

**Restart it:**
```bash
cd ~/n8n && sudo docker compose restart
```

**Stop it / start it again:**
```bash
sudo docker compose down      # stop
sudo docker compose up -d     # start
```

**Back up your workflows** (do this before any update). This saves all your n8n data
to a file in your home folder:
```bash
sudo docker run --rm -v n8n_n8n_data:/data -v ~:/backup alpine tar czf /backup/n8n-backup-$(date +%F).tar.gz -C /data .
```
Then download that `.tar.gz` from the Google SSH window (top-right **⚙ → Download
file**) and keep it safe.

**Check you're still free:** Google Cloud → **Billing**. As long as it's the
`e2-micro` in a free region with a 30 GB standard disk, hosting stays $0. Watch only
for large **network egress** (sending lots of data out) — normal automation traffic
is tiny.

---

## Part 9 — Troubleshooting

| Problem | Fix |
|---|---|
| Site won't load / "can't reach" | DNS not ready or wrong IP. Re-check Part 7.1 (`dig +short …` must equal your static IP). Confirm HTTP+HTTPS firewall was ticked (Part 2.4). |
| "Not secure" / certificate error | Caddy hasn't got the cert yet — it needs DNS correct **and** ports 80/443 open. Wait 2–3 min, refresh. Check `sudo docker compose logs caddy`. |
| n8n loads then crashes / restarts | Out of memory. Confirm swap is on (`free -h` shows 2 Gi). Re-do Part 5.2 if not. |
| "Command not found: docker" | Docker didn't install. Re-run Part 5.3. Use `sudo docker …` (with `sudo`). |
| Forgot the encryption key | It's in `~/n8n/.env` on the server (`cat ~/n8n/.env`). Save it in a password manager. |
| SSH window closed | Just click **SSH** again on the VM — the server keeps running on its own. |
| `sudo` fails weirdly right after connecting (permissions / "can't do that") | Server still provisioning. Close the SSH window, wait ~30s, reopen with **SSH**, retry. |
| `nano: command not found` | You don't need nano with this guide (Part 6 uses paste-blocks). If you want it: `sudo apt-get install -y nano`. |
| Webhooks from n8n don't reach it | Make sure `WEBHOOK_URL=https://your-domain/` in `.env`, then `sudo docker compose up -d` again. |

---

## What this cost you

| Item | Cost |
|---|---|
| Google Cloud server (e2-micro, free region) | **$0 / month** |
| SSL certificate (Caddy + Let's Encrypt) | **$0** |
| Domain name | ~$6-11 / year |
| **Your own production n8n** | **done ✅** |

You now own your automation infrastructure — no monthly n8n subscription, no trial
expiry, full control. Back it up, keep it updated, and build.
