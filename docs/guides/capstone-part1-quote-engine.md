# PART 1 CAPSTONE
## The Quote Engine
### For a commercial cleaning & facility management company

**Duration:** 7 days
**Stack:** n8n, Gmail, Tally.so, Airtable, Google Sheets, Google Drive, Google Calendar, Telegram, Gemini
**Cost to build:** ₦0
**Workflows:** 3

> **How to use this document.** Read sections 1-4 to understand what you are building and why. Then work through **section 6, the Build Order**, one stage at a time, in the exact order given. Do not skip ahead. Section 7 is the detailed reference you consult while building each stage.

---

## 1. The Business

**SparkleCare Facility Services** - a 20-staff commercial cleaning company in Abuja. They clean offices, schools, banks, hospitals and event halls on monthly contracts, plus one-off deep cleans and post-construction cleaning.

They do not sell products. They sell **cleaners, hours and scope.** Every naira they earn starts as a quote.

---

## 2. The Problem

A facilities manager at a corporate office emails SparkleCare an RFQ - usually a PDF or Word document listing the areas to be cleaned, the floor sizes, the frequency, and extras like window cleaning or fumigation.

What happens next:

1. The email lands while the MD is on a site inspection in Gwarinpa.
2. It sits unopened until evening.
3. She then opens the RFQ, works out how many cleaners the floor area needs, checks her rate card, adds consumables, prices the extras, applies a contract discount, and types the quote by hand.
4. It goes out the next day. Sometimes three days later if a site issue comes up.
5. The facilities manager sent that same RFQ to four cleaning companies. Two replied within hours.

**Cleaning is a commodity service.** Rates across Abuja are broadly similar, so the contract often goes to whoever replied first and looked most organised. SparkleCare loses contracts they were fully capable of winning, purely on response time.

The second loss is quieter: quotes that go out and get no reply. Nobody chases them. A deal that was 90% won is abandoned because there is no system reminding anyone it exists.

**Your capstone removes the arithmetic and the forgetting, and leaves the MD with the only decision that matters: the price.**

---

## 3. Channels - Read This Before You Build

Three separate channels. Mixing them up is the most common mistake on this project.

| Direction | Channel | Why |
|---|---|---|
| Customer → business | **Gmail** (primary), **Tally form** (secondary) | Facility managers send RFQs by email because they need a paper trail. Smaller enquiries come through the "Request a Quote" form on SparkleCare's website. |
| System → MD | **Telegram** | The MD's phone. Her control panel. |
| Business → customer | **Gmail**, replying on the original thread | The customer must receive the quote where they sent the RFQ. |

**The customer never sees Telegram.** Telegram exists so the MD can approve a quote from a site visit with one tap instead of opening a laptop.

*Note: a real share of Nigerian enquiries arrive on WhatsApp. That channel is deliberately out of scope for Part 1 because of the setup overhead. Adding it later is a Part 2 exercise, and nothing else in this system changes when you do.*

---

## 4. How It Runs - The Live Sequence

Before you build anything, understand the whole journey. This is what happens on a real Tuesday, start to finish.

**Minute 0** - A facilities manager at Zenith Properties emails `quotes@sparklecare.ng`. Attached is a 2-page RFQ PDF: 1,800 sqm office across 3 floors, daily cleaning, 12-month contract, plus quarterly window cleaning.

**Minute 0-2 - WF1 runs.**
1. The Gmail trigger fires and picks up the email and its attachment.
2. The attachment is saved to Google Drive.
3. Gemini reads the PDF and returns structured data: floor area, frequency, contract length, list of services requested.
4. The rate card is pulled from Google Sheets.
5. A Code node does the arithmetic: 1,800 sqm ÷ 450 = 4 cleaners, no supervisor needed, consumables on 1,800 sqm, daily multiplier, window cleaning added, 5% discount for a 12-month contract. It produces a monthly total and an annual total.
6. The enquiry and every quote line are saved to Airtable.
7. An AI Agent writes the quote email.
8. The MD's phone buzzes with the full draft and three buttons: **Send**, **Edit**, **Discard**.

