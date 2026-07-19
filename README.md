# Automation Factory

The Laravel application behind **ajbuildai.com** — a custom LMS and marketing funnel for
the **AI Automation Accelerator** (a paid 6-week cohort) and **TAAB, The AI Automation
Bootcamp** (the free masterclass that feeds it).

It handles the whole path from a TikTok click to a paying, enrolled student: lead-magnet
tools → masterclass registration → automated reminders → payment (Paystack/Flutterwave) →
account provisioning → a ship-to-unlock member dashboard, with an admin for running it all.

| | |
|---|---|
| **Stack** | PHP 8.2+, Laravel 12, Livewire 3 + Volt, Fortify, Flux, Tailwind v4, Vite |
| **Database** | MySQL (production), SQLite (tests) |
| **Tests** | Pest — `php artisan test` |
| **Hosting** | Hostinger shared hosting, deployed by GitHub Actions |
| **Automation** | n8n (all email/WhatsApp sending happens there, not in Laravel) |

## Documentation

| Doc | What's in it |
|---|---|
| [docs/architecture.md](docs/architecture.md) | Domain model, routes, the two funnels, how data flows |
| [docs/configuration.md](docs/configuration.md) | Every env var and config file that matters, and what breaks without it |
| [docs/operations.md](docs/operations.md) | **Runbooks** — running a masterclass, opening a cohort, and recovering when sends fail |
| [docs/n8n-masterclass-flow.md](docs/n8n-masterclass-flow.md) | The n8n event contract (exact event names, payloads, webhook settings) |
| [DEPLOY.md](DEPLOY.md) | Server layout, GitHub Actions deploy, one-time Hostinger setup |
| [docs/emails/](docs/emails/) | The HTML email templates that live in n8n (versioned here so they don't drift) |

## Quick start

```bash
composer setup     # install, .env, key:generate, migrate, npm install, npm run build
composer dev       # serve + queue listener + vite, all at once
```

`composer setup` is idempotent and safe to re-run. Then grant yourself admin rights
(the user must already exist):

```bash
php artisan user:admin you@example.com
php artisan user:admin you@example.com --revoke   # to take it away
```

Running the pieces individually:

```bash
php artisan serve      # app on :8000
npm run dev            # vite dev server (hot reload)
npm run build          # production assets — REQUIRED before committing UI changes (see below)
php artisan test       # full Pest suite (~107 tests)
```

> **`public/build` is committed to git.** Hostinger has no Node, so compiled assets ship in
> the repo and `deploy.sh` copies them into the web root. If you change anything under
> `resources/`, run `npm run build` and commit the result, or production keeps serving the
> old CSS/JS.

## How it fits together

```
TikTok / socials
      │
      ├─→ /links ──────────────→ the 4 links that matter
      ├─→ /free ───────────────→ free workflows & cheatsheets (admin-managed)
      │
      ├─→ /taab ───────────────→ TAAB masterclass (free, live) ──┐
      │     ├─ /taab/scorecard      readiness scorecard          │  registration
      │     ├─ /taab/roi-calculator ROI calculator               │  → reminders
      │     └─ /taab/tool-stack     tool stack guide             │  → follow-up
      │                                                          ▼
      └─→ /accelerator ────────→ /checkout ──→ Paystack/Flutterwave
                                                     │
                                            webhook verifies payment
                                                     ▼
                                     account + enrollment provisioned
                                                     ▼
                                       /dashboard (ship-to-unlock)
```

Everything the student *receives* — every email and WhatsApp message — is sent by **n8n**.
Laravel's job is to decide *when* something should be sent, fire a typed event at a webhook,
and record that it happened. See [docs/n8n-masterclass-flow.md](docs/n8n-masterclass-flow.md).

## Project map

```
app/
  Console/Commands/     scheduled + operational commands (see below)
  Http/Controllers/
    Api/                payment + Vapi webhooks (server-side verification)
    MasterclassController, ScorecardController, TaabLeadController, VaultController
  Http/Middleware/      CheckEnrollment (paid access), EnsureAdmin
  Models/               Enrollment, Checkpoint, MasterclassRegistration, Student,
                        Lead, Resource, Setting, User
  Support/              Accelerator, Masterclass  (derived state — single source of truth)
                        StudentProvisioner        (account + enrollment + welcome flow)
config/
  accelerator.php       pricing, cohort dates, seats — DO NOT hardcode these in Blade
  taab.php              masterclass session, reminder offsets, lead-magnet settings
resources/views/
  livewire/admin/       the admin screens (Volt)
  livewire/dashboard/   the member terminal
  taab/                 masterclass hub + the three lead-magnet tools
  components/           requirements-costs, layouts (admin, taab)
docs/                   this documentation + email templates + n8n exports
```

### Commands

| Command | Purpose |
|---|---|
| `masterclass:remind` | Fires the 24h reminder, day-of nudge, and post-session follow-up — each once, when due. Scheduled every 15 min. |
| `masterclass:enroll-waitlist` | Moves masterclass waitlisters into the current session's registrations and confirms them. `--dry-run` supported. |
| `masterclass:reset-sends` | Clears send stamps so a touch can be re-sent after a failure. `--type`, `--except`, `--dry-run`. |
| `installments:process` | Sends 2nd-payment links on the due date and suspends overdue balances. Scheduled daily 09:00. |
| `enroll:user {email} {name} [amount] [currency] [--cohort=]` | Enrolls someone manually (offline/bank transfer) exactly as a verified payment would. Defaults to `79000 NGN`. |
| `user:admin {email} [--revoke]` | Grants (or revokes) admin rights for an existing user. |

## Things that will bite you

These are real incidents, not hypotheticals. Details and recovery steps in
[docs/operations.md](docs/operations.md).

- **Hostinger's cron does not run.** The Laravel scheduler is driven by a GitHub Actions
  workflow instead — and GitHub drops ~75% of scheduled runs. Anything with a short
  send window needs a manual run. See *Scheduling reality* in the ops doc.
- **An n8n `2xx` only means "accepted", unless you configure it not to.** The webhook nodes
  must be set to **Respond: When Last Node Finishes**, or Laravel records sends that never
  happened.
- **Config is cached in production.** New `.env` values do nothing until `php artisan
  config:cache` runs. This has silently blanked the Meet link before.
- **Temp passwords are hashed and unrecoverable.** Re-sending a welcome email issues a *new*
  password; it cannot replay the old one.
- **Never hardcode prices or dates in Blade.** `config/accelerator.php` +
  `App\Support\Accelerator` are the single source of truth, and the landing page and
  checkout must agree.
