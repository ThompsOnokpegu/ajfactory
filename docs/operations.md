# Operations runbook

Everything you actually do to run this thing: shipping a masterclass edition, opening a
cohort, and recovering when automation fails. Most of this exists because it went wrong once.

All commands run from the app root on the server:

```bash
cd ~/domains/ajbuildai.com/public_html/laravel
```

---

## ⚠️ Scheduling reality — read this first

**Hostinger's hPanel cron does not execute for this account.** Confirmed by a heartbeat test
that never produced output; `crontab -l` reports no crontab. The Laravel scheduler therefore
never ticks on its own.

The workaround is a set of GitHub Actions workflows that SSH in and run each command
**directly** (not `schedule:run`, which needs exact-minute alignment a delayed cron can't
provide):
- `.github/workflows/scheduler.yml` — `masterclass:remind`, every 15 min.
- `.github/workflows/installments.yml` — `installments:process`, 3×/day (09/15/21 WAT).
- `.github/workflows/masterclass-announce.yml` — `masterclass:announce`, daily + manual.

Each command is idempotent, so extra/duplicate ticks are safe.

**But GitHub's cron is best-effort and drops most runs.** Observed on this repo: 42 runs in
~2 days against an expected ~190 — roughly **75% dropped**, with gaps averaging ~1.7 hours
and one gap of 3h20m.

Consequences you must plan around:

| Touch | Window | Safe to leave to the scheduler? |
|---|---|---|
| Masterclass reminder (T-24h) | ~23 hours | Yes |
| **Day-of nudge (T-2h)** | **2 hours** | **No — run it manually** |
| Post-session follow-up | never closes | Yes, eventually |
| `installments:process` | all day | Yes — runs 3×/day via `installments.yml` |

> The day-of nudge has silently failed twice. On session morning, run it by hand between
> **07:00 and 08:30 WAT**. The commands are idempotent — a manual run and a scheduled run
> can't double-send.

Replacing this scheduler with something reliable is the top outstanding infrastructure task.

---

## ⚠️ The n8n `2xx` trap

An n8n Webhook node set to **Respond: Immediately** returns `200` *before the workflow runs*.
Laravel sees success, stamps the send as done, and n8n then drops or errors the execution —
producing rows marked "reminded" with no email behind them.

**Every webhook node must be set to `Respond: When Last Node Finishes`.**

That makes the status truthful: Laravel blocks until n8n finishes, real failures return
non-2xx, nothing gets stamped, and the next run retries automatically. It also provides
natural backpressure, which is what actually prevents burst-drops — throttling alone never
fixed it, because n8n was accepting everything instantly regardless.

This setting is also what makes failures *visible*: a mis-typed Switch branch
(`masterclass_follow_up` vs `masterclass_followup`) failed all 53 follow-ups with HTTP 500
and was diagnosable in minutes. With "Respond: Immediately" it had been silently discarding
sends indefinitely.

---

## Running a masterclass edition

### 1. Set up the session (a week out)

Edit `config/taab.php`:

```php
'date'                => '2026-08-01',        // the session day
'registration_closes' => '2026-07-31',
'starts_at'           => '2026-08-01 09:00',  // real datetimes drive the reminders
'ends_at'             => '2026-08-01 11:00',
```

Then set the join links **in the server `.env`** (not just config):

```
TAAB_MEET_URL=https://meet.google.com/xxx-xxxx-xxx
TAAB_WA_GROUP_URL=https://chat.whatsapp.com/xxxx
```

**Config is cached in production**, so new `.env` values do nothing until you rebuild it.
This has shipped blank Meet links before:

```bash
php artisan config:cache
php artisan tinker --execute="echo config('taab.masterclass.meet_url').PHP_EOL;"
```

If that prints an empty line, the reminder will send a blank link. Fix it before proceeding.

### 2. Re-invite the funnel to register

People who signed up while registration was closed sit in `students` with
`source=waitlist`, and past registrants mostly attend only once (~30–35% show rate). The
preferred play is to **invite both pools to register** for this session — not silently
enrol them — so we re-capture each person's goal and a fresh intent signal:

```bash
php artisan masterclass:announce --dry-run   # preview exactly who'd be invited
php artisan masterclass:announce             # send the "registration is open" invite
```

It targets current waitlisters **plus** registrants from the last 2 sessions
(`--past-sessions=N` to change the reach), and suppresses anyone already registered for this
session, anyone who already enrolled in the Accelerator, and anyone already invited (the
`masterclass_invites` ledger makes it idempotent — safe to re-run and it runs daily via
`.github/workflows/masterclass-announce.yml`). It fires the `masterclass_reinvite` n8n event,
so that Switch branch must exist (see [n8n-masterclass-flow.md](n8n-masterclass-flow.md)).

**Mind the email cap.** Hostinger allows **100 emails/day per mailbox**. The n8n
`masterclass_reinvite` branch round-robins across three senders — `hello@`, `aj@`, `taab@`
(you must create SMTP credentials for the latter two and select them on their email nodes) —
for ~300/day of headroom. But `hello@` also sends transactional mail (registration
confirmations etc.), so keep runs conservative and cap each day; idempotency carries the rest
to the next day automatically:

