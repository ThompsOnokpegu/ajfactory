<?php

return [

    [
        'id' => 'module-01',
        'title' => 'Module 01: Welcome to the Factory',
        'release_at' => '2026-01-17 00:00:00', // Unlocks Today/Now
        'video_id' => 'welcome_video_id', // Upload a quick intro video
        'duration' => '02:00',
        'has_blueprint' => false,
        'description' => "Welcome to the Founding Batch. In this briefing, I cover how the next 4-5 weeks will run, how to access the private community, and what you need to install before we lift off on Jan 26th."
    ],
    [
        'id' => 'module-02',
        'title' => 'Module 02: Basics of n8n',
        'release_at' => '2026-01-26 00:00:00', // Unlocks Today/Now
        'video_id' => '03bff2b2-1135-4089-b8dc-c095903c87e0', // Replace with actual Bunny Video ID
        'duration' => '26:17 Mins', // Approx total duration
        'has_blueprint' => true,
        'description' => "A complete introduction to the n8n environment. In this module, we cover:\n\n• What is n8n? Cloud vs Desktop Setup.\n• A complete tour of the Interface (Canvas, Nodes, Executions).\n• Building your first 'Hello World' workflow.\n• Understanding JSON Data Flow and Conditional Logic (If/Switch nodes)."
    ],
    [
        'id' => 'module-03',
        'title' => 'Module 03: API Calls With n8n',
        'release_at' => '2026-01-26 00:00:00',
        'video_id' => 'e895c6bf-ff14-46f4-87b1-d62817bfb401',
        'duration' => '28:38 Mins',
        'has_blueprint' => true,
        'description' => "Mastering the most important skill in automation: APIs. This module covers:\n\n• API Concepts for Non-Coders (The Waiter Analogy).\n• Using the HTTP Request Node to fetch live data (Crypto Prices).\n• Handling Authentication and API Keys to access secure data (NewsAPI, Paystack API)."
    ],
    [
        'id' => 'module-04',
        'title' => 'Module 04: Knowledge Base (RAG)',
        'release_at' => '2026-02-02 00:00:00',
        'video_id' => '01246ff7-d654-46c3-8c30-93ab2d894ff3',
        'duration' => '19:33 Mins',
        'has_blueprint' => true,
        'description' => "Teaching the AI about your specific business data. We cover:\n\n• Understanding Retrieval-Augmented Generation (RAG).\n• Setting up a Pinecone Vector Database with Gemini Dimensions (768).\n• Building an Ingestion Pipeline to read PDFs and save them as vectors."
    ],
    [
        'id' => 'module-05',
        'title' => 'Module 05: WhatsApp Automation',
        'release_at' => '2026-02-02 00:00:00',
        'video_id' => 'e3c1e139-b0d1-4c84-aa17-97c61710b526',
        'duration' => '45:00',
        'has_blueprint' => true,
        'description' => "Building a production-ready WhatsApp bot using Twilio. Topics include:\n\n• Buying a Real Number & Setting up a Sender Profile.\n• Understanding the Meta 24-Hour Session Window & Templates.\n• Configuring Production Webhooks to handle live traffic."
    ],
    [
        'id' => 'module-06',
        'title' => 'Module 06: AI Voice Support Agent',
        'video_id' => 'ad4ae58d-9faf-453d-8fa9-45df3d78d1a0',
        'release_at' => '2026-02-16 00:00:00',
        'duration' => '40:00',
        'has_blueprint' => true,
        'description' => "Creating 'Tola', a real-time voice receptionist. This module covers:\n\n• Introduction to Voice AI latency and the Vapi Orchestrator.\n• Configuring the Voice, Transcriber, and System Prompt.\n• Using Function Calling to connect Vapi to n8n for real-time data lookups."
    ],
    [
        'id' => 'module-07',
        'title' => 'Module 07: AI Chat Support Agent',
        'release_at' => '2026-02-16 00:00:00',
        'video_id' => '78096f8a-8945-4052-8d4d-67ede32e4e81',
        'duration' => '40:00',
        'has_blueprint' => true,
        'description' => "Bringing it all together into a smart Chat Agent. We cover:\n\n• Using the AI Agent Node with Window Buffer Memory.\n• Connecting your Pinecone Knowledge Base as a 'Tool'.\n• Logic for handling Fallbacks and Human Handoffs via Email."
    ],
    [
        'id' => 'module-08',
        'title' => 'Module 08: Deploy Your Automation',
        'release_at' => '2026-02-23 00:00:00',
        'video_id' => 'bunny_video_id_module_08',
        'duration' => '35:00',
        'has_blueprint' => true,
        'description' => "Taking your bots from 'Student Project' to 'Business Asset'. Topics:\n\n• Building a Global Error Handling workflow (The Safety Net).\n• The critical difference between Test URLs and Production Webhook URLs.\n• Managing Execution Data logs and exporting workflows for backup."
    ]
];