**WF1 has now finished executing.** It is gone. Nothing is running or waiting.

**Minute 40** - The MD finishes her site inspection, reads the draft in the car, and taps **Send**.

**Minute 40 - WF2 runs.**
9. The button press starts a brand new workflow execution that knows nothing except one ID.
10. It looks up that ID in Airtable and pulls back the customer, the email thread, and the quote.
11. It checks the quote has not already been sent.
12. Gmail sends the quote as a reply on the original thread.
13. A Calendar reminder is set for 3 days out. Airtable is marked `Sent`. The Telegram buttons are replaced with "✅ Sent to Zenith Properties".

**Day 3 - WF3 runs** on its daily schedule, sees no reply, and sends a short follow-up.

**Day 6 - WF3 runs again,** sends a second follow-up, then tells the MD: *"Zenith Properties hasn't responded to two follow-ups on ₦892,000/month. Worth a call?"*

**The point to notice:** steps 1-8 and steps 9-13 are two completely separate workflow runs, separated by 40 minutes. Nothing carries over between them except an ID stored in Airtable. **That is the single hardest idea in this capstone, and section 6 Stage 10 is where you build it.**

---

## 5. Data Model

### Google Sheets - `Rate_Card` (Tab 1)
The MD maintains this. The only place prices live.

| `service_code` | `service_name` | `unit` | `rate` | `notes` |
|---|---|---|---|---|
| `CLN-STD` | Standard cleaner | per cleaner / month | 145000 | Covers ~450 sqm |
| `CLN-SUP` | Site supervisor | per supervisor / month | 210000 | Required at 5+ cleaners |
| `CONSUM` | Consumables and chemicals | per sqm / month | 180 | |
| `WIN-HIGH` | High-level window cleaning | per visit | 95000 | Quarterly |
| `FUM-GEN` | General fumigation | per sqm | 350 | Quarterly |
| `CARP-SHAM` | Carpet shampooing | per sqm | 900 | On request |
| `DEEP-1X` | One-off deep clean | per sqm | 1200 | |
| `POST-CON` | Post-construction clean | per sqm | 2100 | |

### Google Sheets - `Config` (Tab 2)

| `key` | `value` |
|---|---|
| `sqm_per_cleaner` | 450 |
| `supervisor_threshold` | 5 |
| `freq_daily` | 1.0 |
| `freq_3x_weekly` | 0.65 |
| `freq_weekly` | 0.35 |
| `discount_12mo` | 0.05 |
| `discount_24mo` | 0.10 |

### Airtable Base `QuoteEngine`

**Table: `Enquiries`**

| Field | Type | Notes |
|---|---|---|
| `enquiry_id` | Autonumber | |
| `company_name` | Single line text | |
| `contact_name` | Single line text | |
| `contact_email` | Email | |
| `source` | Single select | `Email`, `Form` |
| `gmail_thread_id` | Single line text | So WF2 replies on the right thread |
| `site_type` | Single select | `Office`, `School`, `Bank`, `Hospital`, `Event Hall`, `Other` |
| `location` | Single line text | |
| `total_sqm` | Number | |
| `frequency` | Single select | `Daily`, `3x Weekly`, `Weekly`, `One-off` |
| `contract_months` | Number | |
| `drive_file_id` | Single line text | The RFQ document |
| `quote_monthly` | Currency | |
| `quote_annual` | Currency | |
| `quote_body` | Long text | |
| `quote_subject` | Single line text | |
| `decision_id` | Single line text | **Links this record to the Telegram buttons** |
| `status` | Single select | `New`, `Awaiting Approval`, `Sent`, `Followed Up`, `Won`, `Lost` |
| `awaiting_edit` | Checkbox | |
| `sent_at` | Date | |
| `follow_up_count` | Number | Default 0 |

**Table: `Quote_Lines`**

