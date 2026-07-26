# Architecture

How the application is put together: the domain model, the routes, the two funnels, and
the conventions that keep them consistent.

---

## 1. The two products

Everything in this codebase serves one of two products. Keeping their positioning distinct
matters — it has been got wrong before.

| | **TAAB Masterclass** | **AI Automation Accelerator** |
|---|---|---|
| Price | Free | ₦79,000 (early-bird ₦69,000, or ₦42,000 × 2) |
| Format | One live 2-hour session | 6-week cohort, self-paced + live clinics |
| Purpose | **Clarity before committing** — gauge readiness, costs, skills, mindset | **Building** — 9 production automations + the playbook to sell them |
| Brand | Dark + lime `#c8f064`, Syne / DM Sans | Dark + cyan `#06b6d4`, Space Grotesk |
| Config | `config/taab.php` | `config/accelerator.php` |

**The masterclass is not a build session.** It sells clarity and honest self-assessment; the
three lead-magnet tools exist to *gauge readiness*, not to be built. Building belongs to the
Accelerator. Marketing copy that promises "come build bots" attracts the wrong people and
misrepresents the session.

Legal entity is **Deepr Web Services**; `ajbuildai.com` and `hello@ajbuildai.com` are the
public identity. The Accelerator pages deliberately use the Deepr + cyan look rather than a
separate "AJBuilds AI" teal brand — that was an explicit decision, don't "fix" it.

---

## 2. Domain model

```
User ──1:1?── Enrollment ──1:N── Checkpoint
 │                │
 │                └── plan_type, balance_due, cohort, access_suspended
 │
 └── is_admin

Student                  every lead, from any source (the CRM table)
MasterclassRegistration  a person registered for ONE session_date
Lead                     agency/business enquiries (separate funnel)
Resource                 a free downloadable shared on /free
Setting                  key/value runtime flags (e.g. registration on/off)
```

### `Enrollment`
The record of a paid seat, keyed by email. Carries the plan (`full` / `installment`),
`balance_due`, `second_payment_status`, `cohort`, and `access_suspended`.

`shipToUnlockEnabled()` returns `cohort >= 2` — Cohort 1 stays fully open so existing
students are never retroactively locked out of modules they already had.

### `Checkpoint`
Proof-of-work submitted per module. `status` gates the next module: a student must have an
**approved** checkpoint for module *N* before *N+1* opens. Admin reviews these at
`/admin/checkpoints`. This is the "ship-to-unlock" mechanic.

### `Student` — the lead table
Every lead lands here regardless of source, deduplicated by email. Two fields carry the
segmentation:

- `interest` — what they want (`masterclass`, `accelerator`, `scorecard`, …)
- `source` — where they came from (`registration`, `waitlist`, `scorecard`,
  `accelerator_waitlist`, the tool slugs, …)

Plus `scorecard_tier` / `scorecard_score` when they've completed the readiness scorecard, so
the admin can see who's landing 🟢 / 🟡 / 🔴.

**Convention:** enriching a lead (e.g. adding a scorecard result) must not silently
reclassify their original `interest`/`source`. Only deliberate transitions — like
`masterclass:enroll-waitlist` moving `waitlist` → `registration` — should rewrite them.

### `MasterclassRegistration`
One row per person **per `session_date`**, so the same person can attend multiple editions.
Carries the three idempotency stamps that make reminders safe to re-run:

`reminder_sent_at` · `dayof_sent_at` · `followup_sent_at`

Plus `attended` / `attended_at` — set from the admin Masterclass screen. Only ~30–35% of
registrants show, so attendance is what lets the re-invite flow eventually target no-shows.

`masterclass:remind` selects rows by `session_date = config('taab.masterclass.date')`.
**Changing that config value makes the previous edition's registrants unreachable** by the
command — send their follow-up before rolling over to the next session.

### `masterclass_invites` — re-invite ledger
Idempotency for `masterclass:announce`: one row per (`email`, `session_date`) recording that
someone was nudged to register for a session. Because the target hasn't registered yet, the
stamp can't live on `MasterclassRegistration` — this table is where "already invited" lives,
so a re-run (or the daily tick) never double-invites.

