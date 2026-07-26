<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AJ Thompson — AI Automation Engineer</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
        @media print {
            @page { margin: 0.9cm; }
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .avoid-break { page-break-inside: avoid; }
        }
    </style>
</head>
<body class="bg-gray-100 text-gray-800 antialiased print:bg-white">

    <!-- Print Button (Hidden on Print) -->
    <div class="fixed bottom-8 right-8 print:hidden">
        <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-full shadow-lg transition-colors">
            Print to PDF
        </button>
    </div>

    <!-- ATS-friendly: single column, plain-text contact line, standard section headings -->
    <main class="max-w-[21cm] mx-auto bg-white px-10 py-9 md:px-12 shadow-xl my-10 print:m-0 print:p-0 print:shadow-none print:w-full print:max-w-full text-[13.5px] leading-relaxed">

        <!-- Header -->
        <header class="border-b-2 border-gray-800 pb-4 mb-5">
            <h1 class="text-3xl font-bold text-gray-900 tracking-tight">AJ Thompson</h1>
            <h2 class="text-lg font-semibold text-blue-700 mt-0.5 mb-2">AI Automation Engineer</h2>
            <p class="text-gray-700">
                Abuja, Nigeria &middot; Remote — overlaps US business hours
            </p>
            <p class="text-gray-700">
                <a href="mailto:tommyriode@gmail.com" class="hover:text-blue-700">tommyriode@gmail.com</a>
                &middot; <a href="https://github.com/thompsonokpegu" target="_blank" class="hover:text-blue-700">github.com/thompsonokpegu</a>
                &middot; <a href="https://linkedin.com/in/thompsonokpegu" target="_blank" class="hover:text-blue-700">linkedin.com/in/thompsonokpegu</a>
                &middot; <a href="https://ajbuildai.com" target="_blank" class="hover:text-blue-700">ajbuildai.com</a>
            </p>
        </header>

        <!-- Summary -->
        <section class="mb-5 avoid-break">
            <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider border-b border-gray-300 pb-1 mb-2">Summary</h3>
            <p class="text-gray-700">
                AI Automation Engineer with 7+ years of backend engineering (PHP/Laravel) who now designs, builds, and operates production AI automation systems end to end: self-hosted n8n orchestration, LLM-powered chat agents on WhatsApp and Telegram with human-in-the-loop takeover, RAG-grounded knowledge bases, and AI voice receptionists (Vapi/ElevenLabs). Comfortable owning the whole stack — webhook architecture with idempotency and rate control, payment and billing flows, server provisioning on Google Cloud, and CI/CD — not just the automation layer. Currently delivering AI appointment setters and client portals for service businesses, and teaching a cohort program where every workflow I teach is one I've shipped.
            </p>
        </section>

        <!-- Skills -->
        <section class="mb-5 avoid-break">
            <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider border-b border-gray-300 pb-1 mb-2">Skills</h3>
            <div class="space-y-1 text-gray-700">
                <p><span class="font-semibold text-gray-900">AI &amp; Automation:</span> n8n (self-hosted &amp; cloud), workflow orchestration, LLM integration &amp; prompt engineering (Gemini, OpenAI), function calling / tool use, AI agents (Telegram Bot API, Meta WhatsApp Cloud API, Twilio WhatsApp), human-in-the-loop takeover &amp; escalation, RAG / vector search (Pinecone) &amp; grounded knowledge bases, voice AI (Vapi, ElevenLabs), webhook design (idempotent sends, throttling, retries, server-side verification)</p>
                <p><span class="font-semibold text-gray-900">Backend Engineering:</span> PHP, Laravel (incl. Laravel 12), Livewire/Volt, WordPress/WooCommerce, REST APIs, real-time WebSockets (Laravel Reverb), PostgreSQL, MySQL, MongoDB, queued &amp; scheduled jobs, MVC architecture, Pest/PHPUnit testing</p>
                <p><span class="font-semibold text-gray-900">Payments &amp; Integrations:</span> Paystack (checkout, webhooks, installment billing, signed payment links), Slack (Bolt, interactive messages), Twilio, Google Workspace APIs (Gmail, Sheets, Drive, Calendar), third-party API integration</p>
                <p><span class="font-semibold text-gray-900">Infrastructure &amp; DevOps:</span> Google Cloud (VM provisioning, self-hosting, DNS/SSL), AWS, Linux, Docker, Git/GitHub, GitHub Actions CI/CD</p>
                <p><span class="font-semibold text-gray-900">Frontend:</span> JavaScript, Alpine.js, Tailwind CSS, responsive/mobile-first UI, HTML email (Outlook-safe)</p>
            </div>
        </section>

        <!-- Experience -->
        <section class="mb-5">
            <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider border-b border-gray-300 pb-1 mb-3">Experience</h3>

            <!-- Repetigo -->
            <div class="mb-4 avoid-break">
                <div class="flex flex-col sm:flex-row justify-between items-baseline">
                    <h4 class="text-[15px] font-bold text-gray-900">Founder &amp; AI Automation Engineer <span class="text-gray-500 font-normal">| Repetigo (remote)</span></h4>
                    <span class="text-gray-600 font-medium whitespace-nowrap">2025 – Present</span>
                </div>
                <ul class="list-disc list-outside ml-4 text-gray-700 space-y-1 mt-1.5">
                    <li>Shipped Veta, a production WhatsApp AI lead qualifier for an elevator company in Lagos: multi-turn AI pre-qualification with Slack handoff, dual-path human takeover, and grounded pricing from a versioned knowledge base (detailed under Projects).</li>
                    <li>Design and deploy AI appointment setters for dental clinics and medical spas: Vapi voice agents backed by self-hosted n8n workflows handling qualification, booking, and follow-up.</li>
                    <li>Build branded, AI-powered client portals in Laravel/Livewire (e.g., a project-tracking portal for an elevator installation company) with automated email notification pipelines.</li>
                    <li>Provision and operate self-hosted n8n instances on Google Cloud — VM setup, domain/SSL, upgrades, and backups — cutting clients' automation hosting costs to near zero.</li>
                    <li>Run targeted Google Ads lead-generation campaigns to acquire clients and validate productized automation offers.</li>
                </ul>
            </div>

            <!-- AJBuilds AI -->
            <div class="mb-4 avoid-break">
                <div class="flex flex-col sm:flex-row justify-between items-baseline">
                    <h4 class="text-[15px] font-bold text-gray-900">Creator &amp; Lead Instructor <span class="text-gray-500 font-normal">| AJBuilds AI — AI Automation Accelerator</span></h4>
                    <span class="text-gray-600 font-medium whitespace-nowrap">2026 – Present</span>
                </div>
                <ul class="list-disc list-outside ml-4 text-gray-700 space-y-1 mt-1.5">
                    <li>Created a 6-week cohort program teaching 9 production AI automations — Telegram lead-capture agents, RAG knowledge bases, official WhatsApp bots, and AI voice receptionists — every one built and shipped by me first.</li>
                    <li>Engineered the course platform itself: a Laravel/Livewire LMS with Paystack checkout (full and installment plans with signed payment links and server-side verification), ship-to-unlock module gating, seat caps with waitlists, and an admin dashboard with CSV exports.</li>
                    <li>Automated the full marketing funnel with n8n: registration webhooks drive reminder and follow-up sequences with idempotent, throttled sends; hourly scheduled jobs keep messaging in sync with session state.</li>
                    <li>Set up zero-touch CI/CD with GitHub Actions deploying over SSH, including automatic recovery from drifted server state.</li>
                    <li>Mentor non-technical students through self-hosting and managing their own n8n instances on Google Cloud. [TODO: cohort size / masterclass registrations / waitlist numbers]</li>
                </ul>
            </div>

            <!-- Deepr -->
            <div class="mb-4 avoid-break">
                <div class="flex flex-col sm:flex-row justify-between items-baseline">
                    <h4 class="text-[15px] font-bold text-gray-900">Full-Stack Developer <span class="text-gray-500 font-normal">| Deepr Ecommerce</span></h4>
                    <span class="text-gray-600 font-medium whitespace-nowrap">2022 – 2026</span>
                </div>
                <ul class="list-disc list-outside ml-4 text-gray-700 space-y-1 mt-1.5">
                    <li>Built online stores and custom web applications in PHP and Laravel for SME clients, from data model to deployment.</li>
                    <li>Integrated payment gateways, CDN services, and other third-party APIs to extend platform functionality.</li>
                </ul>
            </div>

            <!-- Sainte Apparel -->
            <div class="mb-2 avoid-break">
                <div class="flex flex-col sm:flex-row justify-between items-baseline">
                    <h4 class="text-[15px] font-bold text-gray-900">Backend Developer <span class="text-gray-500 font-normal">| Sainte Apparel LLC (remote)</span></h4>
                    <span class="text-gray-600 font-medium whitespace-nowrap">2020 – 2026</span>
                </div>
                <ul class="list-disc list-outside ml-4 text-gray-700 space-y-1 mt-1.5">
                    <li>Owned ongoing backend development for an apparel brand, building custom PHP plugins against strict business requirements.</li>
                    <li>Audited and improved web infrastructure — site speed, user experience, and disaster-recovery protocols.</li>
                </ul>
            </div>
        </section>

        <!-- Projects -->
        <section class="mb-5 avoid-break">
            <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider border-b border-gray-300 pb-1 mb-2">Selected Projects</h3>
            <div class="space-y-2 text-gray-700">
                <p>
                    <span class="font-bold text-gray-900">Veta — WhatsApp AI Lead Qualifier <span class="font-normal text-gray-500">(production · elevator company, Lagos)</span>:</span>
                    End-to-end WhatsApp agent that pre-qualifies inbound leads over 5–10 messages, captures structured qualification data, and posts an AI-generated handoff summary to Slack for sales reps to claim. Dual-path human takeover — Slack "Claim" button or admin workspace — pauses the AI mid-conversation and hands the thread to a rep, with a "Resume AI" hand-back. Pricing answers grounded in a versioned knowledge base (no hallucinated quotes), business-hours-aware escalation, cycle-back nurture flows, and an analytics dashboard covering the lead funnel, response-time SLAs, and AI conversation quality. Stack: Laravel 12, Livewire, Twilio, n8n, Gemini, Slack Bolt, Reverb, Postgres.
                </p>
                <p>
                    <span class="font-bold text-gray-900">AI Shopping Concierge — Custom WooCommerce Chat Assistant <span class="font-normal text-gray-500">(luxury fashion store)</span>:</span>
                    Bespoke, plugin-free AI assistant integrating the Google Gemini API directly into WordPress/WooCommerce. Function calling (tool use) lets the model query the live product catalog and recommend real, in-stock items with links via a two-pass flow (model → catalog search → grounded response), with a brand-tuned system prompt covering voice, sizing, shipping, and returns policies. Full-stack widget: vanilla-JS front end (session tracking, FAQ quick-actions, Markdown rendering, WhatsApp handoff) and a secure PHP/AJAX backend with input sanitization and role validation, plus session-grouped conversation logging in the WordPress admin for review and lead capture. Stack: PHP, JavaScript, WordPress, WooCommerce, Gemini API (function calling).
                </p>
                <p>
                    <span class="font-bold text-gray-900">AI Voice Receptionist &amp; Appointment Setter:</span>
                    Inbound voice agent (Vapi + ElevenLabs) that answers calls, qualifies leads, and books appointments via n8n workflows connected to calendar and CRM — built for dental/med-spa front desks.
                </p>
                <p>
                    <span class="font-bold text-gray-900">Telegram AI Agent with RAG Knowledge Base:</span>
                    Conversational lead-capture agent on Telegram with a Pinecone-backed retrieval layer so answers stay grounded in business documents instead of hallucinating; orchestrated in self-hosted n8n with an LLM brain.
                </p>
                <p>
                    <span class="font-bold text-gray-900">Cohort LMS &amp; Payments Platform (ajbuildai.com):</span>
                    Full-funnel Laravel/Livewire platform: marketing pages, lead-magnet tools (ROI calculator, readiness scorecard), Paystack checkout with installment tracking, enrollment gating, n8n-driven email/reminder automation, and GitHub Actions CI/CD.
                </p>
                <p>
                    <span class="font-bold text-gray-900">Multi-Vendor eCommerce MVP:</span>
                    Laravel + Livewire single-page-app marketplace with Laravel Breeze authentication and Paystack integration for payments and seller payouts.
                </p>
            </div>
        </section>

        <!-- Education -->
        <section class="avoid-break">
            <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider border-b border-gray-300 pb-1 mb-2">Education</h3>
            <div class="text-gray-700 space-y-1">
                <p><span class="font-bold text-gray-900">Higher National Diploma, Computer Science</span> — Ken Saro Wiwa Polytechnic, Bori, Nigeria</p>
                <p><span class="font-bold text-gray-900">National Diploma, Computer Science</span> — Ken Saro Wiwa Polytechnic, Bori, Nigeria</p>
            </div>
        </section>

    </main>
</body>
</html>
