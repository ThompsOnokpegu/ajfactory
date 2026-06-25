# n8n — TAAB Masterclass automations

Everything the app sends for the masterclass funnel goes to the **single student
webhook** (`N8N_STUDENT_WEBHOOK_URL`). Build **one workflow** that receives the
webhook, routes on the `type` field, and sends the right Email + WhatsApp message.

**Laravel guarantees each touch fires once** (the `masterclass:remind` command
stamps `reminder_sent_at` / `dayof_sent_at` / `followup_sent_at`), so n8n does
**not** need any dedup/wait logic — it just sends what it receives.

```
Webhook (POST, =N8N_STUDENT_WEBHOOK_URL)
  └─ Switch  on  {{ $json.type }}
       ├─ masterclass_registration   → Email + WhatsApp   (confirmation, immediate)
       ├─ masterclass_reminder        → Email + WhatsApp   (T-24h: Meet link + group)
       ├─ masterclass_starting_soon   → Email + WhatsApp   (T-2h nudge)
       ├─ masterclass_followup        → Email + WhatsApp   (post-session → Accelerator)
       └─ masterclass_waitlist        → Email + WhatsApp   (registration closed)
```

> The same webhook also receives `taab_lead` (lead-magnet tools) and
> `student_signup`. Add those as extra Switch branches if you want — they're out of
> scope here.

---

## Common payload fields

Every masterclass event carries these (use as n8n expressions, e.g. `{{ $json.email }}`):

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
- `accelerator_url` arrives as a **relative** path (`/accelerator`). Prepend the
  domain in emails: `https://ajbuildai.com/accelerator`.
- Wherever a template shows `{{SESSION}}`, use `{{ $json.session_label }}`.

---

## 1. `masterclass_registration` — confirmation (fires immediately on sign-up)

Extra fields: `background`, `goal`.

**Email**
- **Subject:** `You're in 🎯 TAAB Bootcamp — {{SESSION}}`
- **Body:**
```
Hi {{ $json.first_name }},

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
Hi {{ $json.first_name }} 👋 You're registered for the TAAB Bootcamp — {{SESSION}}, live on Google Meet. We'll send your join link + the attendee group 24h before. See you there! — AJ, Repetigo
```

---

## 2. `masterclass_reminder` — 24h before (the join links)

Extra fields: `meet_url`, `whatsapp_group_url`.

**Email**
- **Subject:** `Tomorrow: your TAAB Bootcamp link inside`
- **Body:**
```
Hi {{ $json.first_name }},

TAAB kicks off {{SESSION}} (WAT) — tomorrow. Save these two links:

▶  Join the session (Google Meet):
   {{ $json.meet_url }}

💬  Join the attendee WhatsApp group (announcements + Q&A):
   {{ $json.whatsapp_group_url }}

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

▶ Join (Google Meet): {{ $json.meet_url }}
💬 Attendee group: {{ $json.whatsapp_group_url }}

Join 5 mins early on a laptop. See you there! — AJ
```

---

## 3. `masterclass_starting_soon` — ~2h before (final nudge)

Extra fields: `meet_url`.

**Email**
- **Subject:** `Starting soon — TAAB Bootcamp`
- **Body:**
```
Hi {{ $json.first_name }},

We go live in a couple of hours ({{SESSION}}, WAT).

▶  Join here: {{ $json.meet_url }}

Grab water, open a laptop, and come ready with your questions. See you shortly.

— AJ
```

**WhatsApp**
```
We're live soon ({{SESSION}}, WAT) ⏰
▶ Join: {{ $json.meet_url }}
See you there! — AJ
```

---

## 4. `masterclass_followup` — after the session (→ Accelerator)

Extra fields: `accelerator_url` (relative — prepend `https://ajbuildai.com`),
`recording_url` (may be empty → make the recording line conditional).

**Email**
- **Subject:** `Your next step after TAAB`
- **Body:**
```
Hi {{ $json.first_name }},

Thanks for spending the day at TAAB — I hope you walked away with real clarity.

{{#if recording_url}}📺  Session recording: {{ $json.recording_url }}{{/if}}

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
Hi {{ $json.name }},

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
Hi {{ $json.name }} 👋 Registration for this TAAB session has closed, but you're on the waitlist — I'll message you when the next date is set. Meanwhile, try the free Readiness Scorecard: https://ajbuildai.com/taab/scorecard — AJ
```

---

## WhatsApp node notes
- Guard the WhatsApp send on `{{ $json.whatsapp }}` being non-empty (an `IF` node)
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
