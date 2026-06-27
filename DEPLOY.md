# Deployment (Hostinger)

The app lives in `~/domains/ajbuildai.com/public_html/laravel` and the web root is
`~/domains/ajbuildai.com/public_html`.
Deploys are automated by **GitHub Actions** (`.github/workflows/deploy.yml`):
every push to `main` SSHes into Hostinger, runs `git pull`, then `deploy.sh`
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
  run `npm run build` — `git pull` brings the bundle and `deploy.sh` copies it to the
  web root.
- `deploy.sh` does **not** run migrations by default. If a deploy includes new
  migrations, review them, then uncomment the `php artisan migrate --force` line.
- The `cd` path in `deploy.yml` is set to this account's app root
  (`~/domains/ajbuildai.com/public_html/laravel`). `deploy.sh` copies to its parent
  dir, so the web-root path adapts automatically.