Each row also carries a random `token` embedded in the re-invite link (`/taab?i=<token>`).
On arrival, `TaabController@hub` resolves it to pre-fill the registration form with what we
already hold for that person — identity + `background` from their latest registration, or
name/WhatsApp from their waitlist row. `goal` is intentionally never pre-filled, so re-invites
still capture a *fresh* goal (the reason they're sent to register rather than auto-enrolled).

---

## 3. Derived state lives in `app/Support/`

Blade and Livewire must never compute pricing, seat counts, or session windows themselves —
the landing page and checkout would drift apart.

### `App\Support\Accelerator`
| Method | Returns |
|---|---|
| `registrationOpen()` / `setRegistrationOpen()` | the admin on/off switch (backed by `Setting`) |
| `seatsSold()` / `seatsLeft()` / `isSoldOut()` | scarcity, derived from enrollments |
| `earlybirdActive()` | `seats_sold < earlybird_seats` **and** `now < earlybird_ends_at` |
| `fullPrice()` / `regularFullPrice()` / `installmentEach()` / `installmentTotal()` | current price, NGN or USD |
| `cohortStartsAt()` | parsed start date |
| `publishedTestimonials()` | only `is_published` entries — never fabricate these |

### `App\Support\Masterclass`
`session()`, `startsAt()`, `endsAt()`, `sessionLabel()`, `registrationOpen()` — all parsed in
the configured timezone (`Africa/Lagos`), because the app default is UTC and naive parsing
silently shifts the reminder windows by an hour.

### `App\Support\StudentProvisioner`
The single path that turns a payment into a student: creates the `User` (with a random temp
password), upserts the paid `Enrollment`, and fires the n8n welcome flow. Used by the
Paystack/Flutterwave webhooks, the admin manual-enrol form, and `enroll:user`, so an offline
enrolment behaves exactly like a verified card payment.

`resendWelcome($enrollment, $issueNewPassword = true)` re-fires that flow for one student.
Because temp passwords are hashed at creation, **the original cannot be replayed** — a
re-send issues a new one and resets the account to it.

---

## 4. Routes

### Public marketing
| Route | Purpose |
|---|---|
| `/` | agency landing (business clients) |
| `/accelerator` → `/checkout` | the paid offer and its checkout |
| `/taab` | masterclass hub + registration/waitlist form (`TaabController@hub`; `?i=<token>` pre-fills for re-invited leads) |
| `/taab/scorecard`, `/taab/roi-calculator`, `/taab/tool-stack` | lead-magnet tools |
| `/free`, `/r/{resource}` | free resource hub + click-tracking redirect |
| `/links`, `/builders` | link-in-bio, TikTok waitlist |
| `/terms` | legal |

### Form endpoints (POST, JSON)
`/taab/register` · `/taab/waitlist` · `/taab/lead` · `/taab/scorecard`

All follow the same shape: validate → upsert into `students` → fire a typed n8n event →
return `{ok: true}`. Client-side they're `fetch()` calls sending `X-CSRF-TOKEN` from the
`<meta name="csrf-token">` tag.

### Member area — `auth` + `CheckEnrollment`
`/dashboard` (the terminal) and `/vault/download/{lessonId}`.

`CheckEnrollment` redirects to `/checkout` if there's no paid enrollment, **or if an
installment balance is overdue** (`access_suspended`) — that's how the installment plan is
enforced.

### Admin — `auth` + `admin`
`/admin` (overview) · `/enrollments` · `/checkpoints` · `/masterclass` · `/leads` ·
`/resources`, plus CSV exports for masterclass and leads.

### API webhooks — `routes/api.php`
`/api/webhooks/paystack` · `/api/webhooks/flutterwave` · `/api/webhooks/vapi`

**Payment webhooks verify server-side before granting anything.** Client-side "payment
succeeded" callbacks are never trusted.

---

## 5. Funnel A — the masterclass

```
/taab  ──register──→  masterclass_registrations + students mirror
                             │
                             └──→ n8n: masterclass_registration (confirmation)

masterclass:remind  (every 15 min; each touch once, stamped)
   ├─ T-24h  → masterclass_reminder       Meet link + WhatsApp group
   ├─ T-2h   → masterclass_starting_soon  final nudge
   └─ end+2h → masterclass_followup       → the Accelerator

registration closed?  → waitlist card → masterclass_waitlist
                                              │
                        masterclass:announce invites them (+ the last 2 sessions'
                        registrants) to register for the next session →
                        n8n: masterclass_reinvite  (stamped in masterclass_invites)
```

`masterclass:announce` is the preferred way to fill a session — it drives waitlisters and
recent past registrants back to `/taab` to register themselves (re-capturing their goal),
rather than silently enrolling them. The older `masterclass:enroll-waitlist` (auto-move
`waitlist` → `registration`) still exists but is deprecated.

Offsets are config, not code: `reminder_lead_hours`, `dayof_lead_hours`,
`followup_after_hours`, and `send_throttle_ms` in `config/taab.php`.

**Window shapes differ, and this matters operationally:**

| Touch | Window | Risk |
|---|---|---|
| Reminder | due at T-24h, open until the session starts (**~23h**) | low |
| Day-of nudge | due at T-2h, closes at start (**2h**) | **high** — easily missed |
| Follow-up | due at end+2h, **never closes** | low, but bounded by the config `date` |

### The readiness scorecard
`/taab/scorecard` is a self-contained client-side quiz: 10 questions across 5 dimensions
(Skills, Time, Setup, Mindset, Market), each option worth 0–10, so the total *is* the
percentage. Tiers: **≥70 ready · 45–69 almost · <45 not yet.**

It's graded as an honest filter, not a funnel — questions test what the Accelerator
*requires*, not what it *teaches*. One rule is encoded specially: if someone can neither get
a real international/USD card for Google Cloud verification nor cover the ~$10/mo × 3 months
paid-hosting fallback, their verdict is **capped at 🟡** regardless of score, because they
cannot stand up their own stack.

Completing the quiz gates the results behind an email capture, which POSTs the score,
tier, per-dimension breakdown **and the rendered verdict + next-steps** to
`/taab/scorecard` → `scorecard_result`. Sending the rendered copy (rather than just the
tier) means the email shows exactly what the page showed, with no duplicate copy in n8n to
drift — and it automatically includes the extra hosting step when the cap fires.

---

## 6. Funnel B — the Accelerator

```
/accelerator ──→ /checkout
                    │  plan choice + REQUIRED no-surprises acknowledgement
                    ▼
          Paystack (NGN) / Flutterwave (USD)
                    │
          webhook, verified server-side
                    ▼
          StudentProvisioner: User + Enrollment + n8n enrollment_finalized
                    ▼
          /dashboard — ship-to-unlock
```

- **Pay in full** → `balance_due = 0`, immediate access.
- **Installment (₦42,000 × 2)** → first payment now, `balance_due` tracked.
  `installments:process` sends the 2nd-payment link on the due date
  (`installment_due_days`) and suspends access `installment_grace_hours` after it lapses.
  Payment happens on a **signed** `/installment/{enrollment}/pay` route.
- The checkout's **no-surprises acknowledgement checkbox is a deliberate trust and
  refund-protection mechanism** — it gates the pay button. Don't remove it.

Registration can be paused at any time from the admin overview
(`Accelerator::setRegistrationOpen(false)`), which shows "Registration is paused" on
checkout — distinct from the sold-out state.

---

## 7. Conventions

**Livewire Volt.** Most interactive pages are single-file Volt components: a `<?php ... ?>`
class block on top, Blade below. Admin screens use
`#[Layout('components.layouts.admin', ['title' => '…'])]` and `abort_unless(auth()->user()?->is_admin, 403)`
in `mount()` — every admin action re-checks, not just the mount.

**Styling.** Tailwind v4 utility classes inline. The TAAB pages use their own layout and an
inline `@push('styles')` block (lime/Syne); Accelerator/admin/links use zinc + cyan +
Space Grotesk. Mobile-first — most traffic is TikTok on mobile.

**n8n integration.** Laravel never sends email or WhatsApp. It POSTs a typed event and
records the outcome. Two rules, both learned the hard way:

1. Stamp **only on a genuine success**, so failures stay unstamped and the next run retries
   them. This makes recovery scale-independent — no manual lists of who missed out.
2. A non-2xx must be **logged and surfaced**, never swallowed. Silent failure is how ~20 of
   30 sends disappeared once, and how a one-underscore typo in an n8n Switch went unnoticed.

**Tests.** Pest, `tests/Pest.php` binds `TestCase` + `RefreshDatabase` to `Feature`. Use
`Http::fake()` and `Carbon::setTestNow()` for time-based flows. Note `Http::fake()` *merges*
stubs — use `Http::fakeSequence()` when consecutive calls need different responses.
The `Undefined method 'get'/'artisan'` IDE warnings in test files are known false positives.
