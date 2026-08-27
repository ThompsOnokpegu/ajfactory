# Configuration

What each setting controls and what breaks without it. Standard Laravel keys (`APP_*`,
`DB_*`, `MAIL_*`, `SESSION_*`, cache/queue/redis/AWS) behave normally and aren't repeated
here — this covers what's specific to this project.

> **Production caches config.** Any `.env` change requires `php artisan config:cache` to take
> effect. A value that exists in `.env` but wasn't cached reads as `null` — this has shipped
> blank Meet links in a live reminder. After changing `.env`, always rebuild and verify:
>
> ```bash
> php artisan config:cache
> php artisan tinker --execute="echo config('taab.masterclass.meet_url').PHP_EOL;"
> ```

---

## Environment variables

### n8n webhooks
All outbound automation. If a URL is unset the app logs and skips — it never crashes, but
nothing gets sent.

| Var | Used for |
|---|---|
| `N8N_STUDENT_WEBHOOK_URL` | **The main one.** All masterclass events, TAAB leads, scorecard results, student signups. Routed by the `type` field. |
| `N8N_ENROLLMENT_WEBHOOK` | Paid enrolment welcome flow (`enrollment_finalized`) |
| `N8N_INSTALLMENT_WEBHOOK` | 2nd-payment links and overdue notices. Falls back to `N8N_ENROLLMENT_WEBHOOK` if unset. |
| `N8N_WEBHOOK_URL` | Legacy/general endpoint |

