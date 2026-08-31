<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="Part 1 Capstone of the AI Automation Accelerator: build a three-workflow quote engine that reads an RFQ, prices it against a rate card, and lets the MD approve and send from her phone.">
<title>Part 1 Capstone - The Quote Engine</title>

@include('guides.partials.chrome-css')
</head>
<body>

<div id="progress"></div>

<header class="topbar">
  <a href="/" class="brand" style="text-decoration:none;color:inherit">AJBUILD<b>AI</b></a>
  <button class="themetoggle" id="tt" aria-label="Toggle colour theme">◐ Theme</button>
</header>

<div class="wrap">
  <nav class="toc" aria-label="Guide contents">
    <div class="lbl">The capstone</div>
    <a href="#business"><span class="n">01</span> The business</a>
    <a href="#problem"><span class="n">02</span> The problem</a>
    <a href="#channels"><span class="n">03</span> Channels</a>
    <a href="#sequence"><span class="n">04</span> How it runs</a>
    <a href="#data"><span class="n">05</span> Data model</a>
    <a href="#build"><span class="n">06</span> Build order</a>
    <a href="#reference"><span class="n">07</span> Workflow reference</a>
    <a href="#breaks"><span class="n">08</span> What will break</a>
    <a href="#stuck"><span class="n">09</span> When it doesn't work</a>
    <a href="#deliverables"><span class="n">10</span> Deliverables</a>
    <a href="#grading"><span class="n">11</span> Grading</a>
    <a href="#selling"><span class="n">12</span> Selling this</a>
    <a href="#rules"><span class="n">13</span> Ground rules</a>
  </nav>

  <main>
    <div class="col">

      <div class="hero" id="top">
        <span class="eyebrow">Part 1 Capstone</span>
        <h1>The Quote Engine. <em>Ninety seconds, not a day.</em></h1>
        <p class="lede">A commercial cleaning company loses contracts it was fully capable of winning, purely on response time. You are going to remove the arithmetic and the forgetting, and leave the MD with the only decision that matters: the price.</p>
        <div class="meta">
          <span class="chip">⏱ <b>7 days</b></span>
          <span class="chip">💳 costs <b>₦0</b> to build</span>
          <span class="chip">🔁 <b>3 workflows</b></span>
          <span class="chip">🎯 graded out of <b>100</b></span>
        </div>

        <div class="term-mock" aria-hidden="true">
          <div class="bar"><i style="background:#f26d5b"></i><i style="background:#f5bf4f"></i><i style="background:#64c97b"></i><span class="u">a real Tuesday</span></div>
          <div class="body">
<span class="m">09:14</span> RFQ lands from Zenith Properties<br>
<span class="c">WF1</span> gmail → drive → gemini → price → airtable → <span class="g">telegram ✓</span> <span class="m">92s</span><br>
<span class="m">09:54</span> MD taps <span class="g">✅ Send</span> from a site visit<br>
<span class="c">WF2</span> airtable → gmail <span class="m">(reply on the original thread)</span> → calendar → <span class="g">sent</span>
          </div>
        </div>
      </div>

      <p style="color:var(--muted)"><strong>How to use this document.</strong> Read sections 01 to 04 to understand what you are building and why. Then work through <a class="link" href="#build">section 06, the Build Order</a>, one stage at a time, in the exact order given. Do not skip ahead. Section 07 is the reference you consult while building.</p>

      <div class="console"><div class="chead"><span class="tag"><i></i> the stack</span></div>
