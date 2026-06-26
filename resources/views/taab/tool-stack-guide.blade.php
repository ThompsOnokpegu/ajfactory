<x-layouts.taab
    title="AI Automation Tool Stack Guide — TAAB"
    description="The right AI automation tools for your level — beginner to advanced — with real monthly costs and recommended stacks.">

@push('styles')
<style>
  /* Sticky header */
  .sticky-header { position: sticky; top: 0; z-index: 100; background: rgba(12,12,14,0.92); backdrop-filter: blur(8px); border-bottom: 1px solid var(--border); padding: 0 2rem; }
  .sticky-inner { max-width: 900px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; height: 52px; gap: 12px; }
  .sticky-brand { font-family: 'Syne', sans-serif; font-size: 13px; font-weight: 700; letter-spacing: 0.06em; color: var(--text); white-space: nowrap; }
  .level-tabs { display: flex; gap: 4px; }
  .level-tab { font-family: 'Syne', sans-serif; font-size: 11px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; padding: 5px 14px; border-radius: 100px; border: 1px solid transparent; cursor: pointer; transition: all 0.15s; background: transparent; color: var(--muted); }
  .level-tab:hover { color: var(--text); }
  .level-tab.active-beg { background: var(--beg-bg); border-color: var(--beg-border); color: var(--beg); }
  .level-tab.active-int { background: var(--int-bg); border-color: var(--int-border); color: var(--int); }
  .level-tab.active-adv { background: var(--adv-bg); border-color: var(--adv-border); color: var(--adv); }
  .level-tab.active-all { background: var(--surface2); border-color: var(--border-strong); color: var(--text); }

  .container { max-width: 900px; margin: 0 auto; padding: 3rem 2rem 5rem; position: relative; z-index: 1; }

  .hero { margin-bottom: 3rem; }
  .badge { display: inline-block; font-family: 'IBM Plex Mono', monospace; font-size: 10px; font-weight: 500; letter-spacing: 0.08em; color: var(--muted); border: 1px solid var(--border); padding: 4px 10px; border-radius: 100px; margin-bottom: 1rem; }
  h1 { font-family: 'Syne', sans-serif; font-size: clamp(1.75rem, 4vw, 2.75rem); font-weight: 800; letter-spacing: -0.025em; line-height: 1.1; margin-bottom: 0.75rem; }
  .hero-sub { font-size: 15px; color: var(--muted); font-weight: 300; max-width: 560px; line-height: 1.7; }

  .level-section { margin-bottom: 3rem; }
  .level-header { display: flex; align-items: center; gap: 12px; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 2px solid; }
  .level-header.beg { border-color: var(--beg); }
  .level-header.int { border-color: var(--int); }
  .level-header.adv { border-color: var(--adv); }
  .level-badge { font-family: 'Syne', sans-serif; font-size: 10px; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; padding: 4px 12px; border-radius: 100px; }
  .beg .level-badge { background: var(--beg-bg); color: var(--beg); }
  .int .level-badge { background: var(--int-bg); color: var(--int); }
  .adv .level-badge { background: var(--adv-bg); color: var(--adv); }
  .level-title { font-family: 'Syne', sans-serif; font-size: 1.1rem; font-weight: 700; }
  .level-who { font-size: 13px; color: var(--muted); font-weight: 300; margin-left: auto; font-style: italic; }

  .tool-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 12px; }
  .tool-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); padding: 16px; display: flex; flex-direction: column; gap: 10px; transition: border-color 0.15s, box-shadow 0.15s; }
  .tool-card:hover { border-color: var(--border-strong); box-shadow: 0 2px 12px rgba(0,0,0,0.3); }
  .tool-card-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 8px; }
  .tool-name { font-family: 'Syne', sans-serif; font-size: 14px; font-weight: 700; color: var(--text); }
  .tool-category { font-family: 'IBM Plex Mono', monospace; font-size: 9px; font-weight: 500; letter-spacing: 0.08em; text-transform: uppercase; color: var(--faint); margin-top: 2px; }
  .cost-badge { font-family: 'IBM Plex Mono', monospace; font-size: 10px; font-weight: 500; padding: 3px 8px; border-radius: var(--radius-sm); white-space: nowrap; }
  .cost-free { background: var(--beg-bg); color: var(--beg); }
  .cost-paid { background: var(--surface2); color: var(--text); }
  .cost-freemium { background: var(--int-bg); color: var(--int); }
  .tool-desc { font-size: 12px; color: var(--muted); font-weight: 300; line-height: 1.55; }
  .tool-tags { display: flex; flex-wrap: wrap; gap: 5px; }
  .tag { font-size: 10px; padding: 2px 8px; border-radius: 100px; background: var(--surface2); color: var(--muted); border: 1px solid var(--border); }
  .tool-cost-detail { font-family: 'IBM Plex Mono', monospace; font-size: 10px; color: var(--faint); padding-top: 4px; border-top: 1px solid var(--border); }
  .tool-cost-detail strong { color: var(--muted); font-weight: 500; }

  .combos-section { margin-top: 3rem; }
  .combos-title { font-family: 'Syne', sans-serif; font-size: 11px; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; color: var(--muted); margin-bottom: 1rem; display: flex; align-items: center; gap: 12px; }
  .combos-title::after { content: ''; flex: 1; height: 1px; background: var(--border); }
  .combo-card { border: 1px solid var(--border); border-radius: var(--radius); padding: 16px; margin-bottom: 10px; display: flex; gap: 16px; align-items: flex-start; }
  .combo-level { font-family: 'Syne', sans-serif; font-size: 10px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; min-width: 70px; padding-top: 2px; }
  .combo-card.beg .combo-level { color: var(--beg); }
  .combo-card.int .combo-level { color: var(--int); }
  .combo-card.adv .combo-level { color: var(--adv); }
  .combo-stack { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; flex: 1; }
  .combo-tool { font-family: 'IBM Plex Mono', monospace; font-size: 11px; font-weight: 500; padding: 3px 10px; border-radius: var(--radius-sm); background: var(--surface2); color: var(--text); border: 1px solid var(--border); }
  .combo-plus { font-size: 12px; color: var(--faint); font-weight: 300; }
  .combo-cost { font-family: 'IBM Plex Mono', monospace; font-size: 10px; color: var(--muted); white-space: nowrap; padding-top: 4px; }

  .stack-cta { margin-top: 3rem; padding: 2rem; background: var(--surface); border: 1px solid var(--accent-dim); border-radius: var(--radius); text-align: center; }
  .stack-cta h3 { font-family: 'Syne', sans-serif; font-size: 1.25rem; font-weight: 800; margin-bottom: 0.5rem; }
  .stack-cta p { font-size: 13px; color: var(--muted); font-weight: 300; max-width: 440px; margin: 0 auto 1.25rem; }
  .stack-cta a { display: inline-block; font-family: 'Syne', sans-serif; font-weight: 700; font-size: 14px; background: var(--accent); color: var(--bg); text-decoration: none; padding: 13px 26px; border-radius: var(--radius-sm); }

  .hidden { display: none !important; }

  /* ── Mobile ─────────────────────────────────────────────── */
  @media (max-width: 640px) {
    .sticky-header { padding: 0 1rem; }
    .sticky-inner { height: auto; padding: 8px 0; flex-direction: column; align-items: stretch; gap: 8px; }
    .sticky-brand { font-size: 12px; }
    .level-tabs { overflow-x: auto; -webkit-overflow-scrolling: touch; padding-bottom: 2px; }
    .level-tab { white-space: nowrap; padding: 6px 12px; }
    .container { padding: 2rem 1.15rem 3.5rem; }
    .tool-grid { grid-template-columns: 1fr; }
    .level-header { flex-wrap: wrap; }
    .level-who { margin-left: 0; flex-basis: 100%; }
    .combo-card { flex-wrap: wrap; gap: 8px; }
    .combo-cost { white-space: normal; }
    .stack-cta { padding: 1.5rem; }
  }
