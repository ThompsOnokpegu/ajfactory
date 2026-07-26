<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thompson Onokpegu — AI Automation Engineer</title>
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
            <h1 class="text-3xl font-bold text-gray-900 tracking-tight">Thompson Onokpegu</h1>
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
                AI Automation Engineer with 7+ years of backend engineering (PHP/Laravel) building production AI systems: LLM agents on WhatsApp and Telegram with human-in-the-loop takeover, function calling against live business data, RAG-grounded knowledge bases, AI voice receptionists, and self-hosted n8n orchestration. I own the full stack — webhook architecture, payments, real-time UI, cloud infrastructure, CI/CD — and ship systems that businesses run every day.
            </p>
        </section>

        <!-- Skills -->
        <section class="mb-5 avoid-break">
            <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider border-b border-gray-300 pb-1 mb-2">Skills</h3>
            <div class="space-y-1 text-gray-700">
                <p><span class="font-semibold text-gray-900">AI &amp; Automation:</span> LLM agents (Claude, Gemini, OpenAI), function calling / tool use, RAG &amp; vector search (Pinecone), human-in-the-loop takeover, prompt engineering, AI-assisted development (Claude Code), n8n (self-hosted), voice AI (Vapi, ElevenLabs), WhatsApp (Twilio, Meta Cloud API), Telegram Bot API</p>
                <p><span class="font-semibold text-gray-900">Backend:</span> PHP, Laravel, Livewire, WordPress/WooCommerce, REST APIs, WebSockets (Laravel Reverb), PostgreSQL, MySQL, queues &amp; scheduled jobs, Pest/PHPUnit</p>
                <p><span class="font-semibold text-gray-900">Integrations:</span> Slack (Bolt, interactive messages), Twilio, Paystack payments, Google Workspace APIs, webhook design (idempotency, throttling, server-side verification)</p>
                <p><span class="font-semibold text-gray-900">Infrastructure:</span> Google Cloud, AWS, Linux, Docker, GitHub Actions CI/CD, Git, JavaScript/Tailwind CSS front ends</p>
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
                    <li>Shipped Veta, a production WhatsApp AI lead qualifier for an elevator company — multi-turn pre-qualification, AI-generated Slack handoff summaries, dual-path human takeover (see Projects).</li>
                    <li>Design and deploy AI appointment setters for dental clinics and medical spas: Vapi voice agents backed by self-hosted n8n workflows for qualification, booking, and follow-up.</li>
                    <li>Build branded, AI-powered client portals in Laravel/Livewire with automated email notification pipelines.</li>
                    <li>Provision and operate self-hosted n8n on Google Cloud (Docker, DNS/SSL, backups), cutting clients' automation hosting costs to near zero.</li>
                </ul>
            </div>

            <!-- AJBuilds AI -->
            <div class="mb-4 avoid-break">
                <div class="flex flex-col sm:flex-row justify-between items-baseline">
                    <h4 class="text-[15px] font-bold text-gray-900">Creator &amp; Lead Instructor <span class="text-gray-500 font-normal">| AJBuilds AI — AI Automation Accelerator</span></h4>
                    <span class="text-gray-600 font-medium whitespace-nowrap">2026 – Present</span>
                </div>
                <ul class="list-disc list-outside ml-4 text-gray-700 space-y-1 mt-1.5">
                    <li>Created and teach a 6-week cohort program on building 9 production AI automations — Telegram lead-capture agents, RAG knowledge bases, WhatsApp bots, AI voice receptionists — each one shipped by me first.</li>
                    <li>Engineered the platform end to end: Laravel/Livewire LMS with Paystack checkout (installment billing, signed payment links, server-side verification), ship-to-unlock module gating, and an n8n-automated marketing funnel with idempotent, throttled webhook sends.</li>
                    <li>Run zero-touch CI/CD with GitHub Actions deploying over SSH, including automatic recovery from drifted server state.</li>
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
                    <h4 class="text-[15px] font-bold text-gray-900">Backend Developer <span class="text-gray-500 font-normal">| Sainte Apparel LLC — US/Nigerian apparel brand (remote)</span></h4>
                    <span class="text-gray-600 font-medium whitespace-nowrap">2020 – 2026</span>
                </div>
                <ul class="list-disc list-outside ml-4 text-gray-700 space-y-1 mt-1.5">
                    <li>Owned ongoing backend development, building custom PHP plugins against strict business requirements.</li>
                    <li>Audited and improved web infrastructure — site speed, user experience, and disaster-recovery protocols.</li>
                </ul>
            </div>
        </section>

        <!-- Projects -->
        <section class="mb-5 avoid-break">
            <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider border-b border-gray-300 pb-1 mb-2">Selected Projects</h3>
            <div class="space-y-2 text-gray-700">
                <p>
                    <span class="font-bold text-gray-900">Veta — WhatsApp AI Lead Qualifier <span class="font-normal text-gray-500">(production · elevator company)</span>:</span>
                    End-to-end WhatsApp agent that pre-qualifies inbound leads over 5–10 messages, captures structured qualification data, and posts AI-generated handoff summaries to Slack for reps to claim. Dual-path human takeover (Slack or admin workspace) pauses the AI mid-conversation with a "Resume AI" hand-back; pricing grounded in a versioned knowledge base; analytics dashboard for funnel, response-time SLAs, and conversation quality. Laravel 12 &middot; Livewire &middot; Twilio &middot; n8n &middot; Gemini &middot; Slack Bolt &middot; Reverb &middot; Postgres.
                </p>
                <p>
                    <span class="font-bold text-gray-900">AI Shopping Concierge — WooCommerce Chat Assistant <span class="font-normal text-gray-500">(luxury fashion store)</span>:</span>
                    Plugin-free Gemini assistant inside WordPress/WooCommerce using function calling to query the live catalog and recommend real, in-stock products (model → catalog search → grounded response). Brand-tuned system prompt for voice and policies; vanilla-JS widget with WhatsApp handoff; secure PHP/AJAX backend with session-grouped conversation logs for lead capture.
                </p>
                <p>
                    <span class="font-bold text-gray-900">AI Voice Receptionist &amp; Appointment Setter:</span>
                    Inbound Vapi + ElevenLabs voice agent that answers calls, qualifies leads, and books appointments via n8n workflows connected to calendar and CRM — built for dental and med-spa front desks.
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
