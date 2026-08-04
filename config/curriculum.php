<?php

/*
|--------------------------------------------------------------------------
| CURRICULUM — modules now group MULTIPLE videos (3–5 each)
|--------------------------------------------------------------------------
| Module schema:
|   'id'            => unique module slug
|   'title'         => module title (shown as a sidebar group + subtitle)
|   'release_at'    => when the whole module unlocks (Africa/Lagos)
|   'has_blueprint' => bool — shows the Snapshot Vault panel
|   'blueprint_url' => (optional) override the vault download link
|   'description'   => module overview (shown under the player)
|   'attendance_code' => (LIVE sessions only) the code AJ announces at the END of
|                        the live call; students enter it to mark attendance. Set
|                        a FRESH one per session and never type it in chat — it's
|                        server-side only (never sent to the browser). Leave unset
|                        to keep attendance closed for that session.
|   'playbook_url'    => (LIVE sessions only, optional) resource unlocked once the
|                        student marks attendance for that session.
|   'videos'        => [
|        ['id' => 'm02-v1', 'title' => '...', 'video_id' => '<bunny-id>', 'duration' => '08:00', 'library_id' => '<optional>'],
|        ... 3–5 per module ...
|   ]
|   A lesson can instead be a WRITTEN GUIDE: give it 'guide_url' => '/guides/…'
|   and an empty video_id — the dashboard shows an "Open the guide" panel.
|
| Each module below currently carries its original single video as videos[0].
| ADD the remaining videos per module using their real Bunny IDs — do NOT
| invent IDs. A module with no 'videos' key still works (the dashboard wraps
| its top-level video_id into a single video automatically).
|
| Cohort 2 drip: modules unlock each MONDAY from 6 July 2026; live sessions
| each THURSDAY from 9 July 2026. Module 01 = 6 Jul ... Module 09 = 31 Aug.
*/

