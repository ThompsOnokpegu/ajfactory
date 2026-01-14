<?php

return [
    [
        'id' => 'module-02',
        'title' => 'Module 02: Basics of n8n',
        'video_id' => '03bff2b2-1135-4089-b8dc-c095903c87e0', // Replace with actual Bunny Video ID
        'duration' => '50:00', // Approx total duration
        'has_blueprint' => true,
        'description' => "A complete introduction to the n8n environment. In this module, we cover:\n\n• What is n8n? Cloud vs Desktop Setup.\n• A complete tour of the Interface (Canvas, Nodes, Executions).\n• Building your first 'Hello World' workflow.\n• Understanding JSON Data Flow and Conditional Logic (If/Switch nodes)."
    ],
    [
        'id' => 'module-03',
        'title' => 'Module 03: API Calls With n8n',
        'video_id' => 'e895c6bf-ff14-46f4-87b1-d62817bfb401',
        'duration' => '40:00',
        'has_blueprint' => true,
        'description' => "Mastering the most important skill in automation: APIs. This module covers:\n\n• API Concepts for Non-Coders (The Waiter Analogy).\n• Using the HTTP Request Node to fetch live data (Crypto Prices).\n• Handling Authentication and API Keys to access secure data (NewsAPI, Paystack API)."
    ],
    [
        'id' => 'module-04',
        'title' => 'Module 04: Knowledge Base (RAG)',
        'video_id' => 'bunny_video_id_module_04',
        'duration' => '45:00',
        'has_blueprint' => true,
        'description' => "Teaching the AI about your specific business data. We cover:\n\n• Understanding Retrieval-Augmented Generation (RAG).\n• Setting up a Pinecone Vector Database with Gemini Dimensions (768).\n• Building an Ingestion Pipeline to read PDFs and save them as vectors."
    ],
    [
        'id' => 'module-05',
        'title' => 'Module 05: WhatsApp Automation',
        'video_id' => 'bunny_video_id_module_05',
        'duration' => '45:00',
        'has_blueprint' => true,
        'description' => "Building a production-ready WhatsApp bot using Twilio. Topics include:\n\n• Buying a Real Number & Setting up a Sender Profile.\n• Understanding the Meta 24-Hour Session Window & Templates.\n• Configuring Production Webhooks to handle live traffic."
    ],
    [
        'id' => 'module-06',
        'title' => 'Module 06: AI Voice Support Agent',
        'video_id' => 'bunny_video_id_module_06',
        'duration' => '40:00',
        'has_blueprint' => true,
        'description' => "Creating 'Tola', a real-time voice receptionist. This module covers:\n\n• Introduction to Voice AI latency and the Vapi Orchestrator.\n• Configuring the Voice, Transcriber, and System Prompt.\n• Using Function Calling to connect Vapi to n8n for real-time data lookups."
    ],
    [
        'id' => 'module-07',
        'title' => 'Module 07: AI Chat Support Agent',
        'video_id' => 'bunny_video_id_module_07',
        'duration' => '40:00',
        'has_blueprint' => true,
        'description' => "Bringing it all together into a smart Chat Agent. We cover:\n\n• Using the AI Agent Node with Window Buffer Memory.\n• Connecting your Pinecone Knowledge Base as a 'Tool'.\n• Logic for handling Fallbacks and Human Handoffs via Email."
    ],
    [
        'id' => 'module-08',
        'title' => 'Module 08: Deploy Your Automation',
        'video_id' => 'bunny_video_id_module_08',
        'duration' => '35:00',
        'has_blueprint' => true,
        'description' => "Taking your bots from 'Student Project' to 'Business Asset'. Topics:\n\n• Building a Global Error Handling workflow (The Safety Net).\n• The critical difference between Test URLs and Production Webhook URLs.\n• Managing Execution Data logs and exporting workflows for backup."
    ]
];