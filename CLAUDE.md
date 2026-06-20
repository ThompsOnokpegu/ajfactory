# CLAUDE.md — Landing/Offer Page + Checkout Update

> **Purpose of this file:** a task brief for updating the **AI Automation Accelerator** landing/offer page and checkout flow in this LMS. It is self-contained — all required copy, pricing, and rules are below. Once these pages are shipped, trim this file down to just the durable project facts (or delete it).

---

## 0. How to work in this repo (read first)

1. **Explore before editing.** This is a custom **Laravel + Livewire** LMS. Find the existing routes, Livewire components, Blade views, layouts, Tailwind config, and any payment integration already present. **Match the existing conventions** (naming, component structure, CSS approach) — do not introduce a new framework or restructure things.
2. **Don't invent the stack.** If something below assumes a tool you don't use (e.g. a payment provider), adapt to what's already wired up and leave a `// TODO:` note rather than bolting on a new dependency.
3. **Ask/flag, don't guess, on irreversible things** (DB migrations, deleting routes, changing payment keys). Prefer additive changes.
4. **Placeholders:** anywhere you see `{{TODO: ...}}`, leave a clearly-marked placeholder for the owner to fill — do not fabricate testimonials, dates, or links.

---

## 1. Brand & product facts (use consistently)

| Field | Value |
|---|---|
| House brand | **AJBuilds AI** |
| Domain | **ajbuildai.com** |
| Product / course | **AI Automation Accelerator** (this is "Cohort 2", the relaunch) |
| Audience | Nigeria-first; beginners who want to build & sell AI automations (no coding required) |
| Price (full) | **₦79,000** |
| Installment | **₦42,000 × 2** (two payments) |
| Early-bird | **₦69,000** — first 10 seats OR first 72 hours, whichever ends first |
| Cohort length | **6 weeks** |
| Cohort cap | **{{TODO: 20–25}} seats** (drives scarcity) |
| Core promise | Build 9 real AI workflows + an agency stack, and finish — backed by a completion guarantee |

### Visual identity (match the course workbooks/slides)
- **Dark, modern, techy.** Primary background `#0b1118`; surfaces `#121b26`.
- **Teal accent:** `#14b8a6` / `#2dd4bf` (headings highlights, buttons, rules).
- **Amber** `#f59e0b` for "value/cost" callouts only.
- Clean sans-serif (system/Inter/Segoe-style). Generous spacing. Mobile-first (most traffic is from TikTok on mobile).
- Tone: confident, plain-English, no hype, "no surprises." Naira-first.

---

## 2. Scope of this task

Build/update **two pages** (plus one reusable content block):

1. **Landing / Offer page** — the sales page (public).
2. **Checkout page** — plan selection + payment + the required "no-surprises" acknowledgement.
3. **Requirements & Costs block** — a reusable section/partial shown on the landing page AND linked from checkout (copy in §5).

Pricing, cohort size, and dates should come from **config or DB**, not hardcoded in Blade (see §6).

---

## 3. Landing / Offer page — section-by-section spec

Build in this order, top to bottom. Copy is provided; keep it editable (config/DB or a CMS field where practical).

### 3.1 Hero
- **Eyebrow:** `AJBuilds AI · AI Automation Accelerator`
- **Headline:** `Build 9 real AI automations in 6 weeks — and the playbook to charge for them.`
- **Subhead:** `A hands-on, beginner-friendly cohort. Telegram, WhatsApp & Voice AI on your own infrastructure — even if you can't code. Finish, or we coach you 1-on-1 until you do.`
- **Primary CTA:** `Join Cohort 2 — ₦79,000` → checkout.
- **Trust line under CTA:** `{{TODO: cohort start date}} · {{TODO: X of 20}} seats left · Installments available`
- Optional hero visual: a short looping build clip / screenshot of a live workflow.

### 3.2 The problem → the outcome
- Short problem block: "Everyone's talking about AI automation. Most courses leave you with theory, surprise tool bills, and a half-built project you abandon."
- Outcome block: "You'll ship nine working automations, own your stack, and have a tested way to land paying clients."

### 3.3 What you'll build (curriculum at a glance)
Two columns: **Part 1 — Zero-Friction (free tools)** and **Part 2 — Professional Agency (owned stack)**.
- Part 1: Intake Funnel (form→email) · Automated Archivist (files invoices) · Lead Qualifier (smart routing) · FX & Quotation Engine (live pricing) · **a real AI agent on Telegram that captures leads**.
- Part 2: your own self-hosted server (₦0/mo) · RAG knowledge base (no hallucinations) · official WhatsApp bot · AI voice receptionist · the business/pricing playbook.
- Caption: "9 production-grade workflows, built end-to-end."

### 3.4 How it works (why you'll actually finish)
Four cards:
1. **Self-paced video** on this LMS.
2. **Ship-to-unlock** — the next module opens when you submit proof the last build works, so you never silently fall behind.
3. **Weekly live Build & Debug clinics** — get unblocked in real time.
4. **Accountability pods** — 3–4 classmates working alongside you.
- Line: "6 weeks · ~5–8 hours/week."