return [
    /*
    |--------------------------------------------------------------------------
    | CORE CURRICULUM
    |--------------------------------------------------------------------------
    */
    'core' => [
        [
            'id' => 'module-01',
            'title' => 'Module 01: Welcome to the Factory',
            'release_at' => '2026-07-06 00:00:00',
            'has_blueprint' => false,
            'description' => "Welcome to the Accelerator — setup week, then your first builds. We cover:\n\n• Account setup for every tool you'll use: n8n trial, Gemini API key, Google Cloud, Pinecone, Slack, Google OAuth credentials, a Telegram bot, and Vapi.\n• The foundations behind every workflow — APIs, JSON, and webhooks.\n• A guided tour of the n8n canvas.\n• 'Hello World': your first live automation, sending a message through Telegram.\n• Project 1 — The Intake Funnel: capture a lead and fire an automated response, end to end.",
            'videos' => [
                ['id' => 'module-01-v1', 'title' => 'Welcome Briefing', 'video_id' => '0da06b43-61a4-4dd1-b9fa-cb6dd9672f64', 'duration' => '02:00'],
                ['id' => 'module-01-v2', 'title' => 'n8n Trial Setup', 'video_id' => '3883304a-77ed-4ef9-b836-cf81bf444a8d', 'duration' => '02:47'],
                ['id' => 'module-01-v3', 'title' => 'LLM API Key Setup', 'video_id' => 'ac58ea28-d9b5-43af-9b55-bbb4f08e4514', 'duration' => '01:07'],
                ['id' => 'module-01-v4', 'title' => 'GCP Account Setup', 'video_id' => '544f005c-4653-403b-98fc-6d9c2b609aea', 'duration' => '02:00'],
                ['id' => 'module-01-v5', 'title' => 'Pinecone Account Setup', 'video_id' => 'c4160678-72af-4cca-8820-b256ee40346a', 'duration' => '02:26'],
                ['id' => 'module-01-v6', 'title' => 'Slack & App Setup', 'video_id' => 'a2b10aaf-ed66-4140-b0c5-38149e33363d', 'duration' => '06:11'],
                ['id' => 'module-01-v7', 'title' => 'OAuth Credentials Setup', 'video_id' => 'f4423e5d-6fc8-433f-a30b-cbf86c2897ea', 'duration' => '09:59'],
                ['id' => 'module-01-v8', 'title' => 'Telegram Bot Setup', 'video_id' => 'd155cb39-de71-4eb4-927b-4f672302444e', 'duration' => '01:53'],
                ['id' => 'module-01-v9', 'title' => 'Vapi Orchestrator Setup', 'video_id' => '1e7d1ebe-8980-4b75-ad23-24b9b6b3499f', 'duration' => '02:15'],
                ['id' => 'module-01-v10', 'title' => 'Hello World - Telegram Automation', 'video_id' => '721a09c1-61b4-4ad7-b3b8-75eeb4f190ef', 'duration' => '07:51'],
                ['id' => 'module-01-v11', 'title' => 'Concepts: API, JSON, Webhook', 'video_id' => '6ba1c683-311f-42e1-ba72-d112e06c52e3', 'duration' => '03:18'],
                ['id' => 'module-01-v12', 'title' => 'The n8n Canvas (quick tour)', 'video_id' => '1f406040-4018-4391-bd69-1f1c995cc458', 'duration' => '13:40'],
                ['id' => 'module-01-v13', 'title' => 'Project 1: Intake Funnel', 'video_id' => 'e86158df-989d-465b-902d-45b545dbd5e4', 'duration' => '18:30'],
                // Add further onboarding videos here →
            ],
        ],
        [
            'id' => 'module-02',
            'title' => 'Module 02: Basics of n8n',
            'release_at' => '2026-07-13 00:00:00',
            'has_blueprint' => true,
            'description' => "Learn to shape data in n8n, then put it to work. We cover:\n\n• Shaping data — restructuring and transforming items so each node gets exactly the input it needs.\n• Project 2: The Automated Archivist (basic) — sort incoming files and file them away automatically.\n• Project 2: The Automated Archivist (AI) — add an AI step so it classifies and handles messier, real-world input.\n\nThe original full-length 'Basics of n8n' walkthrough is included as a legacy reference.",
            'videos' => [
                ['id' => 'module-02-guide', 'title' => 'Self-Hosting n8n on Google Cloud', 'video_id' => '', 'duration' => 'Guide', 'guide_url' => '/guides/n8n-on-google-cloud', 'description' => "Your own n8n on Google Cloud's free tier — domain, server, real HTTPS, the works. A copy-paste, nothing-skipped written guide you follow at your own pace. Click \"Open the guide\" to start."],
                ['id' => 'module-02-v1', 'title' => 'Concept:Shaping Data in n8n', 'video_id' => '9630a7ee-2b0f-4d9b-9479-d8e4dfdc975f', 'duration' => '03:40 Mins'],
                ['id' => 'module-02-v2', 'title' => 'Project 2: Automated Archivist - Basic', 'video_id' => 'd2df4aa2-844a-41ff-a36f-82679f119600', 'duration' => '16:49 Mins'],
                ['id' => 'module-02-v3', 'title' => 'Project 2: Automated Archivist - AI', 'video_id' => '69b3d1b4-9c7e-40cc-b54e-52309ba42d90', 'duration' => '15:27 Mins'],
                ['id' => 'module-02-v4', 'title' => 'LEGACY: Basics of n8n', 'video_id' => '03bff2b2-1135-4089-b8dc-c095903c87e0', 'duration' => '26:17 Mins'],
                // Split the topics above into individual videos here →
                // ['id' => 'module-02-v2', 'title' => 'The Interface Tour', 'video_id' => '<bunny-id>', 'duration' => '08:00'],
            ],
        ],
        [
            'id' => 'module-03',
            'title' => 'Module 03: API Calls With n8n',
            'release_at' => '2026-07-20 00:00:00',
            'has_blueprint' => true,
            'description' => "Mastering the most important skill in automation: APIs. This module covers:\n\n• API Concepts for Non-Coders (The Waiter Analogy).\n• Using the HTTP Request Node to fetch live data (Crypto Prices).\n• Handling Authentication and API Keys to access secure data (NewsAPI, Paystack API).",
            'videos' => [
                ['id' => 'module-03-v1', 'title' => 'API Calls With n8n', 'video_id' => 'e895c6bf-ff14-46f4-87b1-d62817bfb401', 'duration' => '28:38 Mins'],
            ],
        ],
        [
            'id' => 'module-04',
            'title' => 'Module 04: Knowledge Base (RAG)',
            'release_at' => '2026-07-27 00:00:00',
            'has_blueprint' => true,
            'description' => "Teaching the AI about your specific business data. We cover:\n\n• Understanding Retrieval-Augmented Generation (RAG).\n• Setting up a Pinecone Vector Database with Gemini Dimensions (768).\n• Building an Ingestion Pipeline to read PDFs and save them as vectors.",
            'videos' => [
                ['id' => 'module-04-v1', 'title' => 'Knowledge Base (RAG)', 'video_id' => '01246ff7-d654-46c3-8c30-93ab2d894ff3', 'duration' => '19:33 Mins'],
            ],
        ],
        [
            'id' => 'module-05',
            'title' => 'Module 05: WhatsApp Automation',
            'release_at' => '2026-08-03 00:00:00',
            'has_blueprint' => true,
            'description' => "Building a production-ready WhatsApp bot using Twilio. Topics include:\n\n• Buying a Real Number & Setting up a Sender Profile.\n• Understanding the Meta 24-Hour Session Window & Templates.\n• Configuring Production Webhooks to handle live traffic.",
            'videos' => [
                ['id' => 'module-05-v1', 'title' => 'WhatsApp Automation', 'video_id' => 'e3c1e139-b0d1-4c84-aa17-97c61710b526', 'duration' => '45:00'],
            ],
        ],
        [
            'id' => 'module-06',
            'title' => 'Module 06: AI Voice Support Agent',
            'release_at' => '2026-08-10 00:00:00',
            'has_blueprint' => true,
            'description' => "Creating 'Tola', a real-time voice receptionist. This module covers:\n\n• Introduction to Voice AI latency and the Vapi Orchestrator.\n• Configuring the Voice, Transcriber, and System Prompt.\n• Using Function Calling to connect Vapi to n8n for real-time data lookups.",
            'videos' => [
                ['id' => 'module-06-v1', 'title' => 'AI Voice Support Agent', 'video_id' => 'ad4ae58d-9faf-453d-8fa9-45df3d78d1a0', 'duration' => '40:00'],
            ],
        ],
        [
            'id' => 'module-07',
            'title' => 'Module 07: AI Chat Support Agent',
            'release_at' => '2026-08-17 00:00:00',
            'has_blueprint' => true,
            'description' => "Bringing it all together into a smart Chat Agent. We cover:\n\n• Using the AI Agent Node with Window Buffer Memory.\n• Connecting your Pinecone Knowledge Base as a 'Tool'.\n• Logic for handling Fallbacks and Human Handoffs via Email.",
            'videos' => [
                ['id' => 'module-07-v1', 'title' => 'AI Chat Support Agent', 'video_id' => '78096f8a-8945-4052-8d4d-67ede32e4e81', 'duration' => '40:00'],
            ],
        ],
        [
            'id' => 'module-08',
            'title' => 'Module 08: Deploy Your Automation',
            'release_at' => '2026-08-24 00:00:00',
            'has_blueprint' => true,
            'description' => "Taking your bots from 'Student Project' to 'Business Asset'. Topics:\n\n• Building a Global Error Handling workflow (The Safety Net).\n• The critical difference between Test URLs and Production Webhook URLs.\n• Managing Execution Data logs and exporting workflows for backup.\n• Free Google Cloud Hosting setup guide.",
            'videos' => [
                ['id' => 'module-08-v1', 'title' => 'Deploy Your Automation', 'video_id' => '67369c39-c2d7-481f-abbf-6aa879641949', 'duration' => '35:00'],
            ],
        ],
        [
            'id' => 'module-09',
            'title' => 'Module 09: FREE Google Cloud Hosting Guide',
            'release_at' => '2026-08-31 00:00:00',
            'has_blueprint' => true,
            'description' => "A step-by-step walkthrough to get your n8n instance live on the web for free using Google Cloud's Always Free tier. We cover:\n\n• Setting up a Google Cloud Account and Navigating the Console.\n• Configuring a Compute Engine VM with Docker to host n8n.\n• Setting up a Reverse Proxy with Nginx and securing it with SSL (Let's Encrypt).\n• Connecting your custom domain and configuring DNS settings.",
            'videos' => [
                ['id' => 'module-09-v1', 'title' => 'Google Cloud Hosting Guide', 'video_id' => '27d0d009-27fc-432e-b246-63bfd77fb2a9', 'duration' => '33:00'],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | LIVE ARCHIVE (Weekly Q&A & Build Sessions)
    |--------------------------------------------------------------------------
    */
    'live' => [
        [
            'id' => 'live-01',
            'title' => 'Live Session #1: n8n Build & Q&A',
            'release_at' => '2026-07-09 00:00:00',
            'library_id' => '589383',
            'has_blueprint' => true,
            'description' => "Our first group build. We covered:\n\n• order status routing (delivered, pending, cancelled) using an \n n8n Switch node and sending Gmail alerts for order #AJB0012.\n• Live Q&A regarding Module 02.",
            'videos' => [
                ['id' => 'live-01-v1', 'title' => 'Build & Q&A Recording', 'video_id' => '8fc97608-f186-4961-a7b2-31cc8653b174', 'duration' => '71:00 Mins'],
            ],
        ],
        [
            'id' => 'live-02',
            'title' => 'Live Session #2: n8n Build & Q&A',
            'release_at' => '2026-07-16 00:00:00',
            'library_id' => '589383',
            'has_blueprint' => true,
            'description' => "Our second group build. We covered:\n\n• Webhook Concepts - a simple notification that an event happened. \n Webhooks have payload (the accompanying data).\n• Live Q&A regarding individual expectations from the course.",
            'videos' => [
                ['id' => 'live-02-v1', 'title' => 'Build & Q&A Recording', 'video_id' => '1d3b04b5-0270-47cd-851e-054ae2693187', 'duration' => '71:00 Mins'],
            ],
        ],
        [
            'id' => 'live-03',
            'title' => 'Live Session #3: n8n Build & Q&A',
            'release_at' => '2026-07-23 00:00:00',
            'library_id' => '589383',
            'has_blueprint' => true,
            'description' => "Our third group build. We covered:\n\n• Resolved Gemini dimension mismatch issue in the RAG workflow. \n• Discussed requirements for WhatsApp automation - Verified Meta Business, Twilio Account etc.\n• Benefits of GCL Hosting and alternatives \n• Live Q&A regarding local vs international phone numbers.",
            'videos' => [
                ['id' => 'live-03-v1', 'title' => 'Build & Q&A Recording', 'video_id' => 'eb4cf5bc-4bb8-4062-b9d4-a701f45444af', 'duration' => '35:00 Mins'],
            ],
        ],
        [
            'id' => 'live-04',
            'title' => 'Live Session #4: Customer Acquisition Playbook',
            'release_at' => '2026-07-30 00:00:00',
            'library_id' => '589383',
            'has_blueprint' => false,
            'description' => "Our fourth group build. We covered:\n\n• The Mindset Shift: Selling results, not \"AI\". \n• The \"Loom Audit\" outreach method. \n• The 7-day free trials.\n• How to price your setup fees and monthly retainers.",
            'videos' => [
                ['id' => 'live-04-v1', 'title' => 'Customer Acquisition Playbook', 'video_id' => '05429538-2ef9-48cc-b452-034d0127c8d9', 'duration' => '32:41 Mins'],
            ],
        ],

        /*
        |----------------------------------------------------------------------
        | Upcoming live sessions — every SATURDAY 9AM WAT from 8 Aug 2026 (6 total).
        |----------------------------------------------------------------------
        | Announce the attendance_code at the END of each call. Set a FRESH code
        | per session: uncomment its line and fill it just before you go live
        | (it's server-side only, never sent to the browser). Paste each video_id
        | after the session is recorded.
        */
        [
            'id' => 'live-05',
            'title' => 'Live Session #5: Build & Q&A',
            'release_at' => '2026-08-08 09:00:00',
            'library_id' => '589383',
            'has_blueprint' => false,
            'attendance_code' => 'PLAYBOOK', // ← the code you announce live on Sat 8 Aug — swap before the call
            'description' => "Weekly live build & Q&A. Recording appears here after the session.",
            'videos' => [
                ['id' => 'live-05-v1', 'title' => 'Build & Q&A Recording', 'video_id' => '', 'duration' => ''],
            ],
        ],
        [
            'id' => 'live-06',
            'title' => 'Live Session #6: Build & Q&A',
            'release_at' => '2026-08-15 09:00:00',
            'library_id' => '589383',
            'has_blueprint' => false,
            // 'attendance_code' => '', // ← set before Sat 15 Aug
            'description' => "Weekly live build & Q&A. Recording appears here after the session.",
            'videos' => [
                ['id' => 'live-06-v1', 'title' => 'Build & Q&A Recording', 'video_id' => '', 'duration' => ''],
            ],
        ],
        [
            'id' => 'live-07',
            'title' => 'Live Session #7: Build & Q&A',
            'release_at' => '2026-08-22 09:00:00',
            'library_id' => '589383',
            'has_blueprint' => false,
            // 'attendance_code' => '', // ← set before Sat 22 Aug
            'description' => "Weekly live build & Q&A. Recording appears here after the session.",
            'videos' => [
                ['id' => 'live-07-v1', 'title' => 'Build & Q&A Recording', 'video_id' => '', 'duration' => ''],
            ],
        ],
        [
            'id' => 'live-08',
            'title' => 'Live Session #8: Build & Q&A',
            'release_at' => '2026-08-29 09:00:00',
            'library_id' => '589383',
            'has_blueprint' => false,
            // 'attendance_code' => '', // ← set before Sat 29 Aug
            'description' => "Weekly live build & Q&A. Recording appears here after the session.",
            'videos' => [
                ['id' => 'live-08-v1', 'title' => 'Build & Q&A Recording', 'video_id' => '', 'duration' => ''],
            ],
        ],
        [
            'id' => 'live-09',
            'title' => 'Live Session #9: Build & Q&A',
            'release_at' => '2026-09-05 09:00:00',
            'library_id' => '589383',
            'has_blueprint' => false,
            // 'attendance_code' => '', // ← set before Sat 5 Sep
            'description' => "Weekly live build & Q&A. Recording appears here after the session.",
            'videos' => [
                ['id' => 'live-09-v1', 'title' => 'Build & Q&A Recording', 'video_id' => '', 'duration' => ''],
            ],
        ],
        [
            'id' => 'live-10',
            'title' => 'Live Session #10: Build & Q&A',
            'release_at' => '2026-09-12 09:00:00',
            'library_id' => '589383',
            'has_blueprint' => false,
            // 'attendance_code' => '', // ← set before Sat 12 Sep
            'description' => "Weekly live build & Q&A. Recording appears here after the session.",
            'videos' => [
                ['id' => 'live-10-v1', 'title' => 'Build & Q&A Recording', 'video_id' => '', 'duration' => ''],
            ],
        ],
    ],
];