Every receiving node must use **Respond: When Last Node Finishes** — see
[operations.md](operations.md#️-the-n8n-2xx-trap).

### `APP_URL` - not a harmless standard key

**It must be the exact public origin, including `https://`.** Signed URLs are built with
`URL::signedRoute()`, which takes its host from the incoming request - but there is no
request in a **console** run, so it falls back to `config('app.url')`. Anything scheduled
(`installments:process` runs via GitHub Actions -> SSH -> artisan) therefore inherits
`APP_URL`, and Laravel's default is `http://localhost`.

Real incident: suspension emails carried
`http://localhost/installment/117/pay?signature=...`. The signature is an HMAC over the
**whole absolute URL**, and the route is `->middleware('signed')`, so such a link can never
be repaired by rewriting the host - it returns 403 Invalid signature. The link has to be
regenerated.

Same trap with the scheme: if `APP_URL` is `http://` and the server redirects to `https://`,
every signed link breaks with the identical 403, because the signature was computed for the
`http` URL. Set the scheme you actually serve.

```bash
# after changing APP_URL - config is cached in production
php artisan config:cache
php artisan tinker --execute=\"echo config('app.url').PHP_EOL;\"
```

`installments:process` now refuses to send a pay link whose host is localhost and logs an
error instead, leaving the student unstamped for the next run - see
[operations.md](operations.md).

### Payments
| Var | Notes |
|---|---|
| `PAYSTACK_SECRET_KEY`, `PAYSTACK_PUBLIC_KEY` | Primary (NGN). Amounts are charged in **kobo** (× 100) at the call site. |
| `FLW_SECRET_KEY`, `FLW_PUBLIC_KEY`, `FLUTTERWAVE_SECRET_HASH` | Flutterwave (USD path). The secret hash validates inbound webhooks. |

Both webhooks verify server-side before granting access. Never grant on a client-side
success callback.

### TAAB masterclass
| Var | Notes |
|---|---|
| `TAAB_MEET_URL` | Google Meet link sent in the reminder + day-of nudge. **Set per session.** |
| `TAAB_WA_GROUP_URL` | WhatsApp group invite sent in the reminder |
| `TAAB_RECORDING_URL` | Optional; included in the follow-up when set |

### Accelerator
| Var | Notes |
|---|---|
| `ACCELERATOR_TELEGRAM_URL` | Community group invite (fallback for the #wins / per-module threads) |

### Video delivery
| Var | Notes |
|---|---|
| `BUNNY_LIBRARY_ID`, `BUNNY_API_KEY` | Bunny.net stream library for course video |

### Deployment secrets (GitHub repo secrets, not `.env`)
`HOSTINGER_SSH_HOST` · `HOSTINGER_SSH_USER` · `HOSTINGER_SSH_PORT` · `HOSTINGER_SSH_KEY`

Used by both `deploy.yml` and `scheduler.yml`.

---

## `config/accelerator.php`

Single source of truth for the paid offer. **Never hardcode any of this in Blade** — the
landing page and checkout must agree, and derived values belong in
`App\Support\Accelerator`.

### Pricing
```php
'price_full'        => 79000,   // NGN
'price_earlybird'   => 69000,
'installment_each'  => 42000,   // × 2
'installment_count' => 2,
'currency'          => 'NGN',
'usd' => ['price_full' => 57, 'price_earlybird' => 50, 'installment_each' => 30],
```
USD values are **fixed, not auto-converted** (implied ≈ ₦1,400/$). Update them by hand if
the rate moves materially.

### Cohort & scarcity
```php
'cohort_number'     => 3,   // stamped on new enrollments; >= 2 enables ship-to-unlock
'cohort_cap'        => 30,  // raised from 25 on 25 Aug 2026, before the launch campaign sent
'earlybird_seats'   => 10,
'earlybird_ends_at' => '2026-08-31 23:59:59',  // Monday 31 Aug 2026
'cohort_starts_at'  => '2026-09-12',           // Saturday 12 Sep 2026
'cart_closes_at'    => '2026-09-14 23:59:59',  // Monday 14 Sep 2026
```

- Early-bird is active while `seats_sold < earlybird_seats` **and** `now < earlybird_ends_at`.
- Seats left = `cohort_cap - seats_sold`; at zero, checkout is disabled and a waitlist CTA shows.
- `cohort_cap` is **public** — it renders as "N of {cap} seats left" on the landing page and
  checkout. Move it between launches, not during one: raising it after leads have seen a
  smaller count is visible fake scarcity to an audience sold on "no surprises".
- `cohort_number >= 2` turns on checkpoint gating. Cohort 1 stays ungated deliberately.
- Changing `cohort_starts_at`? Four other things follow from it, and none of them error
  if you forget:
  1. [emails/7-accelerator-welcome.html](emails/7-accelerator-welcome.html) states the date in
     prose — and it is a *mirror* of the template in n8n, so paste the change there too.
  2. `curriculum.php` → `live` needs sessions dated **after** the new start, or the completion
     guarantee becomes unwinnable (see below).
  3. `cart_closes_at` and any coupon `expires_at` are usually pinned to it.
  4. **Write the weekday in the comment and check it.** Cohort 3 was first set with the
     comments "Monday 29th August" and "Friday 12th September" against dates that were both
     Saturdays. Config comments are what everyone reads when writing the launch copy.
- The landing page picks its own tense from `Accelerator::hasStarted()` — before the start it
  says "starts <date>", after it says "already running, self-paced, you can still catch up".
  Don't hardcode either phrasing.
- **Never hardcode the cohort number in a Blade file.** Use `Accelerator::cohortLabel()`
  ("Cohort 3") or `cohortLabelPadded()` ("Cohort 03"). Cohort 2 shipped with the number typed
  into eight places across the landing page, checkout, and homepage.

### Installments
`installment_due_days` is counted from the **cohort start**, not from when the student paid
— see `Accelerator::installmentDueAt()`. Counting from payment punished early enrollment: a
student who paid two weeks before the cohort opened had to clear their balance before they had
really begun, while someone who paid on day one got the full window. The anchor is the **later
of (cohort start, payment date)**, so early birds measure from the start line and nobody who
joins mid-cohort gets less than the full window either.

**"Cohort start" means the STUDENT'S cohort**, resolved via `Accelerator::startFloorFor()`,
which returns `null` for any cohort earlier than the one being sold. Pass the cohort when
recomputing an existing student; omit it at checkout, where they're joining the current one.
Anchoring everyone to the global `cohort_starts_at` meant that opening Cohort 3 for 12 Sep
would have had `installments:realign` push mid-course Cohort 2 deadlines out to 3 Oct and
un-suspend students suspended for non-payment. Same root cause as the module-01 lockout.

The due date is **stamped once** onto `second_payment_due_at` at payment time, so changing
`installment_due_days` only affects enrollments created afterwards. Raising it from 14 to 21
left earlier students on the old window — run `php artisan installments:realign` to bring
everyone onto the current rule (see [operations.md](operations.md)).

```php
'installment_due_days'    => 21,  // window length; anchored to the cohort start
'installment_grace_hours' => 24,  // suspend access this long after the due date
```

### Checkout coupons
`accelerator.php` → `coupons`, keyed by the code the buyer types (case-insensitive). The
discount is computed **server-side** from this config and re-validated at the final
price-lock, so a discounted price can never be injected from the client. Each entry:
```php
'TAAB25' => [
    'type'       => 'percent',              // 'percent' | 'fixed'
    'value'      => 25,                       // percent: a number; fixed: ['NGN'=>10000,'USD'=>8]
    'plans'      => ['full', 'installment'],  // plans it applies to
    'expires_at' => '2026-09-14 23:59:59',    // optional (Africa/Lagos) - usually cart close
    'label'      => 'TAAB masterclass — 25% off',
],
```
The discount applies to the plan **total** at the current price (early-bird included). The
applied code + discount are recorded on the enrollment (`coupon_code`, `discount_amount`); the
charged `amount` is already the discounted figure the webhook verifies.

### Live-session attendance & completion guarantee
- `accelerator.php` → `guarantee_min_live_sessions` — how many weekly live sessions a student
  must attend (on top of finishing all module checkpoints) to satisfy the completion
  guarantee. Shown on the dashboard progress card; the guarantee itself is honoured by a human.
  **This must stay below the number of sessions a student can actually still attend**, which
  is *not* the session count in `curriculum.php`. Attendance exists only for sessions that ran
  **after that cohort started** *and* had an `attendance_code` set, and there is no retroactive
  path. Real incident: it was set to 6 while Cohort 2 had exactly 6 attendable sessions
  (`live-05`..`live-10`, since 01–04 predated both the cohort and the feature) — so a single
  absence failed the guarantee for everyone. `LiveAttendanceTest` now guards the threshold.
  **Re-count on every cohort launch.** Cohort 3 (starts Sat 12 Sep 2026) would have opened with
  *zero* attendable sessions — the archive stopped at `live-10` on the start date itself — so
  `live-11`..`live-16` (Saturdays 19 Sep to 24 Oct 2026) were added to give the threshold of 4
  a six-session pool. Sessions carried over from the previous cohort never count.
- Per **live** session in `curriculum.php`: `attendance_code` (the code AJ announces at the
  **end** of the call — set a fresh one each session, never type it in chat; it's read
  server-side and never sent to the browser) and optional `playbook_url` (unlocked once the
  student marks attendance). Leave `attendance_code` unset to keep attendance closed.
  Make the code **random and unguessable** — it's committed to git and it's the only thing
  standing between "attended" and "guessed", so avoid words derived from the session title.
  `playbook_url` must be **unset** when there's no playbook: any non-empty value (including a
  `{{TODO}}` placeholder) renders a live button and ships students a dead link.

### Curriculum ordering (`config/curriculum.php`)
Students see modules in **array order**; ship-to-unlock chains each module to the previous
one in that array. The number in `title` is display text and nothing reads it.

**`id` is a stable key, not a position.** It's stored on `Checkpoint.module_id` and inside
`Enrollment.completed_lessons`, and it keys `accelerator.telegram_threads` and
`reviews.stages[].after_module`. To reorder or insert a module: change array order, change
the `title` numbers, give any new module its **own** id — and leave every existing id alone.

Ids therefore drift from their displayed numbers on purpose (`module-03` is "Module 04"), and
each `telegram_threads` line carries a comment naming its displayed module. Renumbering ids to
"fix" this reassigns real student progress. Note the knock-on when a module is **removed**: any
`after_module` still pointing at it silently disables that review stage forever. `CurriculumTest`
asserts the module count, id uniqueness, and that every stage anchor and thread key resolves.

### Community
Three keys, one per role in the checkpoint flow:
- `telegram_wins_url` — the `#wins` thread. **Build proof / checkpoints go here** — the
  dashboard "Ship it to unlock" panel links students to it.
- `telegram_threads` — per-module `#module-n-help` threads (questions), surfaced as the
  "Stuck? Ask in the module thread" link. **Not** where proof goes.
- `telegram_community_url` (`ACCELERATOR_TELEGRAM_URL`) — the group invite; fallback for
  either of the above when unset.

### Testimonials
```php
'testimonials' => [],   // shape: ['name','role','quote','photo','is_published']
```
Empty renders a graceful empty state. **Never fabricate entries.** Real quotes come from
`/admin/reviews` — only rows marked **Quotable** there (student consented *and* was happy),
copied across by hand using the credit line admin displays.

---

## `config/reviews.php`

The staged in-course "soft ask" (see `student_reviews` in
[architecture.md](architecture.md)). Copy lives here so questions can be reworded without
touching component code.

### Ask behaviour
```php
'snooze_days' => 5,          // wait this long before re-showing a dismissed stage
'max_dismissals' => 2,       // declines allowed before we stop asking that stage entirely
'unhappy_at_or_below' => 3,  // ratings ≤ this skip the consent ask and route to "what should we fix?"
'credit_options' => [...],   // full | first | anon — how a consenting student is attributed
```

### Stages
Each entry needs `key`, `after_module`, `enabled`, panel copy (`eyebrow` / `headline` /
`intro`), and `questions` (each with `key`, `label`, `placeholder`, `required`, `rows`).

- `after_module` is a **`config/curriculum.php` module id** — the stage becomes due when that
  module's checkpoint is approved. A typo silently means the stage never fires; there's no
  error to see. Verify the id exists after any curriculum reshuffle.
- **Never rename a `key`** (stage or question) that already has rows — stage keys are stored
  on the row and question keys inside the answers JSON, so a rename orphans existing responses
  and admin falls back to showing the raw key instead of the question.
- Set `enabled => false` to silence a stage without deleting it (keeps its history readable).
- Stages are evaluated **newest-first**, so a student who blew past module 05 without
  answering the module 01 ask gets the midpoint questions rather than stale ones.

---

## `config/taab.php`

### Session
```php
'date'                => '2026-08-29',   // selects registrations; null = "date to be announced"
'time'                => '9:00 AM - 11:00 AM WAT',   // display string only
'registration_closes' => '2026-08-28',
'starts_at'           => '2026-08-29 09:00',   // real datetimes — these drive reminders
'ends_at'             => '2026-08-29 11:00',
'timezone'            => 'Africa/Lagos',
```

`date` and the display strings feed the page; `starts_at`/`ends_at` feed the scheduler. Keep
them consistent — they're separate fields and can drift.

`timezone` is parsed explicitly because the app default is UTC; without it every reminder
window shifts by an hour.

**`date` also scopes `masterclass:remind`.** Rolling it to the next session makes the
previous edition's registrants unreachable — send their follow-up first.

### Reminder offsets
```php
'reminder_lead_hours'  => 24,   // Meet link + WhatsApp group
'dayof_lead_hours'     => 2,    // "starting soon" nudge  ← only a 2-hour window
'followup_after_hours' => 2,    // post-session, relative to ends_at; never expires
'send_throttle_ms'     => 400,  // pause between sends; override with --throttle=N
```

### Lead-magnet tools
`fx_rate` (₦ per $1, used by the ROI calculator), `accelerator_url`, `masterclass_url`.

---

## Runtime settings (database, no deploy needed)

The `Setting` model is a key/value store for flags you flip while the app is live.

| Key | Controls |
|---|---|
| `accelerator_registration_open` | Pauses/resumes checkout. Toggle from the admin overview; read via `Accelerator::registrationOpen()`. |

Use this pattern for anything an operator should change without a deploy.

---

## Scheduled tasks

Defined in `routes/console.php`:

```php
Schedule::command('installments:process')->dailyAt('09:00')->timezone('Africa/Lagos');
Schedule::command('masterclass:remind')->everyFifteenMinutes()->timezone('Africa/Lagos');
```

**These definitions are aspirational on the current host.** Hostinger's cron doesn't run, so
`masterclass:remind` is driven by `.github/workflows/scheduler.yml` calling it directly —
and GitHub drops most scheduled runs. Read
[operations.md](operations.md#️-scheduling-reality--read-this-first) before relying on
anything time-sensitive.
