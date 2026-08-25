# Cohort 03 - 30 seats in 20 days

Target: **30 paid seats** by Mon 14 Sep. Today is Tue 25 Aug.

---

## TODAY (Tue 25 Aug)

- [x] ~~**Fix the bug.**~~ Done. `masterclass:announce` treated every abandoned cart as a buyer and had never invited one. Now excludes `status='pending'` only.
- [x] ~~**Raise the cap.**~~ Done, 25 → 30, before anything sent.
- [x] ~~**Add testimonials.**~~ Done. 3 real Cohort 2 quotes live, and the Proof section on `/accelerator` un-commented (it was hidden - config alone showed nothing).
- [ ] **Deploy all three** + `php artisan config:cache` on the server. Config is cached in production - without this the site still says 25 seats and shows no proof.
- [ ] `php artisan masterclass:announce --dry-run` then `--limit=270`
- [ ] Export S1 (below). DM all of them on WhatsApp today.

---

## The two deadlines

| Date | Event |
|---|---|
| Fri 28 Aug | TAAB registration closes |
| Sat 29 Aug 9am | Masterclass runs |
| **Mon 31 Aug** | **Early-bird ends** |
| Sat 12 Sep | Cohort starts |
| **Mon 14 Sep** | **Cart closes** |

## The price ladder (already built, just use it)

|  | Full | Installments |
|---|---|---|
| Now + TAAB59 | **₦59,000** | ₦37,000 x 2 |
| From 1 Sep + TAAB59 | ₦69,000 | ₦37,000 x 2 |

**The line:** "TAAB59 gets you in at ₦59,000 until Monday 31 August. Same code, ₦69,000 from Tuesday."

⚠️ Installments do **not** rise on 31 Aug. Don't say they do.

---

## Segments

Run each, export CSV, work top-down. All queries suppress `status='paid'` only.

| # | Who | Size | Rate | Seats | Channel |
|---|---|---|---|---|---|
| **S1** | Abandoned checkout | ? | 15-25% | 4-8 | WhatsApp, by hand |
| **S2** | TAAB attendees, never bought | ? | 5-10% | 4-7 | Email + DM |
| **S3** | Registered, no-showed | ? | 1-3% | - | → push to 29 Aug session |
| **S4** | Accelerator waitlist | ? | 5-10% | 3-6 | Email |
| **S5** | Scorecard 🟢/🟡 (overlay) | ? | 5-8% | 2-4 | Email |
| **S6** | Cold tool downloads | ? | 1-2% | 1-2 | Email, 2 touches max |
| | 29 Aug masterclass | | 8-15% | 4-8 | Follow-up |
| | **Total** | | | **19-37** | |

**30 sits in the top third of that range.** It happens if S1 and S2 are worked by hand on WhatsApp and the 29 Aug session fills. It does not happen from email alone - a pure email campaign lands nearer 19. That is the whole difference between the two numbers.

**Skip:** the `leads` table (agency funnel, wrong product) and `source='clients'`.

<details>
<summary><b>Queries</b></summary>

```sql
-- Sizing (run first, fill in the table above)
SELECT source, COUNT(*) FROM students GROUP BY source ORDER BY 2 DESC;
SELECT scorecard_tier, COUNT(*) FROM students GROUP BY scorecard_tier;
SELECT status, COUNT(*) FROM enrollments GROUP BY status;
SELECT COUNT(*) FROM enrollments WHERE status='paid' AND cohort=3;   -- where you are

-- S1 abandoned checkout (latest attempt per person)
SELECT e.full_name, e.email, e.whatsapp, e.plan_type, e.coupon_code, e.created_at
FROM enrollments e
JOIN (SELECT email, MAX(id) id FROM enrollments WHERE status='pending' GROUP BY email) l
  ON l.id = e.id
WHERE e.email NOT IN (SELECT email FROM enrollments WHERE status='paid');

-- S2 attendees / S3 no-shows (flip the attended test)
SELECT first_name, email, whatsapp, goal, session_date
FROM masterclass_registrations
WHERE attended = 1
  AND email NOT IN (SELECT email FROM enrollments WHERE status='paid');

-- S4 waitlist
SELECT name, email, whatsapp, created_at FROM students
WHERE source='accelerator_waitlist'
  AND email NOT IN (SELECT email FROM enrollments WHERE status='paid');

-- S5 scorecard overlay (cuts across S1-S4 - use to prioritise)
SELECT name, email, whatsapp, source, scorecard_tier, scorecard_score FROM students
WHERE scorecard_tier IN ('ready','almost')
  AND email NOT IN (SELECT email FROM enrollments WHERE status='paid')
ORDER BY FIELD(scorecard_tier,'ready','almost'), scorecard_score DESC;

-- S6 cold tail
SELECT name, email, whatsapp, source FROM students
WHERE source IN ('scorecard','roi','tool-stack') AND scorecard_tier IS NULL
  AND email NOT IN (SELECT email FROM enrollments WHERE status='paid');
```
</details>

**S1 tip:** read `plan_type`. `installment` = price objection (TAAB59 → ₦37,000). `full` = usually a failed card. Ask which.
**S2 tip:** `goal` is their own words. Open with it.

---

## Calendar

Send 7-9pm WAT. One touch per day, max.

### Week 1 - fill the room, close on price

