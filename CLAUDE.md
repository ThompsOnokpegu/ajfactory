# CLAUDE.md — working in this repo

Durable project facts and conventions. For depth, start at [README.md](README.md), which
links to the architecture, configuration, and operations docs.

> This file previously held the build brief for the Accelerator landing/checkout pages.
> Those shipped, so it's been trimmed as that brief instructed. Anything describing *how the
> product actually behaves* now lives in `docs/` and in config — this file is only for
> conventions and the facts that don't change often.

---

## 1. How to work here

1. **Explore before editing.** Custom Laravel 12 + Livewire/Volt LMS. Find the existing
   route, component, and layout and match its conventions — don't introduce a new framework
   or restructure.
2. **Don't invent the stack.** If something assumes a tool that isn't wired up, adapt to
   what's here and leave a `// TODO:` rather than adding a dependency.
3. **Flag irreversible things** — migrations, deleting routes, changing payment keys.
   Prefer additive changes.
4. **Never fabricate** testimonials, dates, links, metrics, or keys. Use a clearly marked
   `{{TODO}}` placeholder instead. An empty state is always better than invented proof.
5. **Verify before asserting.** Config and copy have drifted from each other repeatedly —
   check the live file rather than trusting memory or an older doc.
6. **Document every major change or decision in the right doc, in the same PR/commit as the
   change** — code without the doc update is incomplete. Match the change to its home:
   `docs/architecture.md` (domain model, tables, data flow), `docs/configuration.md` (env
   vars / config keys), `docs/operations.md` (runbooks — how to operate a feature),
   `docs/n8n-masterclass-flow.md` (any new/renamed n8n event `type` — it's the app↔n8n
   contract), `DEPLOY.md` (deploy/server), or this file (durable conventions). A one-off fix
   doesn't need a doc; a new command, table, event type, config key, route, or a reversed
   design decision does.

---

## 2. Product facts

| Field | Value |
|---|---|
| Legal entity | **Deepr Web Services** |
| Public identity | **ajbuildai.com** · hello@ajbuildai.com |
| Paid product | **AI Automation Accelerator** — 6 weeks, 9 production workflows |
| Free funnel | **TAAB (The AI Automation Bootcamp)** — a live 2-hour masterclass |
| Audience | Nigeria-first, beginners, no coding required. Mostly mobile / TikTok traffic. |
| Tone | Confident, plain-English, no hype, Naira-first, "no surprises" |

**Pricing, cohort dates, and seat counts are NOT written here** — they live in
`config/accelerator.php` and are read through `App\Support\Accelerator`. Never hardcode them
in Blade; the landing page and checkout must always agree.

### The two products are positioned differently — don't blur them

- **TAAB Masterclass** sells **clarity before committing**: readiness, real costs, skills,
  mindset. It is *not* a build session, and marketing it as "come build bots" attracts the
  wrong audience and misrepresents it.
- **The Accelerator** is where building happens.

### Brand
- **Accelerator / admin / links:** dark `zinc-950` + cyan `#06b6d4`, Space Grotesk.
- **TAAB:** dark + lime `#c8f064`, Syne / DM Sans.
- Mobile-first, generous spacing.
- **Hyphens only in student-facing copy. No em (—) or en (–) dashes.** Applies to
  curriculum titles and lesson names, page copy, and emails.
- The Accelerator pages deliberately keep the **Deepr + cyan** look rather than a separate
  "AJBuilds AI" teal brand. That was an explicit decision — don't "correct" it.
- **Emails are always dark.** Force it (`color-scheme: only dark` + the meta tags) so a
  dark-mode client can't render a washed-out light version.

---

## 3. Non-negotiables

These encode real incidents. Changing them will break something that took a while to fix.

- **The checkout "no-surprises" acknowledgement checkbox gates the pay button.** It's a
  deliberate trust and refund-protection mechanism. Keep it.
- **Payments are verified server-side** via webhook before access is granted. Never trust a
  client-side success callback.
- **Laravel never sends email or WhatsApp** — it fires a typed event at n8n and records the
  outcome. See [docs/n8n-masterclass-flow.md](docs/n8n-masterclass-flow.md) for the exact
  event names; a one-character mismatch fails every send at once.
