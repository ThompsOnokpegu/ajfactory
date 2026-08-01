# n8n — TAAB Masterclass automations

Everything the app sends for the masterclass funnel goes to the **single student
webhook** (`N8N_STUDENT_WEBHOOK_URL`). Build **one workflow** that receives the
webhook, routes on the `type` field, and sends the right Email + WhatsApp message.

**Laravel guarantees each touch fires once** (the `masterclass:remind` command
stamps `reminder_sent_at` / `dayof_sent_at` / `followup_sent_at`), so n8n does
**not** need any dedup/wait logic — it just sends what it receives.

```
Webhook (POST, =N8N_STUDENT_WEBHOOK_URL)
  └─ Switch  on  {{ $json.body.type }}
       ├─ masterclass_registration   → Email + WhatsApp   (confirmation, immediate)
       ├─ masterclass_reminder        → Email + WhatsApp   (T-24h: Meet link + group)
       ├─ masterclass_starting_soon   → Email + WhatsApp   (T-2h nudge)
       ├─ masterclass_followup        → Email + WhatsApp   (post-session → Accelerator)
       ├─ masterclass_waitlist        → Email + WhatsApp   (registration closed)
       └─ masterclass_reinvite        → Email + WhatsApp   ("registration open" → register)
```

> The same webhook also receives `taab_lead` (lead-magnet tools) and
> `student_signup`. Add those as extra Switch branches if you want — they're out of
> scope here.

---

## ⚠️ Exact event names — copy/paste these

A Switch branch that doesn't match **any** `type` fails the whole item. Every
registrant fails identically, so it looks like an outage rather than a typo. This
has bitten us once already: the follow-up branch was checking
`masterclass_follow_up` (extra underscore) instead of `masterclass_followup`, and
all 53 post-session emails failed with HTTP 500.

Copy these verbatim — **note `followup` and `signup` have no underscore, while
`starting_soon` does**:

```
masterclass_registration
masterclass_reminder
masterclass_starting_soon
masterclass_live
masterclass_followup
masterclass_waitlist
masterclass_reinvite
scorecard_result
taab_lead
student_signup
```

These strings are the contract between the app and n8n. They're emitted from
`app/Console/Commands/ProcessMasterclass.php`,
`app/Console/Commands/AnnounceMasterclass.php`,
`app/Http/Controllers/MasterclassController.php`, and
`app/Http/Controllers/ScorecardController.php` — grep there if you ever need to
confirm one.

**Add a fallback branch** on the Switch that logs anything unmatched. Then a
future rename surfaces as one obviously-wrong item instead of a silent mass
failure. Note that with the webhook set to **"Respond: When Last Node Finishes"**
(required — see below), an unmatched type returns a non-2xx, so Laravel won't
stamp it as sent and will retry on the next run.

---

## ⚠️ Webhook response mode — must be "When Last Node Finishes"

The Webhook node **must** be set to *Respond: When Last Node Finishes*, not
*Immediately*. With *Immediately*, n8n returns `200` before the workflow runs, so
Laravel stamps the touch as sent even when n8n later drops or errors the
execution — producing rows marked "reminded" with no email behind them (this cost
us ~20 of 30 sends one edition, and hid the `follow_up` typo above entirely).

Respond-when-done makes the `2xx` truthful: Laravel blocks until n8n has actually
finished, real failures return non-2xx, nothing gets stamped, and the next run
retries them automatically.

---

## Common payload fields

Every masterclass event carries these (use as n8n expressions, e.g. `{{ $json.body.email }}`):