<pre><code>n8n · Gmail · Tally.so · Airtable · Google Sheets
Google Drive · Google Calendar · Telegram · Gemini</code></pre></div>

      <!-- 01 -->
      <section id="business">
        <div class="part-h"><span class="num">01</span><h2>The business</h2></div>
        <p class="part-sub">Know who you are building for before you open n8n.</p>
        <p><strong>SparkleCare Facility Services</strong> - a 20-staff commercial cleaning company in Abuja. They clean offices, schools, banks, hospitals and event halls on monthly contracts, plus one-off deep cleans and post-construction cleaning.</p>
        <p>They do not sell products. They sell <strong>cleaners, hours and scope.</strong> Every naira they earn starts as a quote.</p>
      </section>

      <!-- 02 -->
      <section id="problem">
        <div class="part-h"><span class="num">02</span><h2>The problem</h2></div>
        <p class="part-sub">Two losses. One loud, one quiet.</p>
        <p>A facilities manager at a corporate office emails SparkleCare an RFQ - usually a PDF or Word document listing the areas to be cleaned, the floor sizes, the frequency, and extras like window cleaning or fumigation. What happens next:</p>
        <ol class="steps">
          <li>The email lands while the MD is on a site inspection in Gwarinpa.</li>
          <li>It sits unopened until evening.</li>
          <li>She works out how many cleaners the floor area needs, checks her rate card, adds consumables, prices the extras, applies a contract discount, and types the quote by hand.</li>
          <li>It goes out the next day. Sometimes three days later if a site issue comes up.</li>
          <li>The facilities manager sent that same RFQ to four cleaning companies. Two replied within hours.</li>
        </ol>
        <p><strong>Cleaning is a commodity service.</strong> Rates across Abuja are broadly similar, so the contract often goes to whoever replied first and looked most organised. SparkleCare loses contracts they were fully capable of winning, purely on response time.</p>
        <div class="call warn"><div class="h">⚠ the quiet loss</div><p>Quotes that go out and get no reply. Nobody chases them. A deal that was 90% won is abandoned because there is no system reminding anyone it exists.</p></div>
      </section>

      <!-- 03 -->
      <section id="channels">
        <div class="part-h"><span class="num">03</span><h2>Channels - read this before you build</h2></div>
        <p class="part-sub">Three separate channels. Mixing them up is the most common mistake on this project.</p>
        <div class="tablewrap"><table>
          <thead><tr><th>Direction</th><th>Channel</th><th>Why</th></tr></thead>
          <tbody>
            <tr><td>Customer → business</td><td><b>Gmail</b> (primary), <b>Tally form</b> (secondary)</td><td>Facility managers send RFQs by email because they need a paper trail. Smaller enquiries come through the "Request a Quote" form on the website.</td></tr>
            <tr><td>System → MD</td><td><b>Telegram</b></td><td>The MD's phone. Her control panel.</td></tr>
            <tr><td>Business → customer</td><td><b>Gmail</b>, replying on the original thread</td><td>The customer must receive the quote where they sent the RFQ.</td></tr>
          </tbody>
        </table></div>
        <div class="call note"><div class="h">↳ the rule</div><p><strong>The customer never sees Telegram.</strong> Telegram exists so the MD can approve a quote from a site visit with one tap instead of opening a laptop.</p></div>
        <p style="color:var(--muted)">A real share of Nigerian enquiries arrive on WhatsApp. That channel is deliberately out of scope for Part 1 because of the setup overhead. Adding it later is a Part 2 exercise, and nothing else in this system changes when you do.</p>
      </section>

      <!-- 04 -->
      <section id="sequence">
        <div class="part-h"><span class="num">04</span><h2>How it runs - the live sequence</h2></div>
        <p class="part-sub">What happens on a real Tuesday, start to finish.</p>

        <h3><span class="k">min 0</span> The RFQ arrives</h3>
        <p>A facilities manager at Zenith Properties emails <code class="inl">quotes&#64;sparklecare.ng</code>. Attached is a 2-page RFQ PDF: 1,800 sqm office across 3 floors, daily cleaning, 12-month contract, plus quarterly window cleaning.</p>

        <h3><span class="k">0-2</span> WF1 runs</h3>
        <ol class="steps">
          <li>The Gmail trigger fires and picks up the email and its attachment.</li>
          <li>The attachment is saved to Google Drive.</li>
          <li>Gemini reads the PDF and returns structured data: floor area, frequency, contract length, services requested.</li>
          <li>The rate card is pulled from Google Sheets.</li>
          <li>A Code node does the arithmetic: 1,800 sqm ÷ 450 = 4 cleaners, no supervisor needed, consumables on 1,800 sqm, daily multiplier, window cleaning added, 5% discount for a 12-month contract. It produces a monthly total and an annual total.</li>
          <li>The enquiry and every quote line are saved to Airtable.</li>
          <li>An AI Agent writes the quote email.</li>
          <li>The MD's phone buzzes with the full draft and three buttons: <strong>Send</strong>, <strong>Edit</strong>, <strong>Discard</strong>.</li>
        </ol>
        <div class="call check"><div class="h">✓ WF1 has now finished</div><p>It is gone. Nothing is running or waiting.</p></div>

        <h3><span class="k">min 40</span> WF2 runs</h3>
        <p>The MD finishes her site inspection, reads the draft in the car, and taps <strong>Send</strong>.</p>
        <ol class="steps">
          <li>The button press starts a brand new workflow execution that knows nothing except one ID.</li>
          <li>It looks up that ID in Airtable and pulls back the customer, the email thread, and the quote.</li>
          <li>It checks the quote has not already been sent.</li>
          <li>Gmail sends the quote as a reply on the original thread.</li>
          <li>A Calendar reminder is set for 3 days out. Airtable is marked <code class="inl">Sent</code>. The Telegram buttons are replaced with "✅ Sent to Zenith Properties".</li>
        </ol>

        <h3><span class="k">day 3</span> WF3 runs</h3>
        <p>On its daily schedule, sees no reply, and sends a short follow-up. <strong>Day 6</strong> it runs again, sends a second follow-up, then tells the MD: <em>"Zenith Properties hasn't responded to two follow-ups on ₦892,000/month. Worth a call?"</em></p>

        <div class="call warn"><div class="h">⚠ the point to notice</div><p>Steps 1-8 and steps 9-13 are two completely separate workflow runs, separated by 40 minutes. Nothing carries over between them except an ID stored in Airtable. <strong>That is the single hardest idea in this capstone</strong>, and Stage 10 is where you build it.</p></div>
      </section>

      <!-- 05 -->
      <section id="data">
        <div class="part-h"><span class="num">05</span><h2>Data model</h2></div>
        <p class="part-sub">Build these before you build any workflow.</p>

        <h3><span class="k">5.1</span> Google Sheets - <code class="inl">Rate_Card</code> (Tab 1)</h3>
        <p>The MD maintains this. The only place prices live.</p>
        <div class="tablewrap"><table>
          <thead><tr><th>service_code</th><th>service_name</th><th>unit</th><th>rate</th><th>notes</th></tr></thead>
          <tbody>
            <tr><td><code class="inl">CLN-STD</code></td><td>Standard cleaner</td><td>per cleaner / month</td><td>145000</td><td>Covers ~450 sqm</td></tr>
            <tr><td><code class="inl">CLN-SUP</code></td><td>Site supervisor</td><td>per supervisor / month</td><td>210000</td><td>Required at 5+ cleaners</td></tr>
            <tr><td><code class="inl">CONSUM</code></td><td>Consumables and chemicals</td><td>per sqm / month</td><td>180</td><td></td></tr>
            <tr><td><code class="inl">WIN-HIGH</code></td><td>High-level window cleaning</td><td>per visit</td><td>95000</td><td>Quarterly</td></tr>
            <tr><td><code class="inl">FUM-GEN</code></td><td>General fumigation</td><td>per sqm</td><td>350</td><td>Quarterly</td></tr>
            <tr><td><code class="inl">CARP-SHAM</code></td><td>Carpet shampooing</td><td>per sqm</td><td>900</td><td>On request</td></tr>
            <tr><td><code class="inl">DEEP-1X</code></td><td>One-off deep clean</td><td>per sqm</td><td>1200</td><td></td></tr>
            <tr><td><code class="inl">POST-CON</code></td><td>Post-construction clean</td><td>per sqm</td><td>2100</td><td></td></tr>
          </tbody>
        </table></div>

        <h3><span class="k">5.2</span> Google Sheets - <code class="inl">Config</code> (Tab 2)</h3>
        <div class="tablewrap"><table>
          <thead><tr><th>key</th><th>value</th></tr></thead>
          <tbody>
            <tr><td><code class="inl">sqm_per_cleaner</code></td><td>450</td></tr>
            <tr><td><code class="inl">supervisor_threshold</code></td><td>5</td></tr>
            <tr><td><code class="inl">freq_daily</code></td><td>1.0</td></tr>
            <tr><td><code class="inl">freq_3x_weekly</code></td><td>0.65</td></tr>
            <tr><td><code class="inl">freq_weekly</code></td><td>0.35</td></tr>
            <tr><td><code class="inl">discount_12mo</code></td><td>0.05</td></tr>
            <tr><td><code class="inl">discount_24mo</code></td><td>0.10</td></tr>
          </tbody>
        </table></div>

        <h3><span class="k">5.3</span> Airtable base <code class="inl">QuoteEngine</code> - table <code class="inl">Enquiries</code></h3>
        <div class="tablewrap"><table>
          <thead><tr><th>Field</th><th>Type</th><th>Notes</th></tr></thead>
          <tbody>
            <tr><td><code class="inl">enquiry_id</code></td><td>Autonumber</td><td></td></tr>
            <tr><td><code class="inl">company_name</code></td><td>Single line text</td><td></td></tr>
            <tr><td><code class="inl">contact_name</code></td><td>Single line text</td><td></td></tr>
            <tr><td><code class="inl">contact_email</code></td><td>Email</td><td></td></tr>
            <tr><td><code class="inl">source</code></td><td>Single select</td><td><code class="inl">Email</code>, <code class="inl">Form</code></td></tr>
            <tr><td><code class="inl">gmail_thread_id</code></td><td>Single line text</td><td>So WF2 replies on the right thread</td></tr>
            <tr><td><code class="inl">site_type</code></td><td>Single select</td><td>Office, School, Bank, Hospital, Event Hall, Other</td></tr>
            <tr><td><code class="inl">location</code></td><td>Single line text</td><td></td></tr>
            <tr><td><code class="inl">total_sqm</code></td><td>Number</td><td></td></tr>
            <tr><td><code class="inl">frequency</code></td><td>Single select</td><td>Daily, 3x Weekly, Weekly, One-off</td></tr>
            <tr><td><code class="inl">contract_months</code></td><td>Number</td><td></td></tr>
            <tr><td><code class="inl">drive_file_id</code></td><td>Single line text</td><td>The RFQ document</td></tr>
            <tr><td><code class="inl">quote_monthly</code></td><td>Currency</td><td></td></tr>
            <tr><td><code class="inl">quote_annual</code></td><td>Currency</td><td></td></tr>
            <tr><td><code class="inl">quote_body</code></td><td>Long text</td><td></td></tr>
            <tr><td><code class="inl">quote_subject</code></td><td>Single line text</td><td></td></tr>
            <tr><td><code class="inl">decision_id</code></td><td>Single line text</td><td><b>Links this record to the Telegram buttons</b></td></tr>
            <tr><td><code class="inl">status</code></td><td>Single select</td><td>New, Awaiting Approval, Sent, Followed Up, Won, Lost</td></tr>
            <tr><td><code class="inl">awaiting_edit</code></td><td>Checkbox</td><td></td></tr>
            <tr><td><code class="inl">sent_at</code></td><td>Date</td><td></td></tr>
            <tr><td><code class="inl">follow_up_count</code></td><td>Number</td><td>Default 0</td></tr>
          </tbody>
        </table></div>

        <h3><span class="k">5.4</span> Table <code class="inl">Quote_Lines</code></h3>
        <div class="tablewrap"><table>
          <thead><tr><th>Field</th><th>Type</th><th>Notes</th></tr></thead>
          <tbody>
            <tr><td><code class="inl">enquiry</code></td><td>Link → Enquiries</td><td></td></tr>
            <tr><td><code class="inl">requested_scope</code></td><td>Single line text</td><td>What the RFQ actually asked for</td></tr>
            <tr><td><code class="inl">service_code</code></td><td>Single line text</td><td>From the rate card, or blank</td></tr>
            <tr><td><code class="inl">quantity</code></td><td>Number</td><td>Cleaners, sqm, or visits</td></tr>
            <tr><td><code class="inl">unit</code></td><td>Single line text</td><td></td></tr>
            <tr><td><code class="inl">rate</code></td><td>Currency</td><td></td></tr>
            <tr><td><code class="inl">line_total</code></td><td>Formula</td><td><code class="inl">quantity × rate</code></td></tr>
            <tr><td><code class="inl">status</code></td><td>Single select</td><td>Priced, Needs Manual Pricing</td></tr>
          </tbody>
        </table></div>
        <div class="call warn"><div class="h">⚠ never guess a rate</div><p>Anything the system cannot price becomes a <code class="inl">Needs Manual Pricing</code> line. Surface it to the MD instead.</p></div>
      </section>

      <!-- 06 -->
      <section id="build">
        <div class="part-h"><span class="num">06</span><h2>Build order</h2></div>
        <p class="part-sub">Twelve stages, in this order. Each is small enough to finish and verify in one sitting.</p>
        <div class="call note"><div class="h">↳ the discipline</div><p><strong>Do not start a stage until the previous stage's "Done when" test passes.</strong> Every stage has one for a reason.</p></div>

        <h3><span class="k">St 0</span> Pre-flight check</h3>
        <p>You already have every account this project needs. Five minutes of verification now, so you do not lose an evening to an expired token in the middle of Stage 9.</p>
        <p><strong>Do this.</strong> Run a throwaway workflow with a Manual Trigger and confirm each of these still authenticates:</p>
        <ul class="clean check">
          <li><strong>Telegram</strong> - send yourself a message, and have your chat ID to hand</li>
          <li><strong>Gmail</strong> - list your last 5 messages</li>
          <li><strong>Google Sheets, Drive and Calendar</strong> - read anything</li>
          <li><strong>Airtable</strong> - read any base</li>
          <li><strong>Gemini</strong> - send any prompt and confirm you are not out of quota</li>
        </ul>
        <div class="call check"><div class="h">✓ done when</div><p>All six respond without an auth error. Fix anything that fails now, not later - Google OAuth tokens in particular go stale if you have not used them in a while.</p></div>

        <h3><span class="k">St 1</span> The rate card</h3>
        <p><strong>Do this.</strong> Create the Google Sheet with both tabs exactly as in section 05.</p>
        <div class="call check"><div class="h">✓ done when</div><p>A two-node workflow (Manual Trigger → Google Sheets read) returns all 8 rate card rows and all 7 config rows in n8n.</p></div>

        <h3><span class="k">St 2</span> The database</h3>
        <p><strong>Do this.</strong> Create the Airtable base with both tables and every field listed in section 05. Get the field types right - <code class="inl">decision_id</code> must be <strong>Single line text</strong>, <code class="inl">total_sqm</code> must be <strong>Number</strong>.</p>
        <div class="call check"><div class="h">✓ done when</div><p>You can create one test record in <code class="inl">Enquiries</code> from n8n, and see it appear in Airtable.</p></div>

        <h3><span class="k">St 3</span> The intake form</h3>
        <p><strong>Do this.</strong> Build the Tally form: company name, contact name, email, site type, location, floor area in sqm, frequency, contract length, and an optional file upload.</p>
        <div class="call check"><div class="h">✓ done when</div><p>A Webhook node in n8n receives a real test submission and you can see the file arriving <strong>as a URL, not as binary</strong>. Look at the JSON and confirm this yourself - it matters in Stage 5.</p></div>

        <h3><span class="k">St 4</span> Gmail intake</h3>
        <p><strong>Do this.</strong> Create a Gmail label called <code class="inl">RFQ</code>. Set up a Gmail Trigger node in n8n filtered to that label. Email yourself a test RFQ PDF and apply the label.</p>
        <div class="call check"><div class="h">✓ done when</div><p>The trigger fires and you can see the attachment as binary data on the node output, plus the <code class="inl">threadId</code> in the JSON. <strong>Save that field name - WF2 depends on it.</strong></p></div>

        <h3><span class="k">St 5</span> WF1: both entries become one shape</h3>
        <p>This is where most people create problems for themselves later. Get it right now.</p>
        <ol class="steps">
          <li>In one workflow, place both triggers: Gmail Trigger and Webhook.</li>
          <li>On the Webhook branch only, add an <strong>HTTP Request</strong> node - GET the Tally file URL, <strong>Response Format: File</strong>. This converts the URL into binary.</li>
          <li>Add a <strong>Code node - Normalise Entry</strong> on each branch that outputs the same shape.</li>
          <li>Merge both branches.</li>
          <li><strong>Google Drive</strong> - upload the binary to <code class="inl">/QuoteEngine/RFQs/</code>. Save the returned file ID.</li>
        </ol>
        <div class="console"><div class="chead"><span class="tag"><i></i> the shape both branches must produce</span><button class="copybtn">Copy</button></div>