</style>
@endpush

<div class="sticky-header">
  <div class="sticky-inner">
    <div class="sticky-brand">TAAB · Tool Stack Guide</div>
    <div class="level-tabs">
      <button class="level-tab active-all" onclick="filterLevel('all', this)">All levels</button>
      <button class="level-tab" onclick="filterLevel('beg', this)">Beginner</button>
      <button class="level-tab" onclick="filterLevel('int', this)">Intermediate</button>
      <button class="level-tab" onclick="filterLevel('adv', this)">Advanced</button>
    </div>
  </div>
</div>

<div class="container">

  <div class="hero">
    <div class="badge">// taab-tool-stack-guide-2026</div>
    <h1>The right tools for<br>where you actually are</h1>
    <p class="hero-sub">This is the stack we use in the Accelerator. You can start almost entirely free — the only real recurring cost is one cheap domain (~$10/yr). The paid tools are optional, for when you're already earning.</p>
  </div>

  <!-- BEGINNER -->
  <div class="level-section" data-level="beg" id="sec-beg">
    <div class="level-header beg">
      <span class="level-badge">Beginner</span>
      <span class="level-title">Your first working automations</span>
      <span class="level-who">No coding required · Free to low-cost</span>
    </div>
    <div class="tool-grid">
      <div class="tool-card"><div class="tool-card-top"><div><div class="tool-name">n8n (cloud)</div><div class="tool-category">Workflow automation</div></div><div class="cost-badge cost-freemium">Freemium</div></div><div class="tool-desc">Visual workflow builder. Connect apps, run logic, trigger on events. The backbone tool of modern AI automation — learn this first.</div><div class="tool-tags"><span class="tag">Visual builder</span><span class="tag">Self-hostable</span><span class="tag">API-ready</span></div><div class="tool-cost-detail"><strong>Free tier:</strong> 5 workflows, 2,500 executions/mo · <strong>Starter:</strong> $20/mo</div></div>
      <div class="tool-card"><div class="tool-card-top"><div><div class="tool-name">Telegram (+ free bot)</div><div class="tool-category">Messaging / first AI agent</div></div><div class="cost-badge cost-free">Free</div></div><div class="tool-desc">Your first automation channel and the home of your first AI agent. A free bot, instant webhooks, no business verification — the fastest way to ship something that talks back.</div><div class="tool-tags"><span class="tag">Free bot API</span><span class="tag">Instant setup</span><span class="tag">AI agent</span></div><div class="tool-cost-detail"><strong>Free</strong> — no card, no verification</div></div>
      <div class="tool-card"><div class="tool-card-top"><div><div class="tool-name">Gemini API (Google)</div><div class="tool-category">AI brain</div></div><div class="cost-badge cost-free">Free tier</div></div><div class="tool-desc">The AI brain of your first automations. A generous free tier — call it from n8n via an HTTP request node to draft replies, qualify leads, and summarise. Start here before paying for any AI.</div><div class="tool-tags"><span class="tag">Free tier</span><span class="tag">Fast</span><span class="tag">n8n-ready</span></div><div class="tool-cost-detail"><strong>Free tier</strong> covers most beginner workloads</div></div>
      <div class="tool-card"><div class="tool-card-top"><div><div class="tool-name">Airtable</div><div class="tool-category">Database / CRM</div></div><div class="cost-badge cost-freemium">Freemium</div></div><div class="tool-desc">Spreadsheet meets database. Store leads, contacts, workflow records. Your go-to data layer for simple client automations — most clients already get it.</div><div class="tool-tags"><span class="tag">No-code database</span><span class="tag">API-ready</span><span class="tag">Forms</span></div><div class="tool-cost-detail"><strong>Free:</strong> 5 bases, 1,000 rows · <strong>Plus:</strong> $10/seat/mo</div></div>
      <div class="tool-card"><div class="tool-card-top"><div><div class="tool-name">Tally Forms</div><div class="tool-category">Form builder</div></div><div class="cost-badge cost-free">Free</div></div><div class="tool-desc">Beautiful, free forms that trigger automations. Use as your intake layer — client submits a form, n8n fires. Replaces Typeform for most basic use cases.</div><div class="tool-tags"><span class="tag">Webhook triggers</span><span class="tag">Notion sync</span></div><div class="tool-cost-detail"><strong>Free forever</strong> for most features · Pro: $29/mo</div></div>
      <div class="tool-card"><div class="tool-card-top"><div><div class="tool-name">Gmail + Google Sheets</div><div class="tool-category">Comms + data</div></div><div class="cost-badge cost-free">Free</div></div><div class="tool-desc">Your first automation data pipeline. n8n → Google Sheets is the "hello world" of AI automation. Every client you will ever work with uses email.</div><div class="tool-tags"><span class="tag">Beginner-safe</span><span class="tag">Universal</span></div><div class="tool-cost-detail"><strong>Free</strong> with a Google account</div></div>
    </div>
  </div>

  <!-- INTERMEDIATE -->
  <div class="level-section" data-level="int" id="sec-int">
    <div class="level-header int">
      <span class="level-badge">Intermediate</span>
      <span class="level-title">Charging real money for real solutions</span>
      <span class="level-who">Low-code · runs on free tiers · still near-₦0 to start</span>
    </div>
    <div class="tool-grid">
      <div class="tool-card"><div class="tool-card-top"><div><div class="tool-name">OpenAI API</div><div class="tool-category">AI layer</div></div><div class="cost-badge cost-paid">Pay-per-use</div></div><div class="tool-desc">The AI brain of your automations. GPT-4o for general intelligence, GPT-3.5 Turbo for cheap high-volume tasks. Call it from n8n via HTTP request node.</div><div class="tool-tags"><span class="tag">GPT-4o</span><span class="tag">Function calling</span><span class="tag">Embeddings</span></div><div class="tool-cost-detail"><strong>GPT-4o:</strong> ~$0.005/1k tokens · Budget $10–30/mo to start</div></div>
      <div class="tool-card"><div class="tool-card-top"><div><div class="tool-name">Claude API (Anthropic)</div><div class="tool-category">AI layer</div></div><div class="cost-badge cost-paid">Pay-per-use</div></div><div class="tool-desc">Better than GPT for long documents, nuanced instructions, and complex reasoning tasks. Claude Haiku is extremely cheap for high-volume automations.</div><div class="tool-tags"><span class="tag">Long context</span><span class="tag">Structured output</span><span class="tag">Claude Haiku</span></div><div class="tool-cost-detail"><strong>Haiku:</strong> ~$0.00025/1k tokens · Sonnet: $0.003/1k tokens</div></div>
      <div class="tool-card"><div class="tool-card-top"><div><div class="tool-name">n8n (self-hosted)</div><div class="tool-category">Workflow automation</div></div><div class="cost-badge cost-paid">~$6–10/mo</div></div><div class="tool-desc">Host n8n on a VPS (Railway, DigitalOcean, Render). Unlimited workflows, no execution limits. This is the setup for production client work.</div><div class="tool-tags"><span class="tag">Production-ready</span><span class="tag">Unlimited execs</span><span class="tag">Docker</span></div><div class="tool-cost-detail"><strong>n8n is free</strong> — you pay for the server only (~$6/mo on Railway)</div></div>
      <div class="tool-card"><div class="tool-card-top"><div><div class="tool-name">WhatsApp Business API</div><div class="tool-category">Messaging</div></div><div class="cost-badge cost-paid">Variable</div></div><div class="tool-desc">The highest-demand channel in Nigeria. Use Meta's official API via a BSP (Twilio, 360dialog, Interakt). Required for any WhatsApp AI chatbot project.</div><div class="tool-tags"><span class="tag">High demand</span><span class="tag">Nigeria-relevant</span><span class="tag">Business verification</span></div><div class="tool-cost-detail"><strong>Free tier:</strong> 1k conversations/mo (meta) · BSP fee varies ($15–50/mo)</div></div>
      <div class="tool-card"><div class="tool-card-top"><div><div class="tool-name">Cal.com</div><div class="tool-category">Scheduling / booking</div></div><div class="cost-badge cost-freemium">Freemium</div></div><div class="tool-desc">Calendly alternative, open source. Webhook-ready booking automation. Excellent for building appointment systems connected to AI follow-up sequences.</div><div class="tool-tags"><span class="tag">Webhook triggers</span><span class="tag">v2 API</span><span class="tag">Open source</span></div><div class="tool-cost-detail"><strong>Free:</strong> unlimited bookings · Teams: $15/mo</div></div>
      <div class="tool-card"><div class="tool-card-top"><div><div class="tool-name">Notion API</div><div class="tool-category">Knowledge base / CRM</div></div><div class="cost-badge cost-freemium">Freemium</div></div><div class="tool-desc">Great for building lightweight client-facing systems. Use Notion as a knowledge base for AI chatbots, or as a CRM backend for SME clients who already use Notion.</div><div class="tool-tags"><span class="tag">DB API</span><span class="tag">Client-friendly</span><span class="tag">RAG-ready</span></div><div class="tool-cost-detail"><strong>Free tier</strong> has full API access · Plus: $10/user/mo</div></div>
      <div class="tool-card"><div class="tool-card-top"><div><div class="tool-name">Brevo (Sendinblue)</div><div class="tool-category">Email automation</div></div><div class="cost-badge cost-freemium">Freemium</div></div><div class="tool-desc">Send transactional and marketing emails via API. Build timed email sequences from n8n (confirmation → 1hr reminder → follow-up). Cheaper than Mailchimp.</div><div class="tool-tags"><span class="tag">Transactional</span><span class="tag">Sequences</span><span class="tag">SMTP</span></div><div class="tool-cost-detail"><strong>Free:</strong> 300 emails/day · Starter: $25/mo (20k sends)</div></div>
      <div class="tool-card"><div class="tool-card-top"><div><div class="tool-name">Vapi</div><div class="tool-category">AI voice calls</div></div><div class="cost-badge cost-paid">Pay-per-minute</div></div><div class="tool-desc">Build AI phone agents that can qualify leads, take bookings, answer FAQs. High-demand, high-ticket service. Steep learning curve but lucrative.</div><div class="tool-tags"><span class="tag">Voice AI</span><span class="tag">Outbound/inbound</span><span class="tag">High ticket</span></div><div class="tool-cost-detail"><strong>~$0.05–0.10/min</strong> all-in · Budget $20–50/mo for testing</div></div>
    </div>
  </div>

  <!-- ADVANCED -->
  <div class="level-section" data-level="adv" id="sec-adv">
    <div class="level-header adv">
      <span class="level-badge">Advanced</span>
      <span class="level-title">Enterprise-grade, high-ticket work</span>
      <span class="level-who">Self-hosted on free tiers · only real cost: a domain (~$10/yr)</span>
    </div>
    <div class="tool-grid">
      <div class="tool-card"><div class="tool-card-top"><div><div class="tool-name">Pinecone / Qdrant</div><div class="tool-category">Vector database</div></div><div class="cost-badge cost-freemium">Freemium</div></div><div class="tool-desc">The memory layer for AI that knows your client's data. Powers RAG (Retrieval-Augmented Generation) — chatbots that answer questions from documents, not just training data.</div><div class="tool-tags"><span class="tag">RAG</span><span class="tag">Embeddings</span><span class="tag">Semantic search</span></div><div class="tool-cost-detail"><strong>Pinecone free:</strong> 1 index, 100k vectors · Qdrant: self-hostable free</div></div>
      <div class="tool-card"><div class="tool-card-top"><div><div class="tool-name">LangChain / LlamaIndex</div><div class="tool-category">AI orchestration</div></div><div class="cost-badge cost-free">Open source</div></div><div class="tool-desc">Python/JS frameworks for building complex AI pipelines — agents, multi-step reasoning, document QA, memory management. Required for serious custom AI products.</div><div class="tool-tags"><span class="tag">Agents</span><span class="tag">RAG pipelines</span><span class="tag">Python/JS</span></div><div class="tool-cost-detail"><strong>Free and open source</strong> — you pay only for the AI API calls</div></div>
      <div class="tool-card"><div class="tool-card-top"><div><div class="tool-name">Supabase</div><div class="tool-category">Backend / database</div></div><div class="cost-badge cost-freemium">Freemium</div></div><div class="tool-desc">Open-source Firebase alternative. PostgreSQL with built-in auth, storage, edge functions, and vector search. Your production backend for custom AI apps.</div><div class="tool-tags"><span class="tag">PostgreSQL</span><span class="tag">pgvector</span><span class="tag">Auth</span><span class="tag">Storage</span></div><div class="tool-cost-detail"><strong>Free:</strong> 500MB DB, 1GB storage · Pro: $25/mo</div></div>
      <div class="tool-card"><div class="tool-card-top"><div><div class="tool-name">Flowise / Dify</div><div class="tool-category">AI agent builder</div></div><div class="cost-badge cost-free">Open source</div></div><div class="tool-desc">Visual builders for AI agents, RAG pipelines, and chatbots. Flowise sits between n8n (automation) and LangChain (pure code) — good for agent-focused projects.</div><div class="tool-tags"><span class="tag">AI agents</span><span class="tag">Self-hostable</span><span class="tag">RAG builder</span></div><div class="tool-cost-detail"><strong>Free self-hosted</strong> · Flowise Cloud: $35/mo</div></div>
      <div class="tool-card"><div class="tool-card-top"><div><div class="tool-name">Twilio</div><div class="tool-category">Comms API</div></div><div class="cost-badge cost-paid">Pay-per-use</div></div><div class="tool-desc">SMS, WhatsApp, voice, email via API. Programmable comms for enterprise-grade solutions. More reliable than simpler BSPs for high-volume production workloads.</div><div class="tool-tags"><span class="tag">SMS</span><span class="tag">WhatsApp</span><span class="tag">Voice</span><span class="tag">Email</span></div><div class="tool-cost-detail"><strong>SMS:</strong> ~₦8/msg · <strong>WhatsApp:</strong> ~$0.005/msg + BSP fee</div></div>
      <div class="tool-card"><div class="tool-card-top"><div><div class="tool-name">Railway / Render / Fly.io</div><div class="tool-category">Hosting / infra</div></div><div class="cost-badge cost-paid">$5–20/mo</div></div><div class="tool-desc">Deploy n8n, Flowise, custom APIs, and full-stack apps. Railway is the easiest for Docker-based deployments. Render has a generous free tier. Fly.io for edge.</div><div class="tool-tags"><span class="tag">Docker</span><span class="tag">CI/CD</span><span class="tag">Custom domains</span></div><div class="tool-cost-detail"><strong>Railway:</strong> $5/mo min · <strong>Render:</strong> free tier available</div></div>
    </div>
  </div>

  <!-- Recommended stacks -->
  <div class="combos-section">
    <div class="combos-title">Recommended stacks by use case</div>
    <div class="combo-card beg"><div class="combo-level">Beginner</div><div class="combo-stack"><span class="combo-tool">n8n</span><span class="combo-plus">+</span><span class="combo-tool">Gemini (free)</span><span class="combo-plus">+</span><span class="combo-tool">Airtable</span><span class="combo-plus">+</span><span class="combo-tool">Tally Forms</span></div><div class="combo-cost">₦0/mo on free tiers · Lead capture + AI email drafting</div></div>
    <div class="combo-card int"><div class="combo-level">Intermediate</div><div class="combo-stack"><span class="combo-tool">n8n (self-hosted)</span><span class="combo-plus">+</span><span class="combo-tool">Gemini</span><span class="combo-plus">+</span><span class="combo-tool">WhatsApp API</span><span class="combo-plus">+</span><span class="combo-tool">Airtable</span></div><div class="combo-cost">₦0/mo self-hosted · WhatsApp AI lead qualifier (highest demand in Nigeria)</div></div>
    <div class="combo-card int"><div class="combo-level">Intermediate</div><div class="combo-stack"><span class="combo-tool">n8n (self-hosted)</span><span class="combo-plus">+</span><span class="combo-tool">Telegram</span><span class="combo-plus">+</span><span class="combo-tool">Gemini</span><span class="combo-plus">+</span><span class="combo-tool">Slack</span></div><div class="combo-cost">₦0/mo on free tiers · Lead bot + team alerts</div></div>
    <div class="combo-card adv"><div class="combo-level">Advanced</div><div class="combo-stack"><span class="combo-tool">n8n (self-hosted)</span><span class="combo-plus">+</span><span class="combo-tool">Pinecone</span><span class="combo-plus">+</span><span class="combo-tool">Google Cloud</span><span class="combo-plus">+</span><span class="combo-tool">Vapi</span><span class="combo-plus">+</span><span class="combo-tool">Gemini</span></div><div class="combo-cost">Free self-hosted — pay only for AI/voice usage · RAG + voice receptionist</div></div>
  </div>

  <!-- CTA -->
  <div class="stack-cta">
    <h3>Don't just collect tools — learn to ship with them.</h3>
    <p>The AI Automation Accelerator walks you from your first workflow to a paying client stack, hands-on.</p>
    <a href="{{ config('taab.accelerator_url') }}">Explore the Accelerator →</a>
  </div>

</div>

@push('scripts')
<script>
function filterLevel(level, el) {
  document.querySelectorAll('.level-tab').forEach(t => t.className = 'level-tab');
  if (level === 'all') {
    el.classList.add('active-all');
    document.querySelectorAll('.level-section').forEach(s => s.classList.remove('hidden'));
  } else {
    el.classList.add('active-' + level);
    document.querySelectorAll('.level-section').forEach(s => s.classList.toggle('hidden', s.dataset.level !== level));
  }
}
</script>
@endpush
</x-layouts.taab>