| Field | Type | Notes |
|---|---|---|
| `enquiry` | Link → Enquiries | |
| `requested_scope` | Single line text | What the RFQ actually asked for |
| `service_code` | Single line text | From the rate card, or blank |
| `quantity` | Number | Cleaners, sqm, or visits |
| `unit` | Single line text | |
| `rate` | Currency | |
| `line_total` | Formula | `quantity × rate` |
| `status` | Single select | `Priced`, `Needs Manual Pricing` |

Anything the system cannot price becomes a `Needs Manual Pricing` line. **Never guess a rate.** Surface it to the MD instead.

---

## 6. Build Order

Twelve stages. Build them in this order. **Do not start a stage until the previous stage's "Done when" test passes.** Each stage is small enough to finish and verify in one sitting.

---

### Stage 0 - Pre-flight check
You already have every account and credential this project needs. This stage is five minutes of verification so you do not lose an evening to an expired token in the middle of Stage 9.

**Do this** Run a throwaway workflow with a Manual Trigger and confirm each of these still authenticates:

- Telegram - send yourself a message, and have your chat ID to hand
- Gmail - list your last 5 messages
- Google Sheets, Drive and Calendar - read anything
- Airtable - read any base
- Gemini - send any prompt and confirm you are not out of quota

**Done when** all six respond without an auth error. Fix anything that fails now, not later. Google OAuth tokens in particular go stale if you have not used them in a while.

---

### Stage 1 - The rate card
**Do this** Create the Google Sheet with both tabs exactly as in section 5.

**Done when** a two-node workflow (Manual Trigger → Google Sheets read) returns all 8 rate card rows and all 7 config rows in n8n.

---

### Stage 2 - The database
**Do this** Create the Airtable base with both tables and every field listed in section 5. Get the field types right - `decision_id` must be **Single line text**, `total_sqm` must be **Number**.

**Done when** you can create one test record in `Enquiries` from n8n, and see it appear in Airtable.

---

### Stage 3 - The intake form
**Do this** Build the Tally form: company name, contact name, email, site type, location, floor area in sqm, frequency, contract length, and an optional file upload.

**Done when** a Webhook node in n8n receives a real test submission and you can see the file arriving **as a URL, not as binary**. Look at the JSON and confirm this yourself - it matters in Stage 5.

---

### Stage 4 - Gmail intake
**Do this** Create a Gmail label called `RFQ`. Set up a Gmail Trigger node in n8n filtered to that label. Email yourself a test RFQ PDF and apply the label.

**Done when** the trigger fires and you can see the attachment as binary data on the node output, plus the `threadId` in the JSON. **Save that `threadId` field name - WF2 depends on it.**

---

### Stage 5 - WF1: both entries become one shape
This is where most people create problems for themselves later. Get it right now.

**Do this**
1. In one workflow, place both triggers: Gmail Trigger and Webhook.
2. On the Webhook branch only, add an **HTTP Request** node - GET the Tally file URL, **Response Format: File**. This converts the URL into binary.
3. Add a **Code node - Normalise Entry** on each branch that outputs the same shape:
   ```json
   {
     "source": "Email",
     "company_name": "...",
     "contact_name": "...",
     "contact_email": "...",
     "gmail_thread_id": "...",
     "raw_text": "...",
     "has_file": true
   }
   ```
   For the Tally branch, `gmail_thread_id` is an empty string. Fill everything else from the form fields.
4. Merge both branches.
5. **Google Drive** - upload the binary to `/QuoteEngine/RFQs/`. Save the returned file ID.

**Done when** you can trigger the workflow from Gmail *and* from Tally, and both produce identical JSON structure plus a file in Drive. Test both. Do not move on having tested only one.

---