<pre><code>{
  "source": "Email",
  "company_name": "...",
  "contact_name": "...",
  "contact_email": "...",
  "gmail_thread_id": "...",
  "raw_text": "...",
  "has_file": true
}</code></pre></div>
        <p>For the Tally branch, <code class="inl">gmail_thread_id</code> is an empty string. Fill everything else from the form fields.</p>
        <div class="call check"><div class="h">✓ done when</div><p>You can trigger the workflow from Gmail <em>and</em> from Tally, and both produce identical JSON structure plus a file in Drive. <strong>Test both.</strong> Do not move on having tested only one.</p></div>

        <h3><span class="k">St 6</span> WF1: read the document</h3>
        <p><strong>Do this.</strong> Add the <strong>Gemini Analyze Document</strong> node after the Drive upload. Pass the binary. Prompt it to return <strong>only</strong> this JSON, with no explanation and no markdown:</p>
        <div class="console"><div class="chead"><span class="tag"><i></i> expected extraction</span><button class="copybtn">Copy</button></div>
<pre><code>{
  "site_type": "Office",
  "location": "Central Business District, Abuja",
  "total_sqm": 1800,
  "frequency": "Daily",
  "contract_months": 12,
  "scope_items": ["daily office cleaning", "quarterly window cleaning"],
  "notes": "cleaning must happen before 7am",
  "confidence": 0.9
}</code></pre></div>
        <p>Then add a <strong>Code node</strong> that strips markdown code fences before parsing. Gemini adds them roughly half the time. Write it defensively:</p>
        <div class="console"><div class="chead"><span class="tag"><i></i> code node</span><button class="copybtn">Copy</button></div>