### 3.5 Proof (social proof)
- Grid for **{{TODO: testimonials}}** (text + photo) and **{{TODO: build/result clips}}**.
- If none yet, render a graceful empty state — do NOT fabricate. Add a `is_published` flag so the owner adds them later.

### 3.6 The offer stack (value)
Render as a stacked list with a clear total. Keep the price anchor.

| What you get |
|---|
| 10 modules · 9 production workflows (full curriculum) |
| Ship-to-unlock structure + weekly live Build & Debug clinics |
| Accountability pod (3–4 peers) |
| Done-for-you friction kit: one-command self-host script, Money & Tools Map, sandbox/credit budgets, Meta verification help |
| The Agency Toolkit: intake form, outreach script, onboarding roadmap, pricing playbook |
| Lifetime LMS access + all future updates & session recordings |
| Alumni community + Demo Day |
| **Completion guarantee** (see below) |

- Anchor line: "Agencies charge ₦300k–1M to build *one* of these. You'll learn to build all nine — and sell them — for ₦79,000." Keep as editable copy.

### 3.7 Pricing & payment options
Three options (cards). The early-bird is conditional (see §6 logic):
- **Pay in full — ₦79,000** (or **₦69,000 early-bird** while active).
- **2 payments — ₦42,000 × 2.**
- Each card CTA → checkout with the plan preselected.
- Microcopy: "Secure checkout · {{TODO: Paystack/Flutterwave}} · Card or bank transfer."

### 3.8 Completion guarantee (risk reversal)
- Heading: **"Finish, or we finish with you."**
- Body: "Do the work, attend the clinics, and if your stack still isn't live by the end of the cohort, you get free 1-on-1 sessions until it works — at no extra cost."

### 3.9 Requirements & Costs (no surprises)
- Embed the reusable block from §5 (or a clear summary + a "Read full requirements & costs" link/modal).
- This MUST be visible on the landing page, not hidden — it's a core trust promise.

### 3.10 FAQ
Use the Q&A in §7.

### 3.11 Final CTA + scarcity
- Repeat primary CTA, seats-left counter, cohort start date, and the guarantee line.
- Footer: `AJBuilds AI · ajbuildai.com` + links (privacy, terms, contact).

---

## 4. Checkout page — spec

### 4.1 Plan selection
- Radio/cards for: **Pay in full (₦79,000 / ₦69,000 early-bird)** and **2 payments (₦42,000 × 2)**.
- Preselect the plan passed from the landing CTA.
- Show a live **order summary** (plan, amount due today, total).

### 4.2 Buyer details
- Name, email, phone (Nigerian phone format friendly). Reuse existing user/auth flow if checkout requires an account.

### 4.3 REQUIRED — "No-surprises" acknowledgement (do not skip)
- A **required checkbox**, unchecked by default, that gates the pay button:
  `☐ I've read the Requirements & Costs and understand that beyond tuition I'll need ~one cheap domain (₦8–15k) and ~$5–10 of optional voice credits, plus a card that works for international/USD payments.`
- The checkbox label links to the full Requirements & Costs block (§5) in a modal or page.
- **The pay button stays disabled until this is checked.** This is a deliberate trust + refund-protection mechanism — keep it.

### 4.4 Payment integration
- Use the **payment provider already integrated** in this repo. If none, default to **Paystack** (Nigeria-standard; supports cards + bank transfer), and structure the code so **Flutterwave** could swap in.
- **Pay in full:** single charge (₦79,000, or ₦69,000 if early-bird active at purchase time).
- **Installment (₦42,000 × 2):** charge the first ₦42,000 now; record the second as **due** and schedule/track it (Paystack subscription/plan, a scheduled charge, or a manual reminder + payment link — choose what fits the existing setup; if unsure, implement first payment + a clearly-tracked pending balance and leave a `// TODO:` for automated 2nd charge).
- On success: enrol the user in the cohort, grant LMS access (or queue access for the cohort start date), send a confirmation email, and (if installment) record the outstanding balance.
- Handle failure/cancel gracefully; never grant access without a confirmed payment (verify server-side via webhook/verify call — do not trust client-side success alone).

### 4.5 Trust elements on checkout
- Show the **completion guarantee** line and a "secure payment" indicator.
- Show seats-left if you implement the cap (§6).

---

## 5. Requirements & Costs — full copy (reusable block)

> Render this as a partial/component used on the landing page and linked from the checkout checkbox. Keep wording — it's the "no-surprises" promise the offer is built on.

**Before you enroll — Requirements & Costs**

Beyond **₦79,000 tuition**, the tools are almost entirely free. Here is every cost you may incur — there are no others:

| Tool | For | Cost |
|---|---|---|
| n8n cloud trial | first builds (14-day free trial, no card) | ₦0 |
| Tally | the lead form (Module 1) | ₦0 free plan |
| Google (Gmail/Sheets/Drive/Docs) | throughout | ₦0 |
| Telegram + a free bot | first automation + AI agent | ₦0 |
| Loom / any screen recorder | weekly proof clips | ₦0 |
| LLM API key (Gemini free path) | AI agent brain | ₦0 |
| **Domain name** (Namecheap) | your self-hosted server | **~₦8,000–15,000 / year** |
| Google Cloud Always-Free server | hosting | ₦0 / month |
| Pinecone | AI knowledge base | ₦0 free tier |
| Meta WhatsApp Cloud API | official WhatsApp bot *(optional)* | ₦0 service tier |
| **Vapi voice credits** | AI phone receptionist *(optional)* | **~$5–10 one time** |