```bash
php artisan masterclass:announce --dry-run          # how many are eligible?
php artisan masterclass:announce --limit=270        # send up to 270 today (~90 per sender)
# next day, after the cap resets — already-invited people are skipped:
php artisan masterclass:announce --limit=270
```

Repeat daily until `--dry-run` reports nobody left. Without `--limit` the command tries to
send to everyone at once, which would blow past the per-mailbox cap and bounce.

> `masterclass:enroll-waitlist` (the older command that auto-moves waitlisters straight into
> registrations) still exists but is **deprecated** — it creates registrations with no goal
> and stale intent. Prefer `announce`.

**Marking attendance.** After a session, mark who actually showed in Admin → Masterclass
(the **Attended / No-show** toggle per row). This feeds smarter re-invite targeting over time.

### 3. Session morning

Run the day-of nudge manually (see the scheduling warning above):

```bash
php artisan masterclass:remind --throttle=1500
```

### 4. After the session

The follow-up becomes due at `ends_at + followup_after_hours` and **never expires** — but it
only reaches people while `config('taab.masterclass.date')` still points at their session.
**Send it before rolling the config to the next edition.**

```bash
php artisan masterclass:remind --throttle=1500
```

### Checking state at any time

```bash
php artisan tinker --execute="
\$s='2026-07-18';
\$t=DB::table('masterclass_registrations')->where('session_date',\$s);
echo 'total:    '.\$t->count().PHP_EOL;
echo 'reminded: '.(clone \$t)->whereNotNull('reminder_sent_at')->count().PHP_EOL;
echo 'pending:  '.(clone \$t)->whereNull('reminder_sent_at')->count().PHP_EOL;
"
```

`masterclass:remind` also reports outstanding recipients itself, so a partial send is never
silent.

---

## Recovery: a masterclass send failed

Because sends are stamped only on success, **the normal fix is simply to run the command
again** — unstamped people are retried automatically, at any headcount.

```bash
php artisan masterclass:remind --throttle=1500
```

Repeat until it reports nothing outstanding. Failures are named inline
(`! someone@example.com — HTTP 500 (will retry next run)`) and logged with n8n's response
body:

```bash
grep "Masterclass n8n rejected" storage/logs/laravel.log | tail -5
```

### If people were stamped but never received anything

This only happens when the webhook is on "Respond: Immediately" (see above). The stamps are
unreliable, so clear them and re-send:

```bash
php artisan masterclass:reset-sends --type=reminder --dry-run
php artisan masterclass:reset-sends --type=reminder
php artisan masterclass:remind --throttle=3000
```

`--type` is `reminder` | `dayof` | `followup`. `--except="a@x.com,b@x.com"` spares people who
genuinely received it — but if identifying them is slow, don't bother: a duplicate reminder
is much better than a missing Meet link. Fix the n8n response mode **first**, or you'll just
re-stamp the same lie.

### Raising the throttle

`--throttle=N` (ms between sends) overrides `send_throttle_ms` without a redeploy. Raise it
if n8n struggles under burst. With respond-when-done the pacing matters much less, since
Laravel already waits for each send.

---

## Running an Accelerator cohort

### Opening

Set the dates in `config/accelerator.php` — these drive the landing page, checkout, and
scarcity:

```php
'cohort_number'     => 2,
'cohort_cap'        => 25,
'earlybird_seats'   => 10,
'earlybird_ends_at' => '2026-07-20 23:59:59',
'cohort_starts_at'  => '2026-07-31',
'cart_closes_at'    => '2026-08-03 23:59:59',
```

Deploy, then confirm the live page shows the new dates (config cache again). Keep the
welcome email in [emails/7-accelerator-welcome.html](emails/7-accelerator-welcome.html) in
sync — it states the start date in prose.

**Pausing registration** is a runtime switch, no deploy needed: admin overview → the
Open/Paused toggle. Checkout then shows "Registration is paused" (distinct from sold out).

### Enrolling someone manually (bank transfer / offline)

Admin → Enrollments → **Manual enrol**, or:

```bash
php artisan enroll:user ada@example.com "Ada Builder"              # defaults: 79000 NGN
php artisan enroll:user ada@example.com "Ada Builder" 42000 NGN --cohort=2
```

Both route through `StudentProvisioner`, so the student gets the identical welcome flow and
LMS access as a card payment.

**Two offline cases — pick the right one:**
- **They never started checkout** → use **Manual enrol** above (mints a fresh paid row).
- **They started checkout then paid offline** (a `pending` enrollment already exists — common,
  ~3–4 per cohort) → Admin → Enrollments → row menu (⋯) → **Approve payment**. This finalizes
  the *existing* row, keeping its plan/amount/balance, grants access, sends the welcome, and —
  for installments — schedules the 2nd payment. Don't Manual-enrol these; that would create a
  duplicate and reset the balance to zero. The Approve action only appears on non-`paid` rows
  (filter by Status → pending to find them).