<pre><code>const clean = raw.replace(/```json/g, '').replace(/```/g, '').trim();
const data = JSON.parse(clean);</code></pre></div>
        <p>Then add an <strong>IF</strong> node: is <code class="inl">total_sqm</code> present and greater than zero?</p>
        <ul class="clean">
          <li><strong>False</strong> → Telegram the MD: <em>"RFQ from {company} - couldn't read the floor area. Have a look."</em> with the Drive link. Stop.</li>
          <li><strong>True</strong> → continue.</li>
        </ul>
        <div class="call check"><div class="h">✓ done when</div><p>Three different test RFQs - a clean PDF, a scanned photo, and one with no floor area stated - all produce either valid parsed JSON or a clean Telegram alert. No workflow crashes.</p></div>

        <h3><span class="k">St 7</span> WF1: the pricing engine</h3>
        <div class="call note"><div class="h">↳ build it in isolation</div><p><strong>Build and test this Code node on its own before wiring it in.</strong> Use a Manual Trigger and a Set node with fake extraction data so you can iterate fast.</p></div>
        <p><strong>Do this.</strong> Read the rate card and config from Google Sheets, then compute:</p>
        <div class="console"><div class="chead"><span class="tag"><i></i> the arithmetic</span><button class="copybtn">Copy</button></div>