### Stage 6 - WF1: read the document
**Do this** Add the **Gemini Analyze Document** node after the Drive upload. Pass the binary. Prompt it to return **only** this JSON, with no explanation and no markdown:
```json
{
  "site_type": "Office",
  "location": "Central Business District, Abuja",
  "total_sqm": 1800,
  "frequency": "Daily",
  "contract_months": 12,
  "scope_items": ["daily office cleaning", "quarterly window cleaning"],
  "notes": "cleaning must happen before 7am",
  "confidence": 0.9
}
```
Then add a **Code node** that strips markdown code fences before parsing. Gemini adds them roughly half the time. Write it defensively:
```javascript
const clean = raw.replace(/```json/g, '').replace(/```/g, '').trim();
const data = JSON.parse(clean);
```
Then add an **IF** node: is `total_sqm` present and greater than zero?
- **False** → Telegram the MD: *"RFQ from {company} - couldn't read the floor area. Have a look."* with the Drive link. Stop.
- **True** → continue.

**Done when** three different test RFQs (a clean PDF, a scanned photo, and one with no floor area stated) all produce either valid parsed JSON or a clean Telegram alert. No workflow crashes.

---

### Stage 7 - WF1: the pricing engine
**Build and test this Code node on its own before wiring it in.** Use a Manual Trigger and a Set node with fake extraction data so you can iterate fast.

**Do this** Read the rate card and config from Google Sheets, then compute:
```
cleaners      = ceil(total_sqm / sqm_per_cleaner)
supervisors   = cleaners >= supervisor_threshold ? 1 : 0
labour        = (cleaners × CLN-STD rate) + (supervisors × CLN-SUP rate)
consumables   = total_sqm × CONSUM rate
monthly_base  = (labour + consumables) × frequency_multiplier
```
Then loop `scope_items`. Normalise each one and each rate card `service_name` - lowercase, trim, strip punctuation - and match by text containment (does one string contain the other). Matched → priced line. No match → a line with `status: "Needs Manual Pricing"` and rate 0.

Then apply the contract discount and output: `monthly_total`, `annual_total`, `lines[]`, `unpriced_count`.

**Done when** you can hand it these three test cases and get the right answer:

| Input | Expected |
|---|---|
| 1,800 sqm, Daily, 12 months, no extras | 4 cleaners, 0 supervisors, 5% discount applied |
| 2,700 sqm, Daily, 24 months, window cleaning | 6 cleaners, **1 supervisor**, 10% discount, window line priced |
| 900 sqm, Weekly, 6 months, "pressure washing" | 2 cleaners, 0.35 multiplier, no discount, **1 unpriced line** |

Work these out by hand first, then check the node agrees. If it does not, fix the node - not the expected answer.

---

### Stage 8 - WF1: save to Airtable
**Do this** Create the `Enquiries` record, then one `Quote_Lines` record per line, linked to it.

**Done when** a full run produces one enquiry record with the correct totals and the right number of linked quote lines.

---

### Stage 9 - WF1: draft and send to Telegram
**Do this**
1. **AI Agent - Quote Writer.** Feed it the company, contact name, site details, priced lines, unpriced items, monthly and annual totals, and the discount. Return JSON: `{ "subject", "body" }`.

   Prompt rules: write as the MD of a cleaning company, not as an AI. Nigerian business English - polite and direct, no American filler. Show the staffing and scope as a clear breakdown so the customer sees what they are paying for. State the monthly figure and the contract total. If anything could not be priced, say plainly that pricing for those items will follow separately. Close by offering a site visit.

2. **Code node** - generate a `decision_id`. A timestamp plus a random string is fine: `Date.now() + '-' + Math.random().toString(36).slice(2,8)`.
3. **Airtable** - save `decision_id`, `quote_subject`, `quote_body`, `quote_monthly`, `quote_annual`. Set `status` to `Awaiting Approval`.
4. **Telegram** - send the MD the draft, the monthly total, and the unpriced count, with an inline keyboard of three buttons: `✅ Send`, `✏️ Edit`, `❌ Discard`.

   **Put only the `decision_id` in each button's `callback_data`.** Telegram caps `callback_data` at 64 characters. Nothing else fits. Do not try.

**Done when** emailing an RFQ to yourself results in a complete, readable quote arriving on your phone with three working-looking buttons. **WF1 is now finished.**

---

### Stage 10 - WF2: the approval workflow
**A new, separate workflow.** WF1 has already finished executing by the time the MD taps a button. This workflow starts from nothing and must rebuild everything.

**Do this**
1. **Telegram Trigger** - in the node's update settings, enable **Callback Query**. It is off by default and this is the single most common place students get stuck.
2. **Code node** - read `decision_id` and the chosen action from the callback payload.
3. **Airtable Search** - find the enquiry where `decision_id` equals that value. You now have the customer, the thread ID, the quote and the totals back in hand.
4. **IF** - is `status` still `Awaiting Approval`?
   - **False** → edit the Telegram message to *"Already handled"* and stop. **This is what stops a double tap sending two quotes.**
   - **True** → continue.
5. **Switch** on the action:
   - **`Send`** → Gmail reply using `gmail_thread_id` → Google Calendar event 3 days out (*"Follow up: {company} - ₦{monthly}/month"*) → Airtable `status: Sent`, set `sent_at` → edit the Telegram message to *"✅ Sent to {company}"* so the buttons disappear.
   - **`Edit`** → Telegram: *"Send me the corrected quote as your next message."* Tick `awaiting_edit` in Airtable. A second Telegram trigger (on normal text messages) catches her next message, looks up the record with `awaiting_edit` ticked, saves it as `quote_body`, and sends it.
   - **`Discard`** → Airtable `status: Lost`, edit the message to *"❌ Discarded"*.
6. Add a **Telegram "Answer Callback Query"** action so the button stops showing a loading spinner on her phone.

**Done when** all three buttons work, the quote arrives on the **original email thread** (not as a new email), and tapping `✅ Send` twice sends exactly one email.

---

### Stage 11 - WF3: follow-up
**A third separate workflow.**

**Do this**
1. **Schedule Trigger** - daily, 09:00 WAT.
2. **Airtable Search** - `status` is `Sent`, `sent_at` is 3 or more days ago, `follow_up_count` is under 2.
3. **AI Agent** - a two-sentence follow-up referencing the specific site and monthly figure. It must not reuse the wording of the original quote.
4. **Gmail** - reply on the same thread.
5. **Airtable** - increment `follow_up_count`, set `status` to `Followed Up`.
6. **IF** `follow_up_count` is now 2 → **Telegram** the MD: *"{company} hasn't responded to two follow-ups on ₦{monthly}/month. Worth a call?"*

**Done when** you can backdate a test record's `sent_at` by 4 days, run the workflow manually, and watch the follow-up send correctly.

---

### Stage 12 - Harden, then record
**Do this** Work through every item in section 8. Fix what breaks. Then record your Loom and write your README.

**Done when** all six items in section 8 behave correctly and you have not had to explain away any of them.

---

## 7. Workflow Reference

Quick summary of what you built, for your README and your own sanity.

| Workflow | Trigger | Ends by |
|---|---|---|
| **WF1 - RFQ Intake & Quote Draft** | Gmail (label `RFQ`) or Webhook (Tally) | Sending the MD a Telegram draft with three buttons |
| **WF2 - Approve & Send** | Telegram callback query | Emailing the customer and updating Airtable |
| **WF3 - Follow-Up** | Schedule, daily 09:00 | Chasing unanswered quotes, then alerting the MD |

---

## 8. Things That Will Break Your Build

Test all six. Each one has bitten someone before you.

| # | Scenario | What good looks like |
|---|---|---|
| 1 | Gemini wraps its JSON in code fences | Your Code node strips them and parses cleanly |
| 2 | RFQ says *"3 floors, roughly 600sqm each"* | Total area resolves to 1,800 - handle it in the Gemini prompt |
| 3 | The RFQ is a scanned photo of a printed page | Extraction still works, or a clean Telegram alert fires |
| 4 | A Tally enquiry arrives with no file attached | The form fields alone still produce a quote |
| 5 | The MD taps `✅ Send` twice | Exactly one email goes out |
| 6 | The RFQ asks for pressure washing, not on the rate card | Line appears as `Needs Manual Pricing`, quote still sends, MD is told |

---

## 9. When Something Does Not Work

| Symptom | Almost always the cause |
|---|---|
| Telegram buttons do nothing | Callback Query is not enabled on the Telegram Trigger |
| Button works but nothing is found in Airtable | `decision_id` was never saved, or the search field name is wrong |
| The quote arrives as a new email, not a reply | `gmail_thread_id` was not captured in Stage 4 or not passed in Stage 10 |
| The Tally file is empty or unreadable | You skipped the HTTP Request node - Tally sends a URL, not binary |
| `JSON.parse` fails | Code fences from Gemini, or Gemini returned prose instead of JSON. Tighten the prompt. |
| Two quotes sent to one customer | Missing the status check in Stage 10 step 4 |
| Pricing is wrong | Recheck against the three test cases in Stage 7 before touching anything else |

---

## 10. Deliverables

1. **Three workflow JSON exports**, credentials stripped.
2. **A completed `Rate_Card` and `Config` sheet.**
3. **A populated Airtable base** - at least 6 enquiries across the different statuses, from both sources.
4. **A 5-minute Loom.** Send a real RFQ email, approve on your phone, show the quote arriving in the customer's inbox on the original thread. Do not tour the canvas.
5. **A short README** covering your pricing logic and how you handled all six items in section 8.

---

## 11. Grading - 100 points

| Area | Pts | Full marks |
|---|---|---|
| Pricing accuracy | 20 | All three Stage 7 test cases pass. Unpriced items flagged, never guessed. |
| Channel discipline | 10 | Both entry points merge cleanly. The reply lands on the original email thread. Telegram is never used to talk to the customer. |
| Document handling | 15 | Binary fetched and stored correctly from both sources. Extraction survives PDFs and photos. |
| Async context | 20 | WF2 rebuilds full context from `decision_id` alone. Buttons cannot be pressed twice. |
| Quote quality | 15 | The MD would send it without editing it. |
| Error handling | 10 | Unreadable RFQ, missing floor area, failed send - each has a visible path. |
| Craft | 10 | Named nodes, notes on tricky logic, no hardcoded secrets. |

Below 60 is a resubmit, not a fail.

---

## 12. Selling This

The system is not specific to cleaning. Swap the rate card and it fits any service business that quotes from a scope document.

**Who buys it:** cleaning and facility management, security services, fumigation, corporate training, catering and events, haulage, generator servicing, interior fit-out, landscaping.

**The opening question:**

> *"When an RFQ lands in your inbox, how long before the client gets your quote?"*

Every honest answer is a day or more. Then ask how many RFQs they get in a month, and how many they think they lost to someone faster.

**Pricing:**

| Component | Range |
|---|---|
| Setup | ₦250,000 - ₦600,000 |
| Monthly management | ₦50,000 - ₦120,000 |

**The demo that closes.** Ask for a real RFQ they received last month and their rate card. Run it live. The quote they spent an evening on appears on their phone in ninety seconds, waiting for one tap. That is the whole sale - never show them the canvas.

---

## 13. Ground Rules

- Everything runs on free tiers. Needing a credit card means you took a wrong turn - ask in the cohort channel.
- No credentials hardcoded in Code nodes.
- Follow the Build Order. Every stage has a "Done when" test for a reason.
- Test with a real rate card from a real service business. Ask someone you know. Invented data hides every pricing bug you have.

**Pacing:** Day 1 → Stages 0-2. Day 2 → Stages 3-5. Day 3 → Stages 6-7. Day 4 → Stages 8-9. Day 5 → Stage 10. Day 6 → Stage 11 and hardening. Day 7 → Loom and README.

---

*AI Automation Accelerator - AJBuilds AI*
