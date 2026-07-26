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
| `ACCELERATOR_TELEGRAM_URL` | Community invite — where students post proof checkpoints |

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
'cohort_number'     => 2,   // stamped on new enrollments; >= 2 enables ship-to-unlock
'cohort_cap'        => 25,
'earlybird_seats'   => 10,
'earlybird_ends_at' => '2026-07-20 23:59:59',
'cohort_starts_at'  => '2026-07-31',
'cart_closes_at'    => '2026-08-03 23:59:59',
```

- Early-bird is active while `seats_sold < earlybird_seats` **and** `now < earlybird_ends_at`.
- Seats left = `cohort_cap - seats_sold`; at zero, checkout is disabled and a waitlist CTA shows.
- `cohort_number >= 2` turns on checkpoint gating. Cohort 1 stays ungated deliberately.
- Changing `cohort_starts_at`? Update
  [emails/7-accelerator-welcome.html](emails/7-accelerator-welcome.html) too — it states the
  date in prose.

### Installments
```php
'installment_due_days'    => 14,  // 2nd payment due this long after the 1st
'installment_grace_hours' => 24,  // suspend access this long after the due date
```

### Community
`telegram_community_url` plus per-module `telegram_threads` (forum topic deep links). Any
module left `null` falls back to the main group.

### Testimonials
```php
'testimonials' => [],   // shape: ['name','role','quote','photo','is_published']
```
Empty renders a graceful empty state. **Never fabricate entries.**

---

## `config/taab.php`

### Session
```php
'date'                => '2026-08-01',   // selects registrations; null = "date to be announced"
'time'                => '9:00 AM – 11:00 AM WAT',   // display string only
'registration_closes' => '2026-07-31',
'starts_at'           => '2026-08-01 09:00',   // real datetimes — these drive reminders
'ends_at'             => '2026-08-01 11:00',
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
