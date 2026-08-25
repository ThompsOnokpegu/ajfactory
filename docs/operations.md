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

> **Send the previous edition's follow-up FIRST.** It only reaches people while
> `taab.masterclass.date` still points at their session (see step 4), so rolling the config
> strands anyone still unsent. Recovery is to set `date` back, run `masterclass:remind`, then
> roll forward again - but it's much easier to just do it in this order.

Edit `config/taab.php`:

```php
'date'                => '2026-08-29',        // the session day (a Saturday)
'registration_closes' => '2026-08-28',        // the Friday before
'starts_at'           => '2026-08-29 09:00',  // real datetimes drive the reminders
'ends_at'             => '2026-08-29 11:00',
```

**Don't hardcode this date in a test.** `MasterclassAnnounceTest` and `TaabPrefillTest` freeze
"now" relative to the session, and both broke on the first roll because they pinned literal
dates - one silently flipped from testing "registration closed" to testing "registration
open". They now derive their frozen time from the config via `openRegistrationMoment()` /
`closedRegistrationMoment()`. Keep it that way.

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

**From the Actions tab (no SSH needed)** — Actions → *Masterclass re-invite
(registration-open announcement)* → **Run workflow**. Inputs: `dry_run`, `limit`
(defaults to 270), `past_sessions`, `throttle`. Run it once with `dry_run` ticked to see
the audience, then again without.

On the server:

```bash
php artisan masterclass:announce --dry-run          # preview exactly who'd be invited
php artisan masterclass:announce --limit=270        # send the "registration is open" invite

# Reach the WHOLE list, not just the masterclass waitlist:
php artisan masterclass:announce --dry-run --sources=waitlist,accelerator_waitlist,scorecard,roi,tool-stack --past-sessions=6
```

> **`--sources` defaults to `waitlist` alone, which is a small slice of `students`.** The
> other capture sources (`accelerator_waitlist`, `scorecard`, `roi`, `tool-stack`) are just
> as invitable and were never reached until 25 Aug 2026 — a ~500-row list was being worked as
> if it were ~50. Check what you actually have before choosing:
> `SELECT source, COUNT(*) FROM students GROUP BY source ORDER BY 2 DESC;`
>
> The pool filters on `source` only. It used to also require `interest='masterclass'`, which
> is true for every `source=waitlist` row but false for most others (the accelerator waitlist
> writes `interest='accelerator'`, the scorecard writes `interest='scorecard'`) — so keeping
> that clause would have made `--sources` appear to work while matching nobody. A misspelled
> source now prints a warning rather than silently inviting zero people.
>
> The ledger stamps the **real** source in `masterclass_invites.audience`, so you can see
> which pool converted: `SELECT audience, COUNT(*) FROM masterclass_invites GROUP BY audience;`

It targets `students` rows by **source** (`--sources`, default `waitlist`) **plus** registrants
from the last 2 sessions (`--past-sessions=N` to change the reach), and suppresses anyone
already registered for this session, anyone who has **paid** for the Accelerator, and anyone
already invited (the
`masterclass_invites` ledger makes it idempotent — safe to re-run and it runs daily via
`.github/workflows/masterclass-announce.yml`). It fires the `masterclass_reinvite` n8n event,
so that Switch branch must exist (see [n8n-masterclass-flow.md](n8n-masterclass-flow.md)).

> **"Paid" is load-bearing there.** The checkout writes an `enrollments` row with
> `status='pending'` the instant the pay button is clicked, before the payment modal opens —
> so an abandoned cart looks exactly like a buyer in that table. Until 25 Aug 2026 the
> suppression set was an unfiltered pluck of `enrollments`, which meant **every abandoned
> checkout was silently excluded from every invite, permanently**, with nothing in any log to
> show for it. Those are the hottest leads in the database. The filter now excludes
> `status='pending'` rather than selecting `status='paid'`, so an unexpected status still
> fails safe (suppressed). `MasterclassAnnounceTest` guards both directions.

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

Repeat daily until `--dry-run` reports nobody left.

`--limit` is not optional in practice. Without it the command sends to everyone at once,
blowing past the per-mailbox cap — and the overflow bounces *asynchronously*, so n8n still
returns 2xx and Laravel stamps those people as invited. They then never retry, which is the
one failure mode the unstamped-on-failure design can't recover from. **The GitHub Actions
workflow therefore defaults `--limit` to 270 on both the manual and the scheduled run**, so
an unattended daily tick can't do this. Raise it only when you know the day's quota is
clear.

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

Setting the config dates is step 1 of 6, not the whole job. The other five don't fail loudly,
so work the list. (Cohort 3, opened 19 Aug 2026 for a 12 Sep start, is the worked example.)

**1. Set the dates** in `config/accelerator.php` — these drive the landing page, checkout, and
scarcity:

```php
'cohort_number'     => 3,
'cohort_cap'        => 30,
'earlybird_seats'   => 10,
'earlybird_ends_at' => '2026-08-31 23:59:59',  // Monday 31 Aug 2026
'cohort_starts_at'  => '2026-09-12',           // Saturday 12 Sep 2026
'cart_closes_at'    => '2026-09-14 23:59:59',  // Monday 14 Sep 2026
```

