# Accelerator trailer - brief, script and shot list

The 60-90 second video that sits above the pricing block on `/accelerator`. Click to play,
never autoplay (Nigerian mobile traffic on metered data). Poster frame is a workflow canvas,
not a face.

---

## The one rule that decides everything else

**Nothing in this video may be a fact that lives in config.** No price, no cohort dates, no
seat counts, no "starts Monday". Those change, and a video cannot be edited by changing
`config/accelerator.php`. The moment the price moves, a trailer that quotes it becomes a
liability on your own landing page - the exact failure we just spent the day pulling out of
the follow-up email, the scorecard and the deck.

The page around the video already renders all of that live. Let it.

Also out: income claims ("make ₦500k a month"), "6 weeks", and anything implying the
certificate is accredited. It is a **Certificate of Completion**, and the page says so.

---

## What it has to do, in order of importance

1. **Show an automation actually running in the first 5 seconds.** Most of your traffic has
   never seen an n8n canvas. They cannot want this until they can picture it.
2. **Make the thing feel owned, not rented.** Self-hosted, no monthly tool bills. That is
   your real differentiator against every "AI tools" course.
3. **Kill the "I'll never finish" fear.** Ship-to-unlock, live clinics, daily Telegram,
   and a guarantee with your name on it.
4. **Then, and only then, ask for the click.**

It should feel like a product demo, not a pitch. You are on camera for about 15 of the 70
seconds; the rest is screen.

---

## Script - 70 seconds

Timings are a target, not a straitjacket. Voice over recorded separately from the screen
capture, then laid under it.

### 0:00 - 0:06 | COLD OPEN. No voice at all.

**Screen:** a WhatsApp thread. A message arrives from a stranger: *"Hi, do you still have
space for Saturday?"* Typing dots. A full, sensible reply comes back.

Hard cut to the n8n canvas sitting behind it, nodes firing left to right.

**On-screen text:** `Nobody typed that reply.`

> This is the whole video in six seconds. If someone bounces at 0:07 they still got the idea.

### 0:06 - 0:16 | THE PROBLEM. First time on camera.

> "That is a WhatsApp agent, running on a server I own. No monthly fee for the bot, no
> platform that can switch it off.
>
> Most people who try to learn this end up with twenty browser tabs, a half-built workflow,
> and a tool bill nobody warned them about."

### 0:16 - 0:38 | WHAT YOU BUILD. Fast cuts, screen only.

Each canvas on screen for 2-3 seconds, module name in the corner.

> "In the Accelerator you build nine of these, end to end.
>
> A lead qualifier that scores an enquiry and routes it. A knowledge base your agent actually
> reads from, so it stops making things up. A WhatsApp agent. A voice agent that answers the
> phone and books the appointment. A chat agent that knows when to stop and hand you the
> conversation."

**Cuts, in this order:** Module 03 Lead Qualifier · Module 05 Knowledge Base (RAG) ·
Module 06 WhatsApp Automation · Module 07 AI Voice Support Agent · Module 08 AI Chat Support
Agent.

### 0:38 - 0:52 | HOW IT RUNS. Screen: the LMS, a checkpoint being approved, the Telegram threads.

> "It is self-paced video, but you are not on your own.
>
> You ship a build to unlock the next module, so you cannot quietly drift. There are live
> Build and Debug clinics every week. There is help in the Telegram threads every day.
>
> And everything runs on your own infrastructure, on free tier hosting. No monthly tool bills."

### 0:52 - 1:05 | GUARANTEE AND CERTIFICATE. Back on camera.

**Screen inset:** the certificate, then the public `/verify` page confirming the code.

> "I review every build myself. Finish all nine and you get a Certificate of Completion with
> a code anyone can check on a public page.
>
> And if you do the work and your stack still is not live at the end, I coach you one to one
> until it is."

### 1:05 - 1:12 | CTA.

> "Eight weeks. Nine workflows you own, and the playbook to charge for them."

**On-screen, held:** `ajbuildai.com/accelerator`

---

## Shot list for the screen capture

Capture these in the same session as the still screenshots for the page - same setup, and
the stills are just frames from it.

| # | Shot | Why |
|---|---|---|
| 1 | WhatsApp thread, message in and reply out, on a phone or WhatsApp Web | the cold open |
| 2 | Module 03 Lead Qualifier canvas, zoomed to fit | |
| 3 | Module 05 RAG canvas, Pinecone node visible | the "it does not hallucinate" proof |
| 4 | Module 06 WhatsApp canvas | |
| 5 | Module 07 Voice canvas + a Vapi call actually connecting | most impressive shot you have |
| 6 | Module 08 Chat agent canvas, handover branch visible | |
| 7 | **An execution log with green ticks running down it** | the single best "this is alive" shot |
| 8 | LMS dashboard, a module unlocking after a checkpoint is approved | sells ship-to-unlock |
| 9 | A certificate, then `/verify` returning it as genuine | sells the ending |

### Capture settings

- **1920x1080, 30fps minimum.** n8n in dark theme to match the page.
- **Zoom the browser to 125-150%** before recording. Node labels that are readable on your
  monitor are unreadable on a phone, and that is where this gets watched.
- Hold each canvas still for 2-3 seconds. No fast panning or zooming - it reads as chaos at
  phone size.
- **Burn in captions.** Most of this plays muted.
- Export H.264 MP4, upload to Bunny, send me the video id. I will wire the embed using the
  same pattern the self-hosting guide already uses.

### Scrub before sending

Check every frame for credential names, API keys, real customer phone numbers, emails and
server addresses. A pinned n8n execution on this project leaked a paying customer's name,
email, IP and a working access token earlier today - a screen recording of the same canvas
is the same risk with no scanner to catch it.

---

## While you are there: the stills for the page

Same session, same setup. I need **3-5 canvas screenshots**, 1600-2000px wide, dark theme,
zoomed to fit, scrubbed as above. Lead Qualifier, WhatsApp, Voice and the execution log are
the four that earn their place. PNG is fine - I will convert to WebP and keep the whole
page's media budget under ~500KB, since Hostinger serves the committed repo with no CDN in
front of it.

---

## A separate 9:16 cut for TikTok

Do not post this file vertically. It is a different edit: the cold open becomes the whole
first 3 seconds, the guarantee section goes, and the CTA is spoken rather than held on
screen. Same footage, 30-40 seconds, cut after the page version is locked.
