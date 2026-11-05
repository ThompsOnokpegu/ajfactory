# n8n on Google Cloud: every step, every command

Your own n8n on your own domain, with HTTPS, on Google Cloud's free tier. Work from top
to bottom and paste each command exactly as written.

---

## Part 0: Before you start

- [ ] A **Google account**.
- [ ] A **bank card**. Depending on your card and country, Google may ask for a one-time
      prepayment ($30 in Nigeria).
- [ ] About **$6-11 a year** for a domain.
- [ ] A computer with a browser.

---

## Part 1: Get a domain name

Any registrar works. I use Hostinger.

1. Go to **[hostinger.com](https://www.hostinger.com?REFERRALCODE=1AJ9770)** and search
   for a domain.
2. Add it to the cart, check out and pay.

This is an affiliate link. If you buy through it, I may earn a commission at no extra
cost to you.

---

## Part 2: Create the server

Google renames menus often. If a button has moved, look for the same word nearby.

### 2.1: Create a Google Cloud account

1. Go to **console.cloud.google.com** and sign in.
2. Accept the terms and click **Start free / Activate**.
3. Enter your details and card, and choose **Individual**. Pay the prepayment if Google
   asks for it.

### 2.2: Create a project

1. Top-left, click the **project dropdown**.
2. **New Project** → name it `n8n-automation` → **Create**.
3. Make sure the dropdown shows your **n8n-automation** project.

### 2.3: Set a budget alert

1. In the top search bar, open **Budgets & alerts**.
2. **Create budget** → name it `zero` → set **Target amount** to **$1** → alert at 50%
   and 100% → **Finish**.

### 2.4: Create the server (VM)

1. Search **VM instances** and open **Compute Engine → VM instances**. If asked, click
   **Enable** and wait about a minute.
2. Click **Create instance** and match these exactly. They keep it free.
   - **Name:** `n8n-server`
   - **Region:** one of `us-west1`, `us-central1` or `us-east1`
   - **Series** `E2` → **Machine type** `e2-micro`
   - **Boot disk → Change:** Ubuntu → `Ubuntu 22.04 LTS Minimal` → **Standard persistent
     disk** → **30 GB** → Select
   - **Firewall:** tick both ✅ Allow HTTP and ✅ Allow HTTPS
3. Click **Create** and wait for the **External IP** to appear.

### 2.5: Make the IP permanent

1. Search **IP addresses** and open **VPC network → IP addresses**.
2. On the `n8n-server` row, choose **Promote to static** (or "Reserve"), name it
   `n8n-ip` → **Reserve**.
3. Write down this IP. You need it next.

---

## Part 3: Point your domain at the server

In Hostinger (**hPanel**). On another registrar, add the same record there.

1. Go to **Domains** and click **Manage** on your domain.
2. Open **DNS / Nameservers → DNS records**. Under **Add new record**, set:
   - **Type:** `A`
   - **Name:** `n8n`   ← makes `n8n.yourdomain.com`
   - **Points to:** your static IP from 2.5
   - **TTL:** leave the default
3. Click **Add record**.
4. If another `A` record already uses the `n8n` name, delete it and keep your new one.

DNS takes 5 minutes to a couple of hours. Carry on meanwhile. You check it in 7.1.

---

## Part 4: Connect to your server

1. Google Cloud → **Compute Engine → VM instances**.
2. On the `n8n-server` row, click **SSH**. If asked, click **Authorize**. A black
   terminal window opens.

Paste with right-click → Paste, or `Ctrl+V`, then press Enter. Test it:

```bash
whoami && echo "Connected 🎉"
```

You should see your username and `Connected 🎉`.

> ⚠️ **If sudo fails with a strange error:** a new server takes a minute or two to finish
> setting up. Close the SSH window, wait 30 seconds, click **SSH** again and re-run the
> command.

---

## Part 5: Prepare the server

Run each block on its own. Wait for the prompt to come back before the next.

### 5.1: Update the server

```bash
sudo apt-get update && sudo apt-get upgrade -y
```

If it asks a yes/no question, press Enter. If it lists services and asks which to restart, type the number for **none of the above**, then press Enter.

### 5.2: Add swap memory

```bash
sudo fallocate -l 2G /swapfile
sudo chmod 600 /swapfile
sudo mkswap /swapfile
sudo swapon /swapfile
echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab
```

Check it. The `Swap:` line should show `2.0Gi`.

```bash
free -h
```

### 5.3: Install Docker

```bash
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh
```

Check it. Both should print a version number.

```bash
sudo docker --version && sudo docker compose version
```

---

## Part 6: Install n8n with automatic HTTPS

### 6.1: Make a folder

```bash
mkdir ~/n8n && cd ~/n8n
```

### 6.2: Set your domain

**Change the address** to your subdomain, then press Enter:

```bash
DOMAIN=n8n.yourdomain.com   # ← change to YOUR subdomain
```

Check it. This should print your real subdomain.

```bash
echo "Using domain: $DOMAIN"
```

### 6.3: Settings file (`.env`)

This generates your encryption key and writes the file:

```bash
cat > .env <<EOF
DOMAIN=$DOMAIN
N8N_ENCRYPTION_KEY=$(openssl rand -hex 24)
EOF
```

Show the file, then save the `N8N_ENCRYPTION_KEY=…` line in a password manager:

```bash
cat .env
```

> ⚠️ **Save this.** Without this key, a restored backup can't recover your saved
> credentials.

### 6.4: Web-server file (`Caddyfile`)

```bash
cat > Caddyfile <<'EOF'
{$DOMAIN} {
    reverse_proxy n8n:5678
}
EOF
```

### 6.5: Main file (`docker-compose.yml`)

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

Not in Lagos? Put your timezone in place of `America/New_York`, then press Enter.

```bash
sed -i 's#Africa/Lagos#America/New_York#' docker-compose.yml
```

Check it. You should see `.env`, `Caddyfile` and `docker-compose.yml`.

```bash
ls -a
```

---

## Part 7: Go live

### 7.1: Check the domain points here

Use your own subdomain:

```bash
dig +short n8n.yourdomain.com
```

If it says `dig: command not found`, install it, then run the check again. If the
restart list comes up, choose **none of the above**.

```bash
sudo apt update && sudo apt install -y dnsutils
```

> ⚠️ **Must match first.** It should print **your static IP**. If it doesn't, wait 10-15
> minutes and try again. **Don't start n8n until it matches**, or Caddy can't get the
> certificate.

### 7.2: Start everything

```bash
sudo docker compose up -d
```

The first run takes 1-2 minutes. To watch the certificate arrive (optional):

```bash
sudo docker compose logs -f caddy
```

Look for `certificate obtained`, then press `Ctrl+C`. That doesn't stop the server.

### 7.3: Open your n8n

1. In your normal browser, go to `https://n8n.yourdomain.com`.
2. On the **Set up owner account** screen, enter your email and a strong password.
   That's your n8n login.

A security warning means Caddy is still fetching the certificate. Wait a minute and
refresh.

---

## Part 8: Keep it running

Run `cd ~/n8n` first.

**See it's running:**
```bash
sudo docker compose ps
```

**Update, every few weeks:**
```bash
sudo docker compose pull && sudo docker compose up -d
```

**Restart:**
```bash
sudo docker compose restart
```

**Stop:**
```bash
sudo docker compose down
```

**Start:**
```bash
sudo docker compose up -d
```

**Back up, before any update:**
```bash
sudo docker run --rm -v n8n_n8n_data:/data -v ~:/backup alpine tar czf /backup/n8n-backup-$(date +%F).tar.gz -C /data .
```

Then download the `.tar.gz` from the SSH window (top-right **⚙ → Download file**).

---

## Part 9: Troubleshooting

| Problem | Fix |
|---|---|
| Site won't load | Re-check 7.1: `dig +short …` must equal your static IP. Confirm HTTP and HTTPS were ticked in 2.4. |
| Certificate error | Caddy needs correct DNS and ports 80/443 open. Wait 2-3 minutes and refresh. Check `sudo docker compose logs caddy`. |
| n8n crashes or restarts | Out of memory. `free -h` must show 2 Gi of swap. Re-do 5.2 if not. |
| `docker: command not found` | Re-run 5.3. Always use `sudo docker …`. |
| Forgot the encryption key | Run `cat ~/n8n/.env`. |
| Webhooks don't arrive | `DOMAIN` in `~/n8n/.env` must be your real subdomain. Fix it, then run `sudo docker compose up -d`. |