**Check every weekday you write in a comment against a calendar.** Cohort 3's dates first
landed labelled "Monday 29th August" and "Friday 12th September" when both were Saturdays.
Nothing validates these, and they're what you'll copy into the launch emails and ads.

**2. Bump the coupon expiry.** `coupons.*.expires_at` is normally pinned to `cart_closes_at`.
A coupon that outlives the cart still discounts; one that dies early silently stops working
mid-launch.

**3. Add the live sessions** to `config/curriculum.php` → `live`, dated **after** the cohort
start. This is the step that bites: attendance only exists for a session that runs after the
student's cohort began *and* has an `attendance_code`, so sessions inherited from the previous
cohort count for nobody. Cohort 3 was about to open with the archive ending at `live-10` on
the start date itself — every new student would have had **zero** attendable sessions against
a `guarantee_min_live_sessions` of 4, i.e. a completion guarantee nobody could win, with no
retroactive fix. Schedule at least threshold + 2 sessions inside the cohort window.

**4. Update the copy that isn't derived.** The cohort number now comes from
`Accelerator::cohortLabel()` / `cohortLabelPadded()` and the start/close dates from
`cohortStartsAt()` / `cartClosesAt()`, so the landing page, checkout and homepage follow the
config on their own — but grep for a hardcoded number anyway before you ship. The landing page
switches between "starts <date>" and "already running, self-paced" off
`Accelerator::hasStarted()`, so don't hand-write either tense.

**5. Sync the welcome email.** [emails/7-accelerator-welcome.html](emails/7-accelerator-welcome.html)
states the cohort number and start date in prose, in the subject line, and in the footer. It is
a **mirror** of the template that lives in n8n — editing the file in this repo changes nothing
students receive until you paste it into the n8n enrolment workflow.

**6. Build, deploy, verify.** `npm run build` + commit `public/build`, deploy, then load
`/accelerator` and `/checkout` on production and read the dates back. Config is cached in
production, so a stale `config:cache` shows the *old* cohort with a straight face.

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

**Changed `installment_due_days`, or need to fix an unfair deadline?** The due date is
stamped once at payment, so a config change never reaches existing students. Realign them:

```bash
php artisan installments:realign --dry-run   # table of exactly what would move
php artisan installments:realign             # apply
```