<pre><code>cleaners      = ceil(total_sqm / sqm_per_cleaner)
supervisors   = cleaners >= supervisor_threshold ? 1 : 0
labour        = (cleaners × CLN-STD rate) + (supervisors × CLN-SUP rate)
consumables   = total_sqm × CONSUM rate
monthly_base  = (labour + consumables) × frequency_multiplier</code></pre></div>
        <p>Then loop <code class="inl">scope_items</code>. Normalise each one and each rate card <code class="inl">service_name</code> - lowercase, trim, strip punctuation - and match by text containment. Matched → priced line. No match → a line with <code class="inl">status: "Needs Manual Pricing"</code> and rate 0. Then apply the contract discount and output <code class="inl">monthly_total</code>, <code class="inl">annual_total</code>, <code class="inl">lines[]</code>, <code class="inl">unpriced_count</code>.</p>
        <div class="tablewrap"><table>
          <thead><tr><th>Input</th><th>Expected</th></tr></thead>
          <tbody>
            <tr><td>1,800 sqm, Daily, 12 months, no extras</td><td>4 cleaners, 0 supervisors, 5% discount applied</td></tr>
            <tr><td>2,700 sqm, Daily, 24 months, window cleaning</td><td>6 cleaners, <b>1 supervisor</b>, 10% discount, window line priced</td></tr>
            <tr><td>900 sqm, Weekly, 6 months, "pressure washing"</td><td>2 cleaners, 0.35 multiplier, no discount, <b>1 unpriced line</b></td></tr>
          </tbody>
        </table></div>
        <div class="call check"><div class="h">✓ done when</div><p>All three test cases give the right answer. Work them out by hand first, then check the node agrees. <strong>If it does not, fix the node - not the expected answer.</strong></p></div>

        <h3><span class="k">St 8</span> WF1: save to Airtable</h3>
        <p><strong>Do this.</strong> Create the <code class="inl">Enquiries</code> record, then one <code class="inl">Quote_Lines</code> record per line, linked to it.</p>
        <div class="call check"><div class="h">✓ done when</div><p>A full run produces one enquiry record with the correct totals and the right number of linked quote lines.</p></div>

        <h3><span class="k">St 9</span> WF1: draft and send to Telegram</h3>
        <ol class="steps">
          <li><strong>AI Agent - Quote Writer.</strong> Feed it the company, contact name, site details, priced lines, unpriced items, monthly and annual totals, and the discount. Return JSON: <code class="inl">{ "subject", "body" }</code>.</li>
          <li><strong>Code node</strong> - generate a <code class="inl">decision_id</code>. A timestamp plus a random string is fine.</li>
          <li><strong>Airtable</strong> - save <code class="inl">decision_id</code>, <code class="inl">quote_subject</code>, <code class="inl">quote_body</code>, <code class="inl">quote_monthly</code>, <code class="inl">quote_annual</code>. Set <code class="inl">status</code> to <code class="inl">Awaiting Approval</code>.</li>
          <li><strong>Telegram</strong> - send the MD the draft, the monthly total, and the unpriced count, with an inline keyboard: <code class="inl">✅ Send</code>, <code class="inl">✏️ Edit</code>, <code class="inl">❌ Discard</code>.</li>
        </ol>
        <div class="console"><div class="chead"><span class="tag"><i></i> decision id</span><button class="copybtn">Copy</button></div>
