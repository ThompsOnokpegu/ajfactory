# Set up your own n8n on Hostinger — one-click deploy, step by step

This is the **alternative route** for anyone who can't get a Google Cloud account
verified. Instead of building a server by hand, you buy a small VPS and Hostinger
installs n8n for you — **HTTPS included, no terminal required** for the basic setup.

You end up in the same place as the Google Cloud guide: **your own private n8n on a
real web address with a padlock**, that you own and control.

> **Time:** about 10–15 minutes.
> **The trade-off:** this one **costs money** (the Google Cloud route is free
> hosting). You're paying to skip the hard parts.

> ⚠️ **Screens change.** Hostinger renames menus from time to time. If a button
> isn't *exactly* where this says, look for the same *word* nearby — the steps and
> the commands themselves don't change.

---

## Which guide should you use?

Read this before spending anything.

| | **Google Cloud** (free) | **Hostinger** (this guide) |
|---|---|---|
| Hosting cost | **₦0/month** forever | Paid — from **$6.49/mo** (see Part 1) |
| Sign-up | Needs a card **and** Google's verification, which is where a lot of people get stuck | A normal online checkout |
| Setup | ~45–60 min, copy-paste terminal commands | ~10–15 min, mostly clicking |
| Server power | 1 GB RAM (tight — needs a swap-memory workaround) | 4 GB RAM on the entry plan |
| HTTPS | You set it up (Caddy) | Automatic |
| Domain | You buy one (~₦8–15k/yr) | Works instantly on a free Hostinger address; free domain for 1 year included |

**Try [the Google Cloud guide](/guides/n8n-on-google-cloud) first** — it's free, and free
is hard to beat. Come here if Google won't verify you, or if you'd rather pay to skip
the terminal.

> **Disclosure:** the Hostinger links below are referral links. It costs you nothing
> extra, and it's the same host used in the Google Cloud guide for domains.

---

## What you'll end up with

```
https://n8n.srv123456.hstgr.cloud   ← free address, works immediately
   (or your own n8n.yourdomain.com — Part 7, optional)
        │
        ▼
Your Hostinger VPS
        ├─ Traefik  → automatic SSL + forwards traffic
        └─ n8n      → your automation tool, running in Docker
```

Hostinger's template builds all of that for you.

---

## Part 0 — What you need before you start

- [ ] A **debit/credit card** that works for online international payments.
- [ ] An email address.
- [ ] A computer with a browser. There's **no software to install**.

That's it. No cloud account verification, no domain needed to get started.

---

## Part 1 — Buy the VPS