| Day | Do |
|---|---|
| **Tue 25** | Today's list above |
| **Wed 26** | `masterclass:announce --limit=270` again · S4 waitlist email |
| **Thu 27** | S2 attendee email (open with their `goal`) |
| **Fri 28** | Last call to register for tomorrow |
| **Sat 29** | `masterclass:go-live` 9am. Follow-up auto-fires ~1pm |
| **Sun 30** | **Personal WhatsApp to every attendee who hasn't bought.** Highest-intent 24h of the campaign |
| **Mon 31** | Early-bird close. Email + WhatsApp, morning and evening, all segments. Only loud day |

### Week 2 - sell the thing, not a deadline

No real deadline exists 1-13 Sep. Don't invent one.

| Day | Email |
|---|---|
| **Tue 1** | "Early-bird closed. TAAB59 still takes ₦10,000 off until the 14th" |
| **Thu 3** | The 9 workflows, named |
| **Sat 5** | Requirements + real tool costs (`requirements-costs.blade.php`) |
| **Mon 8** | Completion guarantee - checkpoints + 4 of 6 lives |
| **Wed 10** | Objections, built from the `before` field in Admin → Reviews |

### Week 3 - close

| Day | Do |
|---|---|
| **Thu 11** | "Starts Saturday" + seats left |
| **Sat 12** | Cohort starts. Doors open 2 more days by design - say so |
| **Sun 13** | "One day left, you've missed nothing" - self-paced |
| **Mon 14** | Cart close. 3 sends: morning, 3pm, 9pm. Then stop |
| **Tue 15** | Everyone else → Cohort 4 waitlist |

---

## Copy

Hyphens only. Never fabricate seats - read `Accelerator::seatsLeft()`. Every message ends in a one-word reply.

**S1 · abandoned, installment (WhatsApp, by hand)**
> Hey {{name}}, AJ here. Not automated.
>
> You got to the payment page and stopped. Which was it:
>
> 1. ₦42,000 in one go was too much this month
> 2. The card didn't go through
>
> If 1: TAAB59 brings the first payment to ₦37,000, second isn't due until 3 weeks after we start.
> If 2: say so and I'll send bank transfer details.
>
> Just reply 1 or 2.

**S2 · attendee (email)**
> Subject: {{first_name}}, you said you wanted to {{goal}}
>
> Hi {{first_name}},
>
> When you registered for TAAB you told me your goal was: "{{goal}}".
>
> That's what the six weeks are built for. Nine production workflows, one at a time, plus the playbook for charging for them.
>
> Cohort 3 starts Saturday 12 September. TAAB59 takes ₦10,000 off - ₦59,000 in full until Monday, ₦69,000 after.
>
> https://ajbuildai.com/accelerator
>
> If something's holding you back, reply and tell me what. I read every one.
>
> - AJ

**Mon 31 Aug · early-bird close (all segments)**
> Subject: ₦59,000 until midnight tonight
>
> Hi {{first_name}},
>
> Short one. Early-bird on Cohort 3 ends at midnight tonight, Lagos time.
>
> Until then TAAB59 gets you in for ₦59,000 paid in full. From tomorrow the same code gets ₦69,000.
>
> Nothing else changes - same six weeks, same nine workflows, same guarantee. Only the price.
>
> https://ajbuildai.com/checkout
>
> {{seats_left}} of 30 seats left.
>
> - AJ

**Mon 14 Sep · cart close**
> Subject: Doors close at midnight
>
> Hi {{first_name}},
>
> Cohort 3 started Saturday and enrolment closes tonight at midnight.
>
> You haven't missed anything - week one is setup and it's self-paced. But this is the last time you can join this cohort.
>
> https://ajbuildai.com/checkout
>
> If it's a no, that's fine - reply "next time" and I'll put you on the Cohort 4 list instead of emailing you again.
>
> - AJ

---

## Track daily

| Number | Query |
|---|---|
| Paid seats | `SELECT COUNT(*) FROM enrollments WHERE status='paid' AND cohort=3` |
| New pending rows today | 3-4 in a day = **checkout is broken**, go test it with a real card |
| DM replies | by hand |

**Mon 31 Aug: under 10 seats?** Stop sending. Ring five people from S1 and ask why.
**Mon 8 Sep: under 18?** Add a live debug clinic in week 3, not more email.

---

## Don't

- Run `masterclass:enroll-waitlist` (silent auto-move, no fresh intent). Use `announce`.
- Send S6 more than twice - it costs you deliverability the hot segments need.
- Pitch "come build bots" for TAAB. It sells clarity. Building is the Accelerator.
- Touch `cohort_cap` again now that sending has started. It went 25 → 30 on day 0, which is fine. Moving it after leads have seen "N of 30" is visible fake scarcity.
- Add a second discount code. Two stages is enough; a third teaches them to wait.

---

## After 14 Sep: build `accelerator:campaign`

Nothing fires an Accelerator offer at leads today - `masterclass_followup` only reaches masterclass registrants, 2h post-session. Everything above is manual.

Clone `AnnounceMasterclass`: `--segment`, `--touch`, `--dry-run`, `--limit=270`; ledger table unique on `(email, touch)`; stamp only on 2xx; suppress `status='paid'`. One n8n event `accelerator_campaign`, switch on `touch`. Read `docs/n8n-masterclass-flow.md` first - webhook must be *Respond: When Last Node Finishes*.

~1 day. Don't let it delay week 1.
