# n8n — Accelerator enrolment & installment automations

The paid side of the funnel. Separate from
[n8n-masterclass-flow.md](n8n-masterclass-flow.md), which covers the free TAAB funnel on
`N8N_STUDENT_WEBHOOK_URL`.

Four events, across **two** webhooks:

| Webhook (env var) | Events |
|---|---|
| `N8N_ENROLLMENT_WEBHOOK` | `enrollment_finalized` |
| `N8N_INSTALLMENT_WEBHOOK` | `installment_due`, `installment_overdue_suspended`, `installment_completed` |

```
Webhook (POST, =N8N_INSTALLMENT_WEBHOOK)
  └─ Normalize (Code)          derives first_name / amount_display / due_display
       └─ Switch on {{ $json.body.event }}
            ├─ installment_due                → Email  (pay link)
            ├─ installment_overdue_suspended  → Email  (access paused)
            ├─ installment_completed          → Email  (receipt)
            └─ Unmatched                      → Stop and Error
```

An importable build of exactly that lives at
[n8n/accelerator-installments.json](n8n/accelerator-installments.json) — see
[Importing the workflow](#importing-the-workflow).

---

## ⚠️ These route on `event`, NOT `type`

The masterclass events carry a `type` field. **These carry `event`.** A Switch copied
across from the masterclass workflow will match nothing and send nothing, and because
the field is simply absent rather than wrong, nothing in the payload looks broken.

```
{{ $json.body.event }}     ← correct here
{{ $json.body.type }}      ← correct in the masterclass workflow only
```

Exact strings, copy/paste these:

```
enrollment_finalized
installment_due
installment_overdue_suspended
installment_completed
```

They're emitted from `app/Support/StudentProvisioner.php`,
`app/Console/Commands/ProcessInstallments.php`,
`app/Http/Controllers/Api/PaystackWebhookController.php` and
`app/Http/Controllers/Api/FlutterwaveWebhookController.php` — grep there to confirm one.

---

## ⚠️ `N8N_INSTALLMENT_WEBHOOK` is currently misspelled in `.env`

Every installment sender resolves its URL like this:

```php
config('services.n8n.installment_webhook') ?: config('services.n8n.enrollment_webhook')
```

`config/services.php` reads `env('N8N_INSTALLMENT_WEBHOOK')`, but the `.env` sets
**`N8N_INSTALLMENT_WEBHOOK_URL`**. The key therefore resolves to `null` and all three
installment events **fall back onto the enrolment webhook** — which is why
`installment_overdue_suspended` shows up at `/webhook/enrollment_finalized`, and why the
dedicated `/webhook/installment_webhook` endpoint receives nothing.

To split them properly, on the server:

```bash
# .env  — drop the _URL suffix so config/services.php actually reads it
N8N_INSTALLMENT_WEBHOOK=https://ai.deeprmarketing.com/webhook/installment_webhook

php artisan config:cache
php artisan tinker --execute="echo config('services.n8n.installment_webhook');"
```

If that prints an empty line the fallback is still active. **Import and activate the
installment workflow before renaming the var**, or the three events will POST to a path
n8n doesn't serve.

Prefer to keep everything on one webhook? Then delete the unused `.env` line so the next
person doesn't read it as configured, and add the three branches to the enrolment
workflow instead.

---

## ⚠️ Webhook response mode — must be "When Last Node Finishes"

Same non-negotiable as the masterclass flow, for the same reason: with *Respond:
Immediately*, n8n returns `200` before the workflow runs, so Laravel records a send that
never happened.

It matters more here, because these events move money. A `installment_due` that Laravel
believes was delivered is a student who never got their pay link and gets suspended 21
days later without warning.

**Add the fallback branch too.** The shipped workflow sets the Switch's fallback output to
*extra* and wires it into a **Stop and Error** node. That returns a non-2xx, so Laravel
leaves the row unstamped and retries. (Note the masterclass workflow currently uses
`fallbackOutput: "none"`, which silently *drops* an unmatched item and still returns 200 —
worth changing there as well.)

---

## `amount` means three different things

The single biggest trap in this contract. Branch before you format money.

| Event | What `amount` holds |
|---|---|
| `enrollment_finalized` | the price they just paid (`amount`) |
| `installment_due` | the **remaining balance** (`balance_due`) |
| `installment_overdue_suspended` | the **remaining balance** (`balance_due`) |
| `installment_completed` | the **full course total** (`amount_total`) |

`enrollment_finalized` also carries `amount_total` and `balance_due` separately, so an
installment buyer's welcome email can state both what they paid now and what's left.

---

## Payloads

Every field below is referenced in n8n as `{{ $json.body.<field> }}` — the Webhook node
wraps the POSTed JSON under `body`.

### `enrollment_finalized` → `N8N_ENROLLMENT_WEBHOOK`

Fires when a payment is verified server-side, when an admin manually enrols someone, and
when an admin clicks **Re-send welcome**.

| Field | Notes |
|---|---|
| `event` | `enrollment_finalized` |
| `gateway` | `paystack` \| `flutterwave` \| `manual` |
| `full_name`, `email`, `phone` | `phone` is nullable |
| `temp_password` | **nullable** — null when the user account already existed |
| `login_url` | absolute, e.g. `https://ajbuildai.com/login` |
| `amount`, `currency` | what they just paid |
| `plan_type` | `full` \| `installment` |
| `amount_total`, `balance_due` | `balance_due > 0` means a second payment is coming |
| `second_payment_status` | `none` \| `pending` \| `link_sent` \| `paid` |
| `reference` | the enrolment payment reference (`MAN_*` for manual) |
| `paid_at` | ISO 8601 |
| `resent` | **only present on an admin re-send**, always `true` |

Template: [emails/7-accelerator-welcome.html](emails/7-accelerator-welcome.html)

**Branch on `resent`** before counting a sale — otherwise a re-send double-counts. And
handle `temp_password` being null: print the "here's your password" block only when it's
set, or a returning student gets an email with an empty box where their password should be.

### `installment_due` → `N8N_INSTALLMENT_WEBHOOK`

Fired by `installments:process` (3×/day via GitHub Actions) once `installment_due_days`
have elapsed since the cohort start.

| Field | Notes |
|---|---|
| `event` | `installment_due` |
| `full_name`, `email`, `phone` | `phone` is nullable |
| `amount` | **remaining balance**, not the course price |
| `currency` | `NGN` \| `USD` |
| `pay_url` | signed route, **deliberately never expires** |
| `original_reference` | the first payment's reference |
| `plan_type` | always `installment` here |
| `due_at` | ISO 8601 |

Template: [emails/10-installment-due.html](emails/10-installment-due.html)

**No `gateway` key** — this comes from a scheduled command, not a payment gateway. A
Switch or Set node keyed on `gateway` drops it.

### `installment_overdue_suspended` → `N8N_INSTALLMENT_WEBHOOK`

Fired `installment_grace_hours` after a missed due date. **Access is already suspended
when this fires** — `ProcessInstallments` sets `access_suspended = true` and *then* posts.
So present-tense "your access is paused" copy is accurate.

Identical payload to `installment_due`; `due_at` is the date already missed.

Template: [emails/11-installment-overdue.html](emails/11-installment-overdue.html)

Paying via `pay_url` clears the balance and lifts the suspension, which is why the link has
no expiry. Don't add one.

### `installment_completed` → `N8N_INSTALLMENT_WEBHOOK`

Fired by the Paystack/Flutterwave webhook when the second payment clears.

| Field | Notes |
|---|---|
| `event` | `installment_completed` |
| `gateway` | `paystack` \| `flutterwave` |
| `full_name`, `email`, `phone` | |
| `amount` | **the full course total**, not the installment just paid |
| `currency` | |
| `reference` | the **second** payment's reference |
| `original_reference` | the enrolment reference |

Template: [emails/12-installment-completed.html](emails/12-installment-completed.html)

**No `pay_url`, no `due_at`, no `plan_type`.** Copying a CTA over from the due email
produces a dead button.

---

## Importing the workflow

1. n8n → **Workflows → Import from File** →
   [n8n/accelerator-installments.json](n8n/accelerator-installments.json).
2. Open each of the three Email nodes and **re-select the SMTP credential**. The export
   references credential id `7RGhNLHFADXdsIsp` (`AJ BuildAI@hello`); if that id differs on
   the instance, the node shows an empty credential rather than erroring at import.
3. Confirm the Webhook node's path matches the URL in the server `.env`.
4. Confirm **Respond: When Last Node Finishes** survived the import.
5. Activate, then rename the env var (see above) and `php artisan config:cache`.

The three email bodies are embedded in the workflow **and** mirrored in
[emails/](emails/). They're generated from the same source, so they start identical — if
you edit one, edit both, the same rule as everywhere else in this repo.

### No WhatsApp branch, deliberately

The masterclass workflow sends WhatsApp through Twilio using **pre-approved Content
Template SIDs**. No approved templates exist for these three messages, and a SID can't be
invented — an unapproved one fails at send. Email-only is correct until templates are
approved; then copy a `WA - …` node across, swap the ContentSid, and guard for a null
`body.phone` first.

---

## Test it

Fire a real-shaped payload at the webhook without touching a live student:

```bash
curl -X POST https://ai.deeprmarketing.com/webhook/installment_webhook \
  -H 'Content-Type: application/json' \
  -d '{
        "event": "installment_due",
        "full_name": "Test Student",
        "email": "you@example.com",
        "phone": "+2348000000000",
        "amount": 42000,
        "currency": "NGN",
        "pay_url": "https://ajbuildai.com/installment/pay/1?signature=test",
        "original_reference": "TEST_REF",
        "plan_type": "installment",
        "due_at": "2026-10-03T00:00:00+01:00"
      }'
```

A `2xx` means the branch matched *and* the email actually sent. Swap `event` for something
unknown and you should get a non-2xx from the Stop and Error node — if that returns `200`,
the response mode or the fallback wiring is wrong.

From the app side, `installments:process` is idempotent and reports what it did:

```bash
php artisan installments:process
```