1. Go to **[hostinger.com/vps](https://www.hostinger.com/vps-hosting?REFERRALCODE=1AJ9770)**
   and choose a **KVM** plan:

   | Plan | Specs | Good for |
   |---|---|---|
   | **KVM 1** | 1 vCPU · 4 GB RAM · 50 GB | **Start here.** Plenty for this course and normal automations. |
   | **KVM 2** | 2 vCPU · 8 GB RAM · 100 GB | Heavier use, many workflows running at once. |

2. Pick your billing term and check out.

3. Hostinger asks a few setup questions after payment. If it offers to install an
   application or asks what you'll use the server for, you can pick **n8n** here and
   skip Part 2. If you're not sure, choose plain **Ubuntu** — Part 2 installs n8n
   properly either way.

> ⚠️ **Know what you're paying before you click.** At the time of writing Hostinger
> lists **KVM 1 at $6.49/mo** and **KVM 2 at $8.79/mo**, but those are promotional
> rates that need a **long term paid upfront**, and they **renew higher** (KVM 1
> renews at $11.99/mo, KVM 2 at $14.99/mo). Prices are in **US dollars** — your bank
> sets the naira rate. Check the total and the renewal price on the checkout page
> before paying; these numbers change.

> **Free domain:** VPS plans include a free domain for the first year. You don't need
> it for n8n to work — Hostinger gives you a free web address either way — but it's
> worth claiming.

---

## Part 2 — Install n8n (the one-click bit)

1. In **hPanel**, go to **VPS** in the left menu.
2. Click **Manage** on your server.
3. Open **OS & Panel → Operating System**.
4. In the template search box, type **`n8n`** and select the **n8n** template.
5. Click **Change OS**.
6. Tick the box acknowledging that **all files will be deleted**, then **Next**.
7. Set a **root password**. **Save it in a password manager** — you need it if you
   ever use the terminal (Parts 4 and 7).
8. Click **Confirm**. A progress bar appears; installation takes a couple of minutes.

> ⚠️ **"Change OS" erases the server completely.** On a brand-new VPS that's exactly
> what you want — there's nothing on it. **Never** run this on a server that already
> has websites or data you care about.

---

## Part 3 — Open your n8n and create your account

1. When the install finishes, go back to the **VPS Overview** page.
2. Click **Manage App**. Your n8n opens in a new tab.

   Your address looks like **`https://n8n.srv123456.hstgr.cloud`** (your own number).
   Note the **padlock** — HTTPS is already set up. **Write this address down.**

3. n8n shows a **"Set up owner account"** screen. Enter your email and a strong
   password.

**You're live.** 🎉 That's a real, private, always-on n8n — no trial timer.

> **This address is fully usable.** Webhooks work, the course projects work, nothing
> is second-class about it. Part 7 (your own domain) is optional polish.

---

## Part 4 — Keeping it running

You can do everything below from hPanel's built-in terminal:
**VPS → Manage → Terminal**. Log in as `root` with the password from Part 2.

**Update n8n** (worth doing every few weeks):

```bash
cd $(dirname $(find /root /docker -name docker-compose.yml 2>/dev/null | head -1))
docker compose pull
docker compose down
docker compose up -d
```

**Check it's running:**

```bash
docker ps
```

**Back it up.** Two layers, and you want both:

- **The server:** hPanel → **VPS → Manage → Backups & Monitoring**. Weekly backups are
  included on VPS plans. You can also take a **snapshot** before risky changes — but
  a snapshot is a one-slot, temporary thing (it's replaced by the next one and expires
  after about a day), so treat it as an undo button, not a backup.
- **Your workflows:** inside n8n, use **Download** on a workflow to save the JSON. Keep
  the important ones in a folder on your computer. This is the copy that survives
  anything.

---

## Part 5 — Troubleshooting

| Problem | Fix |
|---|---|
| **Manage App** does nothing / n8n won't load | The template is probably still installing. Check VPS Overview shows **Running**, wait 2–3 minutes, refresh. |
| Forgot the **root** password | Reset it from the VPS management screen in hPanel, under the server's settings. |
| Forgot your **n8n** login | There's no reset email — it's your own server. In the terminal: `docker ps` to find the n8n container name, then `docker exec -it <container> n8n user-management:reset`, then `docker restart <container>`. This clears the **accounts only** — your workflows are untouched — and the next visit shows the setup screen again. |
| Custom domain shows "not secure" | DNS hasn't finished spreading. The certificate can only be issued once the domain resolves to your VPS — check with `dig +short n8n.yourdomain.com`, wait, then retry. |
| Webhooks still fire at the old address after a domain change | `WEBHOOK_URL` wasn't updated, or the containers weren't restarted. Re-do Part 7 from 7.5 onward. |
| Anything hosting-level (billing, server won't boot) | Hostinger has 24/7 chat support in hPanel. You're paying for it — use it. |

---

## Part 6 — What this cost you

| Item | Cost |
|---|---|
| VPS (KVM 1, promotional rate) | from **$6.49 / month** |
| SSL certificate (automatic) | **$0** |
| Web address (`…hstgr.cloud`) | **$0** |
| Domain name | free for year 1 with the plan |
| **Your own production n8n** | **done ✅** |

No n8n subscription, no trial expiry, full control — you just paid for convenience
instead of spending an hour in a terminal. Keep it updated, keep your workflows
backed up, and build.

---

## Part 7 — (Optional) Use your own domain

Everything already works, so **skip this if you're keen to start building.** Come back
when you want `n8n.yourdomain.com` instead of `n8n.srv123456.hstgr.cloud`.

This part **does** need the terminal.

### 7.1 — Point the domain at your server

1. Find your VPS **IP address** on the hPanel VPS Overview page.
2. At your domain registrar, open the **DNS records** for your domain and add:
   - **Type:** `A`
   - **Name:** `n8n`   ← this makes `n8n.yourdomain.com`
   - **Points to:** your VPS IP address
   - **TTL:** leave the default
3. Save. DNS takes **5 minutes to a couple of hours** to spread.

### 7.2 — Wait until DNS is actually ready

Open **VPS → Manage → Terminal** and run (with your domain):

```bash
dig +short n8n.yourdomain.com
```

**Don't continue until this prints your VPS IP.** The certificate can't be issued
before it does.

### 7.3 — Find the config folder

Hostinger has used more than one location for this, so let the server find it:

```bash
cd $(dirname $(find /root /docker -name docker-compose.yml 2>/dev/null | head -1))
pwd && ls
```

You should see `docker-compose.yml` and `.env`.

### 7.4 — Make a safety copy

```bash
cp docker-compose.yml docker-compose.yml.bak && cp .env .env.bak
```

If anything goes wrong, `cp docker-compose.yml.bak docker-compose.yml` puts it back.

### 7.5 — Set your domain

Type your subdomain once — the next commands reuse it. **Change the address:**

```bash
DOMAIN=n8n.yourdomain.com   # ← change to YOUR subdomain
```

```bash
echo "Using domain: $DOMAIN"
```

If that still says `n8n.yourdomain.com`, you didn't change it — run it again.

### 7.6 — Apply the change

Three edits. Paste each block as-is:

```bash
sed -i "s|^TRAEFIK_HOST=.*|TRAEFIK_HOST=$DOMAIN|" .env
```

```bash
sed -i 's|https://${COMPOSE_PROJECT_NAME}.${TRAEFIK_HOST}/|https://${TRAEFIK_HOST}/|' docker-compose.yml
```

```bash
sed -i 's|Host(`${COMPOSE_PROJECT_NAME}.${TRAEFIK_HOST}`)|Host(`${TRAEFIK_HOST}`)|' docker-compose.yml
```

Check they landed:

```bash
grep TRAEFIK_HOST .env && grep -n "WEBHOOK_URL\|routers" docker-compose.yml
```

You want `TRAEFIK_HOST=n8n.yourdomain.com`, a `WEBHOOK_URL` of
`https://${TRAEFIK_HOST}/`, and a router rule of `Host(\`${TRAEFIK_HOST}\`)` — none of
them should still mention `${COMPOSE_PROJECT_NAME}`.

### 7.7 — Restart

```bash
docker compose down && docker compose up -d
```

Give it a minute to fetch the certificate, then open **`https://n8n.yourdomain.com`**.
Padlock, your workflows, your domain.

> **Your existing workflows are safe** — this changes the address, not the data. But
> any webhook URL you've already pasted into another service now points at the old
> address, so update those.