- **Stamp a send only on genuine success**, and never swallow a non-2xx. Failures must stay
  unstamped so the next run retries them — that's what makes recovery work at any headcount.
- **Run `npm run build` and commit `public/build`** before pushing UI changes. Hostinger has
  no Node; production serves the committed bundle.
- **Config is cached in production.** New `.env` values need `php artisan config:cache`.
- **The module-01 date floor belongs to the student's cohort, not the current one.** Use
  `Accelerator::startFloorFor($cohort)`; it returns `null` for any past cohort. Reading
  `accelerator.cohort_starts_at` directly re-locks every mid-course student the moment you
  schedule the next cohort - that happened on the Cohort 3 launch and shut Cohort 2 out of
  module 01 with approved checkpoints in hand. A start floor must never move forward under
  someone who has already begun.
- **Curriculum module ids are stable keys, never positions.** `Checkpoint.module_id`,
  `Enrollment.completed_lessons`, `accelerator.telegram_threads` and
  `reviews.stages[].after_module` all key off them. Reordering the curriculum changes the
  array order and the display `title` **only** — never an `id`. So ids legitimately stop
  matching their numbers (`module-03` displays as "Module 04"), and "tidying" them up
  silently reassigns students' approved checkpoints and completed lessons. Renaming an id
  also breaks review stages with no error — the stage just never fires again.
  `CurriculumTest` guards this.
- **A written guide is a pair: edit both halves.** Each guide is a standalone page in
  `resources/views/guides/` plus its markdown source in `docs/guides/`. They've drifted
  before (the page said one thing, the markdown another). Shared CSS/JS lives in
  `resources/views/guides/partials/` — both pages `@include` it, so don't paste a second
  copy of the design system into a new guide.
- **Don't `git add -A`.** This repo contains large binaries and n8n exports that GitHub's
  push protection rejects. Stage only what you changed.

---

## 4. Where things live

| Need | Look at |
|---|---|
| Pricing, cohort dates, seats | `config/accelerator.php` → `App\Support\Accelerator` |
| Masterclass session, reminder offsets | `config/taab.php` → `App\Support\Masterclass` |
| Staged student-review questions & ask cadence | `config/reviews.php` → `App\Models\StudentReview` |
| Turning a payment into a student | `App\Support\StudentProvisioner` |
| Scheduled/operational commands | `app/Console/Commands/` |
| Admin screens | `resources/views/livewire/admin/` |
| Requirements & costs copy (shared) | `resources/views/components/requirements-costs.blade.php` |
| Email templates (mirrors of what's in n8n) | `docs/emails/` |
| Student-facing snippets (prompts / code) | `snippets` table → `App\Models\Snippet`, admin at `/admin/snippets` |
| Written student guides | `resources/views/guides/` (the live page) + `docs/guides/` (markdown source) |

Runtime flags an operator should change without a deploy belong in the `Setting` key/value
store (e.g. `accelerator_registration_open`), not in config.

---

## 5. Testing

Pest. `php artisan test` runs the suite; `tests/Pest.php` binds `TestCase` + `RefreshDatabase`
to `Feature`. Use `Http::fake()` and `Carbon::setTestNow()` for time-based flows — note
`Http::fake()` *merges* stubs, so use `Http::fakeSequence()` when consecutive calls need
different responses. `Undefined method 'get'/'artisan'` warnings in test files are known IDE
false positives.

Cover the behaviour that matters operationally: that a failed send isn't recorded as sent,
that gating actually gates, and that idempotent commands stay idempotent.

`phpunit.xml` **pins the `N8N_*` webhook URLs** (alongside `DB_CONNECTION=sqlite` etc.) so
the suite is self-contained. Don't remove them: the code skips a send when the webhook URL is
unconfigured, so any `Http::assertSent`-style test would pass locally (dev `.env` has the URL)
but fail on CI (which copies `.env.example`, where they're unset). Real incident — that's why
they're pinned. Similarly, tests that assert a send must ensure the relevant webhook config is
set (it is, via `phpunit.xml`), not rely on ambient `.env`.