It recomputes from **the student's own cohort start** (`Accelerator::installmentDueAt($anchor,
$cohort)`) and **never shortens an existing deadline**, so it can't make anyone suddenly overdue and is safe to re-run. It also
undoes what the old deadline already triggered: a student flipped to `link_sent` goes back to
`pending` (so `installments:process` sends the link on the real date) and anyone suspended is
un-suspended — provided the new deadline plus grace hasn't already passed. Genuinely overdue
students stay suspended.

`installments:process` runs daily at 09:00 and:
1. sends the 2nd-payment link when `installment_due_days` elapses,
2. suspends access `installment_grace_hours` after a missed due date.

Both fire n8n events — `installment_due` and `installment_overdue_suspended` — handled by
the **Accelerator - Installments & Balance** workflow. They route on `event` (not `type`),
and the n8n side is documented in [n8n-enrollment-flow.md](n8n-enrollment-flow.md).

If a student reports no pay link, check `config('services.n8n.installment_webhook')` is
actually set before anything else: unset, it falls back to the enrolment webhook and the
events go to a workflow with no branch for them.

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

**Pick the code carefully.** It lives in a git-committed config file and it's the only thing
separating "was on the call" from "guessed it". Use something random like `TUNDRA-907` —
never a word anyone could infer from the session title (a session about the playbook had the
code `PLAYBOOK`, which anyone could have guessed straight into the completion guarantee).

**Before changing `guarantee_min_live_sessions`, count what's actually attendable** — see
[configuration.md](configuration.md). Sessions that ran before a cohort started, or that had
no `attendance_code`, can never be credited, and there's no retroactive fix.

### Selling a written guide

The guides are gated by default and free to Accelerator students. To sell one to
everybody else, create the product in **Admin → Resources**:

| Field | Value |
|---|---|
| `url` | the guide path, e.g. `/guides/n8n-on-google-cloud` — this is what links the Resource to the gate |
| `price` | NGN. A price of 0/null leaves it un-sellable and the locked page just points at the Accelerator |
| `price_usd` | optional |
| `is_published` | on |

One row is enough for both guides — a purchase of either unlocks both, deliberately.

Buyers land on `/resources/access/{token}` after paying (also emailed), and the "Open
your resource" button carries the token that unlocks the guide for their browser. **Don't
hand anyone a bare `/guides/…` link** — without the token they'll hit the sales page
having already paid. Send them their access link instead.

To check the gate is actually on, request a guide signed out:

```bash
curl -s -o /dev/null -w '%{http_code}\n' https://ajbuildai.com/guides/n8n-on-google-cloud
curl -s https://ajbuildai.com/guides/n8n-on-google-cloud | grep -c 'this guide is for members'
```

`200` plus a `1` from the grep is correct — the sales page. A `0` means the guide body is
being served to the public.

### Harvesting reviews for the next cohort's launch

Feedback is collected **during** the cohort, not begged for at the end. Three staged asks
appear in the student's dashboard once the matching checkpoint is approved (`first-win` after
module 01, `midpoint` after 05, `finish` after 09). Nothing to run — it's automatic.

**What you do:**

1. **Approve checkpoints promptly.** The ask is triggered by approval, so a backlog in
   Admin → Checkpoints is also a backlog of reviews you're not collecting. Approving within a
   day catches students at the moment they're still buzzing about the thing they built.
2. **Check Admin → Reviews weekly**, on the **Needs a call** tab first. A rating ≤ 3 is a
   student on the way to churning or to a refund request — that tab exists so you hear about
   it in week 2 rather than in a review after the cohort. Reach out personally. **These are
   never marketing material**, no matter how the response is phrased.
3. **Build launch copy from the Quotable tab.** Those students rated ≥ 4 *and* ticked the
   consent box. Each card shows the exact credit line to use (`Chidi O., Cohort 2`) —
   it honours their full-name / first-name / anonymous choice, so use it verbatim.
4. **Copy quotes into `config/accelerator.php` → `testimonials` by hand.** There is no bulk
   import on purpose: a quote going on the sales page should be a decision, and raw answers
   are usually more useful trimmed. Never edit a quote into saying something the student
   didn't say.

   > **Read the quote against the rest of the page before publishing it.** A Cohort 2
   > answer credited "the OpenAI API" — this course teaches Gemini, and
   > `requirements-costs.blade.php` advertises that key at ₦0. Publishing it would have put
   > a testimonial naming a paid API it doesn't teach on the same page as the costs table.
   > You can't correct it without putting words in their mouth, so the fix is to pick a
   > different answer from the same student.

   > **The Proof section on `/accelerator` was commented out** from launch until 25 Aug 2026
   > while the array was empty. Populating the config is only half the job — if that block
   > is ever hidden again, quotes render nowhere and nothing errors. `AcceleratorProofTest`
   > asserts against the rendered page for exactly that reason. The `@else` branch is a
   > graceful CTA empty state, so the section is safe to leave enabled permanently.

**Which answers map to which piece of Cohort 3 copy:**

| Question | Use it for |
|---|---|
| `before` — what they weren't sure about | The objection block on the landing page. It's your audience's fear in their own words. |
| `win` — what they got working in module 1 | Proof that the first two weeks deliver. Specific builds beat "great course". |
| `worth_it` — what they'd tell a friend | Price-objection copy, and ad hooks. |
| `result` — concrete outcome | The strongest asset you have. Also the most fragile — **never** stretch it. |
| `who_for` — who should join | Ad targeting and the "is this for me?" section. |
| `one_line` | Short-form social and testimonial cards. |

Tuning lives in `config/reviews.php` (see [configuration.md](configuration.md)) — reword
questions, change `snooze_days`, or set a stage's `enabled => false`. If response rate is low,
reword the questions before adding more asks: `max_dismissals` exists so the app never nags,
and raising it costs more goodwill than the extra responses are worth.

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
| **Reviews** | Staged in-course feedback. **Quotable** = consented + happy, safe for marketing (credit line shown per row). **Needs a call** = rated ≤ 3, reach out, never publish |
| **Snippets** | Prompts / code / JSON students copy from their dashboard. Pin one to a module, or leave the module blank to show it on every module. New snippets publish immediately; **Publish** toggles a draft. Editing never silently republishes a draft |
| **Masterclass** | Registrations for the current session + send status pills; mark Attended/No-show per row; CSV export |
| **Leads & Waitlist** | Every lead with source + scorecard tier/score; CSV export |
| **Resources** | CRUD for `/free` — add a link, publish/unpublish, **pin to top**, see click counts. Set a **price** to sell it: the link is gated behind a one-off checkout and only revealed after payment (needs a `resource_purchased` n8n branch to email the buyer). Optional USD price for Flutterwave. |

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
| Nobody is being asked for a review | Checkpoints not approved yet (that's the trigger), stage `after_module` id doesn't match `curriculum.php`, or the cohort is legacy Cohort 1 |
| Waitlisters got nothing | They're `students`, not registrations — invite them with `masterclass:announce` (they register themselves) |
| Follow-up can't reach last edition | `taab.masterclass.date` already moved on |
| Student can't log in after paying | Re-send welcome from admin (issues a new temp password) |
| Site serving old CSS/JS | `npm run build` not run / `public/build` not committed |
| Prices wrong on one page only | Something hardcoded in Blade instead of using `Accelerator` |
| `Base table or view not found` after a push | Deploy failed (SSH timeout) so `migrate` didn't run — re-run the deploy |
| Live Attendance / Telegram link missing in prod | Deploy failed, or config not re-cached — re-run the deploy |
| Student paid offline but has no access | If they started checkout, Admin → Enrollments → **Approve payment** on their pending row; if not, **Manual enrol** |
