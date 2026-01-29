<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Legal & Privacy | Automation Factory</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@700;900&family=Inter:wght@400;600&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <style>
        .bg-grid { background-image: linear-gradient(to right, #18181b 1px, transparent 1px), linear-gradient(to bottom, #18181b 1px, transparent 1px); background-size: 50px 50px; }
        .legal-content h2 { @apply text-white text-xl font-black uppercase italic tracking-tighter mt-12 mb-4; }
        .legal-content p { @apply text-zinc-400 mb-4 leading-relaxed; }
        .legal-content ul { @apply list-disc list-inside text-zinc-400 mb-6 space-y-2; }
    </style>
</head>
<body class="bg-zinc-950 text-zinc-300 font-sans antialiased">
    
    <div class="fixed inset-0 bg-grid z-0 opacity-20 pointer-events-none"></div>

    <div class="relative z-10 min-h-screen flex flex-col">
        <!-- Header -->
        <header class="p-6 border-b border-zinc-900 bg-zinc-950/80 backdrop-blur-md sticky top-0">
            <div class="max-w-4xl mx-auto flex justify-between items-center">
                <a href="/" class="text-sm font-black tracking-tighter italic text-white uppercase">
                    AUTO<span class="text-cyan-500">MATION</span>.FACTORY
                </a>
                <a href="javascript:history.back()" class="text-[10px] font-black uppercase text-zinc-500 hover:text-white transition tracking-widest">
                    [ Back_to_Portal ]
                </a>
            </div>
        </header>

        <main class="flex-1 max-w-3xl mx-auto w-full py-20 px-6">
            <div class="mb-16">
                <span class="inline-block px-2 py-1 bg-zinc-900 border border-zinc-800 text-[10px] font-mono text-zinc-500 uppercase tracking-widest mb-4">
                    Effective_Date: January_2026
                </span>
                <h1 class="text-5xl md:text-6xl font-black text-white uppercase italic tracking-tighter leading-none">
                    Legal_Core <br>
                    <span class="text-cyan-500">& Privacy Protocols</span>
                </h1>
            </div>

            <div class="legal-content">
                <section id="terms">
                    <h2>01. Terms of Service</h2>
                    <p>By accessing the Automation Factory (the "Accelerator"), you agree to be bound by these terms. These systems are provided for educational and commercial implementation purposes.</p>
                    <ul>
                        <li>The "Snapshot Vault" blueprints are licensed for individual or agency use only. Reselling the raw JSON files is strictly prohibited.</li>
                        <li>You are responsible for the actions of any AI agents deployed using our blueprints.</li>
                        <li>Usage of the Meta Cloud API and Vapi is subject to their respective third-party terms.</li>
                    </ul>
                </section>

                <section id="privacy">
                    <h2>02. Privacy Policy</h2>
                    <p>We respect your data. Our identity protocols are designed to be transparent.</p>
                    <p><strong>Data Collection:</strong> We collect your name, email, and WhatsApp number specifically to provision your Member Terminal access and send system updates.</p>
                    <p><strong>Third Parties:</strong> Payment data is handled exclusively by Paystack and Flutterwave. We do not store your credit card or bank details on our local servers.</p>
                </section>

                <section id="refunds">
                    <h2>03. Refund & Cancellation</h2>
                    <p>Because the Accelerator provides immediate access to the "Snapshot Vault" (digital goods) upon enrollment, we operate a limited refund policy.</p>
                    <ul>
                        <li>Refunds are only issued if requested within 24 hours of purchase and if NO blueprints have been downloaded from the Vault.</li>
                        <li>Once a blueprint is accessed, the "intellectual property" has been transferred, and the sale is final.</li>
                    </ul>
                </section>

                <section id="liability">
                    <h2>04. Limitation of Liability</h2>
                    <p>AJ Thompson and the Automation Factory are not liable for any costs incurred due to API usage (Vapi, Meta, OpenAI) or malfunctions in deployed workflows. Test your systems in a sandbox environment before full deployment.</p>
                </section>
            </div>

            <footer class="mt-32 pt-12 border-t border-zinc-900 text-center">
                <p class="text-[10px] font-mono text-zinc-700 uppercase tracking-[0.5em]">
                    System_End // Compliance_Verified_2026
                </p>
            </footer>
        </main>
    </div>
</body>
</html>