### A student didn't get their welcome email

Admin → Enrollments → row menu (⋯) → **Re-send welcome**.

This issues a **new temporary password** — the original is hashed and cannot be recovered —
and resets the account to it, so any password the student already set stops working. The new
password appears in the admin flash message, so you can hand it over directly if the email
fails again. The flash also tells you plainly if n8n *rejected* the send rather than
reporting a false success.

### Installments

`installments:process` runs daily at 09:00 and:
1. sends the 2nd-payment link when `installment_due_days` elapses,
2. suspends access `installment_grace_hours` after a missed due date.

Admin → Enrollments exposes **Re-send pay link** and **Mark balance paid** per student.
Suspended students are bounced to `/checkout` by `CheckEnrollment` until the balance clears.

### Running a live session (attendance)

To drive live attendance (higher-value than the recordings), each live session has an
attendance code students enter to get credit + unlock that session's playbook, and it counts
toward the completion guarantee.

1. **Before the session**, add the week's live session to `config/curriculum.php` under `live`
   with a fresh `attendance_code` (and optional `playbook_url`), and deploy. **Never put the
   code in chat** — it's read server-side and never sent to the browser, so announcing it live
   is what proves attendance.
2. **At the end of the call**, say the code aloud (e.g. "today's code is MANGO").
3. Students open the session in their dashboard → **Live Attendance** panel → enter the code →
   recorded, and the playbook link appears.
4. **Verify** in Admin → Enrollments: each student shows a `Live N` count. Attendance +
   approved checkpoints feed the "Completion Guarantee" card on their dashboard
   (threshold = `accelerator.guarantee_min_live_sessions`).

Codes are case-insensitive and each student can only mark a session once. Rotate the code
every session so a leaked one can't be reused later.

---

## Deploying

Push to `main`. GitHub Actions SSHes in, resets the working tree to `origin/main`, and runs
`deploy.sh` (migrate → publish build assets → rebuild caches). See [../DEPLOY.md](../DEPLOY.md).

**If a deploy fails, re-run it — don't assume prod is fine.** Hostinger's SSH occasionally
times out (`dial tcp … i/o timeout`). The reset can bring new code live while `migrate` never
runs, leaving prod with **new code but a missing table/column** — you'll see
`Base table or view not found` right after a push. Fix: re-run the deploy (Actions → *Deploy
to Hostinger* → **Re-run jobs**, or trigger it via *Run workflow* — it supports
`workflow_dispatch`). `deploy.sh` is idempotent (`migrate --force` + cache rebuilds), so a
re-run is always safe. Last-resort manual fix over SSH: `php artisan migrate --force`.

**Before pushing UI changes:** run `npm run build` and commit `public/build`. Hostinger has
no Node; production serves the committed assets.

**Repository push rules:** large binaries (`.pptx`) and n8n JSON exports containing live
webhook URLs have been rejected by GitHub's push protection. Don't `git add -A` blindly in
this repo — stage the files you actually changed, and scrub secrets from any n8n export
before committing it.

---

## Admin reference

| Screen | What it's for |
|---|---|
| **Overview** | KPIs + the registration Open/Paused switch |
| **Enrollments** | All students. Approve a pending offline payment, suspend/reinstate, re-send welcome, re-send pay link, mark balance paid, change cohort, manual enrol; per-student live-attendance count |
| **Checkpoints** | Approve/reject ship-to-unlock proof submissions — this is what opens the next module |
| **Masterclass** | Registrations for the current session + send status pills; mark Attended/No-show per row; CSV export |
| **Leads & Waitlist** | Every lead with source + scorecard tier/score; CSV export |
| **Free Resources** | CRUD for `/free` — add a link, publish/unpublish, see click counts |

Grant admin rights with `php artisan user:admin you@example.com` (add `--revoke` to remove).
The user must already exist.

---

## Troubleshooting quick reference

| Symptom | Likely cause |
|---|---|
| Reminder sent with a blank Meet link | `.env` set but `config:cache` not rebuilt |
| Rows say "reminded" but nobody got email | n8n webhook on "Respond: Immediately" |
| All sends fail with HTTP 500 | n8n Switch matches no branch — check the exact `type` string |
| A touch never fired at all | GitHub Actions cron gap — just run the command manually |
| Waitlisters got nothing | They're `students`, not registrations — invite them with `masterclass:announce` (they register themselves) |
| Follow-up can't reach last edition | `taab.masterclass.date` already moved on |
| Student can't log in after paying | Re-send welcome from admin (issues a new temp password) |
| Site serving old CSS/JS | `npm run build` not run / `public/build` not committed |
| Prices wrong on one page only | Something hardcoded in Blade instead of using `Accelerator` |
| `Base table or view not found` after a push | Deploy failed (SSH timeout) so `migrate` didn't run — re-run the deploy |
| Live Attendance / Telegram link missing in prod | Deploy failed, or config not re-cached — re-run the deploy |
| Student paid offline but has no access | If they started checkout, Admin → Enrollments → **Approve payment** on their pending row; if not, **Manual enrol** |