| Field | Example | Notes |
|---|---|---|
| `type` | `masterclass_reminder` | the Switch key |
| `first_name` | `Adebayo` | not present on `masterclass_waitlist` |
| `last_name` | `Okafor` | not present on `masterclass_waitlist` |
| `name` | `Adebayo Okafor` | present on all |
| `email` | `adebayo@example.com` | lower-cased |
| `whatsapp` | `+2348000000000` | may be empty → guard the WhatsApp node |
| `session_date` | `2026-06-27` | the session this person is tied to |
| `session_label` | `Saturday 27 June · 9:00 AM – 11:00 AM WAT` | ready-to-print; templates use it as `{{SESSION}}` |
| `starts_at` | `2026-06-27T09:00:00+01:00` | ISO 8601, Africa/Lagos (use if you need the raw datetime) |
| `timestamp` | ISO 8601 | when the event fired |

**Per-event extras** are listed with each template below. `session_label` is present
on every event except `masterclass_waitlist` (which isn't tied to a session).

> **Ready-made HTML emails** (branded, email-client-safe, merge fields baked in) live
> in [`docs/emails/`](emails/) — one file per event. Open them in a browser to preview,
> then paste into the n8n Send-Email node's HTML field. The plain-text bodies below are
> the fallback / WhatsApp copy.

**Gotchas**
- The Webhook node wraps the POSTed JSON under `body`, so every field is referenced as
  `{{ $json.body.<field> }}` (as in the templates below). `recording_block` is the one
  exception — it's a value you build in a Set node, so it stays `{{ $json.recording_block }}`.
- `accelerator_url` arrives as a **relative** path (`/accelerator`). Prepend the
  domain in emails: `https://ajbuildai.com/accelerator`.
- Wherever a template shows `{{SESSION}}`, use `{{ $json.body.session_label }}`.

---

## 1. `masterclass_registration` — confirmation (fires immediately on sign-up)

Extra fields: `background`, `goal`.

**Email**
- **Subject:** `You're in 🎯 TAAB Bootcamp — {{SESSION}}`
- **Body:**
```
Hi {{ $json.body.first_name }},

You're registered for The AI Automation Bootcamp (TAAB).

🗓  {{SESSION}} (WAT)
📍  Live on Google Meet

Here's what happens next:
• 24 hours before, we'll email you the Google Meet link + the link to join the
  attendee WhatsApp group.
• Come with questions — Session 5 is an open floor and attendees drive it.

It's a focused 2-hour session to get real clarity before you commit to anything. See you there.

— AJ Thompson
Repetigo · TAAB
```

**WhatsApp**
```
Hi {{ $json.body.first_name }} 👋 You're registered for the TAAB Bootcamp — {{SESSION}}, live on Google Meet. We'll send your join link + the attendee group 24h before. See you there! — AJ, Repetigo
```

---

## 2. `masterclass_reminder` — 24h before (the join links)

Extra fields: `meet_url`, `whatsapp_group_url`.

**Email**
- **Subject:** `Tomorrow: your TAAB Bootcamp link inside`
- **Body:**
```
Hi {{ $json.body.first_name }},

TAAB kicks off {{SESSION}} (WAT) — tomorrow. Save these two links:

▶  Join the session (Google Meet):
   {{ $json.body.meet_url }}

💬  Join the attendee WhatsApp group (announcements + Q&A):
   {{ $json.body.whatsapp_group_url }}

Tips before we start:
• Join 5 minutes early on a laptop if you can — you'll be following along live.
• Have a notebook (or a fresh Google Doc) open.
• Block the full 2 hours — the 5 sessions build on each other.

See you tomorrow.

— AJ
```

**WhatsApp**
```
Reminder: TAAB Bootcamp is {{SESSION}} (WAT) 🚀

▶ Join (Google Meet): {{ $json.body.meet_url }}
💬 Attendee group: {{ $json.body.whatsapp_group_url }}

Join 5 mins early on a laptop. See you there! — AJ
```

---

## 3. `masterclass_starting_soon` — ~2h before (final nudge)

Extra fields: `meet_url`.

**Email**
- **Subject:** `Starting soon — TAAB Bootcamp`
- **Body:**
```
Hi {{ $json.body.first_name }},

We go live in a couple of hours ({{SESSION}}, WAT).

▶  Join here: {{ $json.body.meet_url }}

Grab water, open a laptop, and come ready with your questions. See you shortly.

— AJ
```

**WhatsApp**
```
We're live soon ({{SESSION}}, WAT) ⏰
▶ Join: {{ $json.body.meet_url }}
See you there! — AJ
```

---

## 3b. `masterclass_live` — "we're live now" (fired manually by `masterclass:go-live`)

Extra fields: `meet_url`. Sent by hand the moment the session goes live (the
scheduled cron can't hit a tight window), stamped via `live_sent_at`. The branch
does **email + WhatsApp**: the Switch's `Live` output fans out to both a
3-account round-robin email path (Pick Sender → Rotate SMTP → hello@/aj@/taab@,
same as re-invite) and a Twilio WhatsApp node.

**Email** — branded HTML in [`emails/9-live.html`](emails/9-live.html), Subject:
`🔴 We're live — join TAAB now` (CTA button → `{{ $json.body.meet_url }}`).

**WhatsApp** — Twilio approved template (its ContentSid is set on the `WA - Live`
node in the workflow). ContentVariables mirror `starting_soon`:
`{{1}}` = first name, `{{2}}` = the Meet **code** (`meet_url` split on `/`, last
segment). ⚠️ If your approved template expects the **full** Meet URL in `{{2}}`,
drop the `.split('/').pop()` from the node's ContentVariables.

---

## 4. `masterclass_followup` — after the session (→ Accelerator)

Extra fields: `accelerator_url` (relative — prepend `https://ajbuildai.com`),
`recording_url` (may be empty → make the recording line conditional).

**Email**
- **Subject:** `Your next step after TAAB`
- **Body:**
```
Hi {{ $json.body.first_name }},

Thanks for joining TAAB — I hope you walked away with real clarity.

{{#if recording_url}}📺  Session recording: {{ $json.body.recording_url }}{{/if}}

If the bootcamp showed you that AI automation IS for you, the next step is the
AI Automation Accelerator — the 6-week cohort where you build 9 real automations
end to end (and the playbook to charge for them), backed by a completion guarantee.

Everything we previewed, built with you, step by step:
👉  https://ajbuildai.com/accelerator

Seats are capped each cohort and fill from this list first — if you're in, grab
your seat before it's full.

Questions? Just reply to this email.

— AJ Thompson
Repetigo · AJBuilds AI
```
> n8n has no `{{#if}}` — implement the recording line with an IF node or a Set
> node that builds the line only when `recording_url` is non-empty.

**WhatsApp**
```
Thanks for joining TAAB! 🙌 If you're ready to actually build, the AI Automation Accelerator is the next step — 9 real automations in 6 weeks: https://ajbuildai.com/accelerator . Seats fill from this list first. Reply with any questions. — AJ
```

---

## 5. `masterclass_waitlist` — registration closed (next-session interest)

Only `name`, `email`, `whatsapp`, `type`, `timestamp` (no `first_name`/session).

**Email**
- **Subject:** `You're on the list for the next TAAB`
- **Body:**
```
Hi {{ $json.body.name }},

Registration for the current TAAB session has closed — but you're on the list,
and I'll email you the moment the next date is set.

While you wait, the three free tools we use in the bootcamp are already
yours to use:
• Readiness Scorecard — https://ajbuildai.com/taab/scorecard
• ROI Calculator     — https://ajbuildai.com/taab/roi-calculator
• Tool Stack Guide   — https://ajbuildai.com/taab/tool-stack

Talk soon.

— AJ · Repetigo
```

**WhatsApp**
```
Hi {{ $json.body.name }} 👋 Registration for this TAAB session has closed, but you're on the waitlist — I'll message you when the next date is set. Meanwhile, try the free Readiness Scorecard: https://ajbuildai.com/taab/scorecard — AJ
```

---

## 6. `masterclass_reinvite` — registration is open (→ register for this session)

Fired by `masterclass:announce` to nudge two audiences to **register** for the
current session: waitlisters who never registered, and registrants from the last
couple of sessions (most only ever attend once). It deliberately drives them to
the registration form — not a silent auto-enrol — so we re-capture their goal and
a fresh intent signal.

Branded HTML: [`emails/8-reinvite.html`](emails/8-reinvite.html). The importable branch
(built into `n8n/aj-buildai-waitlist-masterclass.json`) round-robins across **three Hostinger
senders** — `hello@`, `aj@`, `taab@` — because each mailbox is capped at **100 emails/day**.
A Code node (`Pick Sender`) sets `smtpIndex = n % 3` and a Switch (`Rotate SMTP`) routes to
one of three email nodes. **Before use:** create SMTP credentials for `aj@` and `taab@` in
n8n and select them on their email nodes (`hello@` is already wired). Daily volume is capped
app-side with `masterclass:announce --limit` — see the operations runbook.

Extra fields: `register_url` (the `/taab` hub with a per-invite token —
`/taab?i=<token>` — **relative**, prepend `https://ajbuildai.com`; the token makes
the form pre-fill the lead's known name/email/WhatsApp/background, so **link to
`register_url` verbatim, don't hardcode a bare `/taab`**), `audience`
(`waitlist` | `past_registrant`, for optional copy tweaks / segmentation).
`name` + `first_name` are always present.

**Email**
- **Subject:** `New TAAB date: {{SESSION}} — save your spot`
- **Body:**
```
Hi {{ $json.body.first_name }},

The next The AI Automation Bootcamp (TAAB) is set:

🗓  {{SESSION}} (WAT)
📍  Live on Google Meet

It's a focused, free 2-hour session to get real clarity on AI automation before
you commit to anything. Seats on the call are limited, so register to lock yours:

👉  https://ajbuildai.com{{ $json.body.register_url }}

Registering also lets us tailor the session to what you're trying to achieve —
you'll tell us your main goal on the form.

See you there.

— AJ Thompson
Repetigo · TAAB
```

**WhatsApp**
```
Hi {{ $json.body.first_name }} 👋 New TAAB Bootcamp date: {{SESSION}} (WAT), live on Google Meet. It's a free 2-hour clarity session — register to save your spot (and tell us your goal): https://ajbuildai.com{{ $json.body.register_url }} — AJ
```

> **Use `register_url` verbatim** (prefixed with the domain) — it carries the
> `?i=<token>` that pre-fills the form. A bare `https://ajbuildai.com/taab` still
> works but throws away the pre-fill. `register_url` already starts with `/taab`.
>
> Idempotency is handled app-side: Laravel stamps the `masterclass_invites` ledger
> so no one is invited to the same session twice. n8n just sends what it receives.

---

## WhatsApp node notes
- Guard the WhatsApp send on `{{ $json.body.whatsapp }}` being non-empty (an `IF` node)
  — some registrants leave it blank.
- The send node depends on your provider (Meta WhatsApp Cloud API, 360dialog,
  Whapi, etc.). Templates above are plain text; if you're on Meta Cloud API for the
  reminders, those must be **pre-approved template messages** (session window rules),
  whereas the WhatsApp **group link** in email sidesteps that entirely.

## Test it
Fire a sample event at the webhook from a terminal (replace the URL):
```bash
curl -X POST "$N8N_STUDENT_WEBHOOK_URL" -H "Content-Type: application/json" -d '{
  "type":"masterclass_reminder","first_name":"Test","name":"Test User",
  "email":"you@example.com","whatsapp":"",
  "session_date":"2026-06-27","starts_at":"2026-06-27T09:00:00+01:00",
  "meet_url":"https://meet.google.com/abc-defg-hij",
  "whatsapp_group_url":"https://chat.whatsapp.com/XXXX"
}'
```
On the app side, `php artisan masterclass:remind` fires the real events when due
(hourly via the scheduler).