<pre><code>Date.now() + '-' + Math.random().toString(36).slice(2,8)</code></pre></div>
        <p><strong>Prompt rules for the Quote Writer:</strong> write as the MD of a cleaning company, not as an AI. Nigerian business English - polite and direct, no American filler. Show the staffing and scope as a clear breakdown so the customer sees what they are paying for. State the monthly figure and the contract total. If anything could not be priced, say plainly that pricing for those items will follow separately. Close by offering a site visit.</p>
        <div class="call warn"><div class="h">⚠ 64 characters</div><p><strong>Put only the <code class="inl">decision_id</code> in each button's <code class="inl">callback_data</code>.</strong> Telegram caps it at 64 characters. Nothing else fits. Do not try.</p></div>
        <div class="call check"><div class="h">✓ done when</div><p>Emailing an RFQ to yourself results in a complete, readable quote arriving on your phone with three working-looking buttons. <strong>WF1 is now finished.</strong></p></div>

        <h3><span class="k">St 10</span> WF2: the approval workflow</h3>
        <div class="call warn"><div class="h">⚠ this is the hard one</div><p><strong>A new, separate workflow.</strong> WF1 has already finished executing by the time the MD taps a button. This workflow starts from nothing and must rebuild everything from one ID.</p></div>
        <ol class="steps">
          <li><strong>Telegram Trigger</strong> - in the node's update settings, enable <strong>Callback Query</strong>. It is off by default and this is the single most common place students get stuck.</li>
          <li><strong>Code node</strong> - read <code class="inl">decision_id</code> and the chosen action from the callback payload.</li>
          <li><strong>Airtable Search</strong> - find the enquiry where <code class="inl">decision_id</code> equals that value. You now have the customer, the thread ID, the quote and the totals back in hand.</li>
          <li><strong>IF</strong> - is <code class="inl">status</code> still <code class="inl">Awaiting Approval</code>? <strong>False</strong> → edit the Telegram message to "Already handled" and stop. <em>This is what stops a double tap sending two quotes.</em> <strong>True</strong> → continue.</li>
          <li><strong>Switch</strong> on the action - see the table below.</li>
          <li>Add a <strong>Telegram "Answer Callback Query"</strong> action so the button stops showing a loading spinner on her phone.</li>
        </ol>
        <div class="tablewrap"><table>
          <thead><tr><th>Action</th><th>What happens</th></tr></thead>
          <tbody>
            <tr><td><b>Send</b></td><td>Gmail reply using <code class="inl">gmail_thread_id</code> → Calendar event 3 days out → Airtable <code class="inl">status: Sent</code>, set <code class="inl">sent_at</code> → edit the Telegram message to "✅ Sent to {company}" so the buttons disappear.</td></tr>
            <tr><td><b>Edit</b></td><td>Telegram: "Send me the corrected quote as your next message." Tick <code class="inl">awaiting_edit</code>. A second Telegram trigger on normal text messages catches her next message, looks up the record with <code class="inl">awaiting_edit</code> ticked, saves it as <code class="inl">quote_body</code>, and sends it.</td></tr>
            <tr><td><b>Discard</b></td><td>Airtable <code class="inl">status: Lost</code>, edit the message to "❌ Discarded".</td></tr>
          </tbody>
        </table></div>
        <div class="call check"><div class="h">✓ done when</div><p>All three buttons work, the quote arrives on the <strong>original email thread</strong> (not as a new email), and tapping <code class="inl">✅ Send</code> twice sends exactly one email.</p></div>

        <h3><span class="k">St 11</span> WF3: follow-up</h3>
        <p><strong>A third separate workflow.</strong></p>
        <ol class="steps">
          <li><strong>Schedule Trigger</strong> - daily, 09:00 WAT.</li>
          <li><strong>Airtable Search</strong> - <code class="inl">status</code> is <code class="inl">Sent</code>, <code class="inl">sent_at</code> is 3 or more days ago, <code class="inl">follow_up_count</code> is under 2.</li>
          <li><strong>AI Agent</strong> - a two-sentence follow-up referencing the specific site and monthly figure. It must not reuse the wording of the original quote.</li>
          <li><strong>Gmail</strong> - reply on the same thread.</li>
          <li><strong>Airtable</strong> - increment <code class="inl">follow_up_count</code>, set <code class="inl">status</code> to <code class="inl">Followed Up</code>.</li>
          <li><strong>IF</strong> <code class="inl">follow_up_count</code> is now 2 → <strong>Telegram</strong> the MD: <em>"{company} hasn't responded to two follow-ups on ₦{monthly}/month. Worth a call?"</em></li>
        </ol>
        <div class="call check"><div class="h">✓ done when</div><p>You can backdate a test record's <code class="inl">sent_at</code> by 4 days, run the workflow manually, and watch the follow-up send correctly.</p></div>

        <h3><span class="k">St 12</span> Harden, then record</h3>
        <p><strong>Do this.</strong> Work through every item in section 08. Fix what breaks. Then record your Loom and write your README.</p>
        <div class="call check"><div class="h">✓ done when</div><p>All six items in section 08 behave correctly and you have not had to explain away any of them.</p></div>
      </section>

      <!-- 07 -->
      <section id="reference">
        <div class="part-h"><span class="num">07</span><h2>Workflow reference</h2></div>
        <p class="part-sub">What you built, for your README and your own sanity.</p>
        <div class="tablewrap"><table>
          <thead><tr><th>Workflow</th><th>Trigger</th><th>Ends by</th></tr></thead>
          <tbody>
            <tr><td><b>WF1 - RFQ Intake &amp; Quote Draft</b></td><td>Gmail (label <code class="inl">RFQ</code>) or Webhook (Tally)</td><td>Sending the MD a Telegram draft with three buttons</td></tr>
            <tr><td><b>WF2 - Approve &amp; Send</b></td><td>Telegram callback query</td><td>Emailing the customer and updating Airtable</td></tr>
            <tr><td><b>WF3 - Follow-Up</b></td><td>Schedule, daily 09:00</td><td>Chasing unanswered quotes, then alerting the MD</td></tr>
          </tbody>
        </table></div>
      </section>

      <!-- 08 -->
      <section id="breaks">
        <div class="part-h"><span class="num">08</span><h2>Things that will break your build</h2></div>
        <p class="part-sub">Test all six. Each one has bitten someone before you.</p>
        <div class="tablewrap"><table>
          <thead><tr><th>#</th><th>Scenario</th><th>What good looks like</th></tr></thead>
          <tbody>
            <tr><td>1</td><td>Gemini wraps its JSON in code fences</td><td>Your Code node strips them and parses cleanly</td></tr>
            <tr><td>2</td><td>RFQ says <em>"3 floors, roughly 600sqm each"</em></td><td>Total area resolves to 1,800 - handle it in the Gemini prompt</td></tr>
            <tr><td>3</td><td>The RFQ is a scanned photo of a printed page</td><td>Extraction still works, or a clean Telegram alert fires</td></tr>
            <tr><td>4</td><td>A Tally enquiry arrives with no file attached</td><td>The form fields alone still produce a quote</td></tr>
            <tr><td>5</td><td>The MD taps <code class="inl">✅ Send</code> twice</td><td>Exactly one email goes out</td></tr>
            <tr><td>6</td><td>The RFQ asks for pressure washing, not on the rate card</td><td>Line appears as <code class="inl">Needs Manual Pricing</code>, quote still sends, MD is told</td></tr>
          </tbody>
        </table></div>
      </section>

      <!-- 09 -->
      <section id="stuck">
        <div class="part-h"><span class="num">09</span><h2>When something does not work</h2></div>
        <p class="part-sub">Check here before rebuilding anything.</p>
        <div class="tablewrap"><table>
          <thead><tr><th>Symptom</th><th>Almost always the cause</th></tr></thead>
          <tbody>
            <tr><td>Telegram buttons do nothing</td><td>Callback Query is not enabled on the Telegram Trigger</td></tr>
            <tr><td>Button works but nothing found in Airtable</td><td><code class="inl">decision_id</code> was never saved, or the search field name is wrong</td></tr>
            <tr><td>The quote arrives as a new email, not a reply</td><td><code class="inl">gmail_thread_id</code> was not captured in Stage 4 or not passed in Stage 10</td></tr>
            <tr><td>The Tally file is empty or unreadable</td><td>You skipped the HTTP Request node - Tally sends a URL, not binary</td></tr>
            <tr><td><code class="inl">JSON.parse</code> fails</td><td>Code fences from Gemini, or Gemini returned prose instead of JSON. Tighten the prompt.</td></tr>
            <tr><td>Two quotes sent to one customer</td><td>Missing the status check in Stage 10 step 4</td></tr>
            <tr><td>Pricing is wrong</td><td>Recheck against the three test cases in Stage 7 before touching anything else</td></tr>
          </tbody>
        </table></div>
      </section>

      <!-- 10 -->
      <section id="deliverables">
        <div class="part-h"><span class="num">10</span><h2>Deliverables</h2></div>
        <p class="part-sub">Five things. Submit them together.</p>
        <ol class="steps">
          <li><strong>Three workflow JSON exports</strong>, credentials stripped.</li>
          <li><strong>A completed <code class="inl">Rate_Card</code> and <code class="inl">Config</code> sheet.</strong></li>
          <li><strong>A populated Airtable base</strong> - at least 6 enquiries across the different statuses, from both sources.</li>
          <li><strong>A 5-minute Loom.</strong> Send a real RFQ email, approve on your phone, show the quote arriving in the customer's inbox on the original thread. Do not tour the canvas.</li>
          <li><strong>A short README</strong> covering your pricing logic and how you handled all six items in section 08.</li>
        </ol>
      </section>

      <!-- 11 -->
      <section id="grading">
        <div class="part-h"><span class="num">11</span><h2>Grading - 100 points</h2></div>
        <p class="part-sub">Below 60 is a resubmit, not a fail.</p>
        <div class="tablewrap"><table>
          <thead><tr><th>Area</th><th>Pts</th><th>Full marks</th></tr></thead>
          <tbody>
            <tr><td>Pricing accuracy</td><td><b>20</b></td><td>All three Stage 7 test cases pass. Unpriced items flagged, never guessed.</td></tr>
            <tr><td>Async context</td><td><b>20</b></td><td>WF2 rebuilds full context from <code class="inl">decision_id</code> alone. Buttons cannot be pressed twice.</td></tr>
            <tr><td>Document handling</td><td><b>15</b></td><td>Binary fetched and stored correctly from both sources. Extraction survives PDFs and photos.</td></tr>
            <tr><td>Quote quality</td><td><b>15</b></td><td>The MD would send it without editing it.</td></tr>
            <tr><td>Channel discipline</td><td><b>10</b></td><td>Both entry points merge cleanly. The reply lands on the original email thread. Telegram is never used to talk to the customer.</td></tr>
            <tr><td>Error handling</td><td><b>10</b></td><td>Unreadable RFQ, missing floor area, failed send - each has a visible path.</td></tr>
            <tr><td>Craft</td><td><b>10</b></td><td>Named nodes, notes on tricky logic, no hardcoded secrets.</td></tr>
          </tbody>
        </table></div>
      </section>

      <!-- 12 -->
      <section id="selling">
        <div class="part-h"><span class="num">12</span><h2>Selling this</h2></div>
        <p class="part-sub">The system is not specific to cleaning. Swap the rate card and it fits any service business that quotes from a scope document.</p>
        <p><strong>Who buys it:</strong> cleaning and facility management, security services, fumigation, corporate training, catering and events, haulage, generator servicing, interior fit-out, landscaping.</p>
        <div class="call note"><div class="h">↳ the opening question</div><p><em>"When an RFQ lands in your inbox, how long before the client gets your quote?"</em></p><p style="margin-top:.6rem">Every honest answer is a day or more. Then ask how many RFQs they get in a month, and how many they think they lost to someone faster.</p></div>
        <div class="tablewrap"><table>
          <thead><tr><th>Component</th><th>Range</th></tr></thead>
          <tbody>
            <tr><td>Setup</td><td><b>₦250,000 - ₦600,000</b></td></tr>
            <tr><td>Monthly management</td><td><b>₦50,000 - ₦120,000</b></td></tr>
          </tbody>
        </table></div>
        <p><strong>The demo that closes.</strong> Ask for a real RFQ they received last month and their rate card. Run it live. The quote they spent an evening on appears on their phone in ninety seconds, waiting for one tap. That is the whole sale - never show them the canvas.</p>
      </section>

      <!-- 13 -->
      <section id="rules">
        <div class="part-h"><span class="num">13</span><h2>Ground rules</h2></div>
        <ul class="clean">
          <li>Everything runs on free tiers. Needing a credit card means you took a wrong turn - ask in the cohort channel.</li>
          <li>No credentials hardcoded in Code nodes.</li>
          <li>Follow the Build Order. Every stage has a "Done when" test for a reason.</li>
          <li>Test with a real rate card from a real service business. Ask someone you know. <strong>Invented data hides every pricing bug you have.</strong></li>
        </ul>

        <div class="console"><div class="chead"><span class="tag"><i></i> pacing - 7 days</span></div>
<pre><code>Day 1  →  Stages 0-2      Day 5  →  Stage 10
Day 2  →  Stages 3-5      Day 6  →  Stage 11 + hardening
Day 3  →  Stages 6-7      Day 7  →  Loom + README
Day 4  →  Stages 8-9</code></pre></div>
      </section>

      <div class="foot">AJBuildAI · AI Automation Accelerator · Part 1 Capstone</div>
    </div>
  </main>
</div>

@include('guides.partials.chrome-js')
</body>
</html>