**Bottom line:** beyond tuition, plan for about **one cheap domain (~₦8–15k) + ~$5–10 of voice credits.** No servers to rent, no monthly bills, no surprise charges.

**You'll need:** a computer + stable internet; a smartphone; a Google account; **a card that works for international/USD payments** (or a virtual USD card) for free Google Cloud verification and the optional voice credits — *if you can't get one, we have a shared option so it never blocks you*; and ~5–8 hours/week for 6 weeks.

**You do NOT need:** coding experience; a registered business to start or finish (only the *optional* WhatsApp module needs one — without it you complete via the Telegram path); expensive software; prior AI knowledge.

**Why the n8n trial won't trap you:** it's 14 days, but we move you onto your own free self-hosted server early — before it expires.

---

## 6. Pricing & cohort config (don't hardcode)

Create a config (e.g. `config/accelerator.php`) or a DB-backed `cohorts` record exposing:

```php
'price_full'        => 79000,   // NGN
'price_earlybird'   => 69000,   // NGN
'installment_each'  => 42000,   // NGN x2
'installment_count' => 2,
'currency'          => 'NGN',
'cohort_cap'        => 20,       // {{TODO: confirm 20–25}}
'seats_sold'        => 0,        // derive from enrolments
'earlybird_seats'   => 10,
'earlybird_ends_at' => null,     // {{TODO: datetime, 72h window}}
'cohort_starts_at'  => null,     // {{TODO}}
'cart_closes_at'    => null,     // {{TODO}}
'payment_provider'  => 'paystack', // or 'flutterwave'
```

- **Early-bird active** when `seats_sold < earlybird_seats` AND `now < earlybird_ends_at`.
- **Seats-left** = `cohort_cap - seats_sold`; when 0, disable checkout and show a waitlist CTA.
- All amounts in **kobo** when calling Paystack (×100).

---

## 7. FAQ copy

- **Do I need to know how to code?** No. It's drag-and-drop automation. If you can follow step-by-step instructions, you can do this.
- **What will it cost beyond the ₦79,000?** About one cheap domain (~₦8–15k/yr) and ~$5–10 of optional voice credits. Everything else runs on free tiers.
- **Do I need a registered business (CAC)?** No — not to start or to finish. It's only needed for the optional WhatsApp module; without it you complete via the Telegram path.
- **What if I fall behind?** Ship-to-unlock keeps you on track, there's a catch-up buffer week, weekly live clinics, and the completion guarantee.
- **Do I need an international card?** Only for free Google Cloud verification and the optional voice credits — a virtual USD card works, or use our shared option.
- **How much time per week?** About 5–8 hours, over 6 weeks.
- **Is it live or recorded?** Both — self-paced videos plus weekly live Build & Debug clinics and an accountability pod.
- **Can I pay in installments?** Yes — ₦42,000 × 2.
- **What's the guarantee?** Do the work and if your stack still isn't live by the end, we coach you 1-on-1 until it is.

---

## 8. Definition of done (acceptance criteria)

- [ ] Landing/offer page renders all sections in §3, mobile-first, on-brand (dark + teal), copy editable via config/DB where noted.
- [ ] Pricing, seats-left, early-bird, and cohort dates are driven by config/DB (§6), not hardcoded.
- [ ] Three pricing options present; CTAs route to checkout with the plan preselected.
- [ ] Requirements & Costs block (§5) visible on the landing page and linked from checkout.
- [ ] Checkout supports pay-in-full, early-bird (conditional), and installment.
- [ ] **Required "no-surprises" acknowledgement checkbox gates the pay button** (disabled until checked).
- [ ] Payment verified **server-side** (webhook/verify) before granting access; failures handled gracefully.
- [ ] Installment records the outstanding ₦42,000 balance and tracks/schedules the 2nd payment (or a clear TODO if automation isn't wired).
- [ ] Successful purchase enrols the user + grants/queues LMS access + sends confirmation.
- [ ] Seats-sold-out state disables checkout and shows a waitlist CTA.
- [ ] No fabricated testimonials, dates, links, or keys — all marked `{{TODO}}`.
- [ ] Follows existing repo conventions; no new framework introduced.

---

## 9. Out of scope / do not touch
- The course content modules themselves (separate workstream).
- Existing auth, student dashboard, or video delivery — unless required to grant access on purchase (additive only).
- Do not change live payment keys or run migrations without confirming with the owner.

---

## 10. Placeholders the owner must supply
- Cohort start date, cart-close date, early-bird 72h window.
- Final seat cap (20–25).
- Real testimonials + build/result clips.
- Payment provider keys (Paystack/Flutterwave), confirmation email content, booking/contact links.
