# Deployment (Hostinger)

The app lives in `~/domains/ajbuildai.com/public_html/laravel` and the web root is
`~/domains/ajbuildai.com/public_html`.
Deploys are automated by **GitHub Actions** (`.github/workflows/deploy.yml`):
every push to `main` SSHes into Hostinger, resets the working tree to `origin/main`
(`git fetch origin main && git reset --hard origin/main` — a plain `git pull` used to fail
whenever the server tree had drifted), then runs `deploy.sh`
(copies the build assets into the web root, then clears + rebuilds the
config/route/view caches). No more manual copying or cache commands.

## How the build assets get published
The web root is `public_html` but Vite's assets are pulled into
`laravel/public/build`. `deploy.sh` copies that folder up to `public_html/build`
on every deploy (the original stays put), so you never copy it by hand again.

## One-time server setup

### 1. Enable SSH + add a deploy key
- hPanel → **Advanced → SSH Access**: note the **Host**, **Port** (usually `65002`),
  and **Username**, and make sure SSH is enabled.
- Generate a key pair for CI (locally or in Hostinger's terminal):
  ```bash
  ssh-keygen -t ed25519 -f ~/.ssh/gh_deploy -N ""
  cat ~/.ssh/gh_deploy.pub >> ~/.ssh/authorized_keys
  chmod 600 ~/.ssh/authorized_keys
  cat ~/.ssh/gh_deploy        # private key → GitHub secret below
  ```

### 2. Add GitHub repo secrets
GitHub repo → **Settings → Secrets and variables → Actions → New secret**:

| Secret | Value |
|---|---|
| `HOSTINGER_SSH_HOST` | SSH host/IP from hPanel |
| `HOSTINGER_SSH_USER` | SSH username (e.g. `u123456789`) |
| `HOSTINGER_SSH_PORT` | `65002` (or whatever hPanel shows) |
| `HOSTINGER_SSH_KEY`  | the **private** key (full contents of `gh_deploy`) |

### 3. Turn off hPanel auto-deployment
hPanel → **Advanced → Git** → disable auto-deployment, so the GitHub workflow is
the single source of truth (otherwise both will try to pull).

## Manual deploy
Run the workflow from the **Actions** tab (“Deploy to Hostinger” → *Run workflow*),
or over SSH:

```bash
cd ~/domains/ajbuildai.com/public_html/laravel && git fetch origin main && git reset --hard origin/main && bash deploy.sh
```

## Notes
- Built assets (`public/build`) are committed to the repo, so the server does **not**
  run `npm run build` — the reset brings the bundle and `deploy.sh` copies it to the
  web root. **Run `npm run build` and commit `public/build` before pushing UI changes**,
  or production keeps serving the old CSS/JS.
- `deploy.sh` **does** run `php artisan migrate --force` on every deploy, then rebuilds
  the config/route/view caches. Review pending migrations before pushing.
- **A failed deploy can leave prod with new code but un-run migrations.** Hostinger SSH
  sometimes times out (`dial tcp … i/o timeout`); the reset may land new code before the
  run dies, so `migrate` never happens → `Base table or view not found` after a push.
  **Always check the Deploy run is green**, and if it's red just re-run it (Actions →
  *Deploy to Hostinger* → Re-run jobs, or *Run workflow* — it has `workflow_dispatch`).
  `deploy.sh` is idempotent, so re-running is safe.
- The `cd` path in `deploy.yml` is set to this account's app root
  (`~/domains/ajbuildai.com/public_html/laravel`). `deploy.sh` copies to its parent
  dir, so the web-root path adapts automatically.

## Scheduler-fallback workflows

**Hostinger's hPanel cron does not execute for this account** — so the Laravel scheduler
never ticks. Each scheduled command therefore has its own GitHub Actions workflow that reuses
the same SSH secrets and runs the command **directly** (not `schedule:run`, which needs
exact-minute alignment a delayed cron can't give). All are idempotent, so extra/late runs are
harmless:

- `scheduler.yml` — `masterclass:remind`, every 15 min.
- `installments.yml` — `installments:process`, 3×/day (09/15/21 WAT).
- `masterclass-announce.yml` — `masterclass:announce` (re-invite), daily + manual.

**GitHub's scheduled triggers are best-effort and drop most runs** — measured at ~75% on
this repo. Anything with a short send window must be run manually. Read
[docs/operations.md](docs/operations.md#️-scheduling-reality--read-this-first) before
relying on it. Replacing this with a reliable trigger is an open task.

You can fire it any time from **Actions → Masterclass reminders → Run workflow**
(`workflow_dispatch`), which is not throttled.
