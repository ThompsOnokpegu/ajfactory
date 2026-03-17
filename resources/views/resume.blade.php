<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AJ Thompson - AI Automation Engineer Resume</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body {
            font-family: 'Inter', sans-serif;
        }
        /* Custom print adjustments to ensure A4 fit and prevent awkward page breaks */
        @media print {
            @page {
                margin: 0.5cm;
            }
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .avoid-break {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body class="bg-gray-100 text-gray-800 antialiased print:bg-white">

    <!-- Print Button (Hidden on Print) -->
    <div class="fixed bottom-8 right-8 print:hidden">
        <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-full shadow-lg transition-colors flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
            </svg>
            Print to PDF
        </button>
    </div>

    <!-- Resume Container (A4 Proportions) -->
    <main class="max-w-[21cm] mx-auto bg-white p-10 md:p-12 shadow-xl my-10 print:m-0 print:p-0 print:shadow-none print:w-full print:max-w-full text-sm">
        
        <!-- Header -->
        <header class="border-b-2 border-gray-800 pb-5 mb-6">
            <h1 class="text-4xl font-bold text-gray-900 uppercase tracking-tight mb-2">AJ Thompson</h1>
            <h2 class="text-xl font-medium text-blue-600 mb-4">AI Automation Engineer & Backend Developer</h2>
            
            <div class="flex flex-wrap gap-x-6 gap-y-2 text-gray-600 text-sm">
                <div class="flex items-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                    Abuja, Nigeria
                </div>
                <div class="flex items-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                    <a href="mailto:tommyriode@gmail.com" class="hover:text-blue-600">tommyriode@gmail.com</a>
                </div>
                <div class="flex items-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/></svg>
                    <a href="https://github.com/thompsonokpegu" target="_blank" class="hover:text-blue-600">github.com/thompsonokpegu</a>
                </div>
                <div class="flex items-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg>
                    <a href="https://linkedin.com/in/thompsonokpegu" target="_blank" class="hover:text-blue-600">linkedin.com/in/thompsonokpegu</a>
                </div>
            </div>
        </header>

        <!-- Professional Summary -->
        <section class="mb-6 avoid-break">
            <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider border-b border-gray-300 pb-1 mb-3">Professional Summary</h3>
            <p class="text-gray-700 leading-relaxed text-justify">
                Business Growth Consultant and Technical Builder with over 7 years of backend development experience, specializing in AI automation, workflow optimization, and vertical SaaS. Proven expertise in designing MVC architectures, handling RESTful APIs, and building robust web applications using Laravel and Livewire. Transitioned deep software engineering fundamentals into developing AI employees, self-hosted n8n workflows, and conversational AI agents to drive revenue and operational efficiency for B2B clients globally.
            </p>
        </section>

        <!-- Core Competencies -->
        <section class="mb-6 avoid-break">
            <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider border-b border-gray-300 pb-1 mb-3">Core Competencies</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-gray-700">
                <p><span class="font-semibold text-gray-900">AI & Automation:</span> n8n (Self-hosted on Google Cloud), Vapi, ElevenLabs, LLM Prompting, Workflow Orchestration.</p>
                <p><span class="font-semibold text-gray-900">Backend Engineering:</span> Laravel, PHP, Livewire, Volt, REST APIs, OOP, MVC Architecture.</p>
                <p><span class="font-semibold text-gray-900">Frontend & Databases:</span> JavaScript, TailwindCSS, MySQL, MongoDB.</p>
                <p><span class="font-semibold text-gray-900">Infrastructure & Tools:</span> Google Cloud, AWS, Git/GitHub, Third-Party API Integration.</p>
            </div>
        </section>

        <!-- Experience -->
        <section class="mb-6">
            <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider border-b border-gray-300 pb-1 mb-4">Professional Experience</h3>
            
            <!-- Job 1 -->
            <div class="mb-5 avoid-break">
                <div class="flex flex-col sm:flex-row justify-between items-baseline mb-1">
                    <h4 class="text-base font-bold text-gray-900">Founder & AI Automation Consultant <span class="text-gray-500 font-normal">| Repetigo</span></h4>
                    <span class="text-sm text-gray-600 font-medium whitespace-nowrap">2025 - Present</span>
                </div>
                <ul class="list-disc list-outside ml-4 text-gray-700 space-y-1.5 mt-2">
                    <li>Architect and deploy B2B AI and workflow automation software, helping service-based businesses streamline operations.</li>
                    <li>Develop custom, AI-powered project tracking portals (e.g., for Elite Elevators and Escalators), featuring automated email notification pipelines and branded client-facing UI using Laravel and Livewire.</li>
                    <li>Design and implement AI Appointment Setters utilizing Vapi and self-hosted n8n environments, specifically optimized for dental clinics and medical spas.</li>
                    <li>Manage targeted, lead-generation Google Ads campaigns to drive client acquisition and validate SaaS offerings.</li>
                </ul>
            </div>

            <!-- Job 2 -->
            <div class="mb-5 avoid-break">
                <div class="flex flex-col sm:flex-row justify-between items-baseline mb-1">
                    <h4 class="text-base font-bold text-gray-900">Creator & Lead Instructor <span class="text-gray-500 font-normal">| AJ Builds AI / AI Automation Accelerator</span></h4>
                    <span class="text-sm text-gray-600 font-medium whitespace-nowrap">2026 - Present</span>
                </div>
                <ul class="list-disc list-outside ml-4 text-gray-700 space-y-1.5 mt-2">
                    <li>Develop and deliver intensive curriculum focused on building and selling AI employees, establishing thought leadership in the AI automation space.</li>
                    <li>Mentor a cohort of students, guiding non-technical founders to successfully self-host and manage n8n instances on Google Cloud.</li>
                    <li>Host high-converting masterclasses on workflow monetization, driving significant waitlist growth and course adoption.</li>
                </ul>
            </div>

            <!-- Job 3 -->
            <div class="mb-5 avoid-break">
                <div class="flex flex-col sm:flex-row justify-between items-baseline mb-1">
                    <h4 class="text-base font-bold text-gray-900">Full Stack Developer <span class="text-gray-500 font-normal">| Deepr Ecommerce</span></h4>
                    <span class="text-sm text-gray-600 font-medium whitespace-nowrap">2022 - 2026</span>
                </div>
                <ul class="list-disc list-outside ml-4 text-gray-700 space-y-1.5 mt-2">
                    <li>Develop robust online stores and custom web applications utilizing vanilla PHP and Laravel.</li>
                    <li>Engineer bespoke e-Commerce strategies and digital infrastructure tailored for SMEs.</li>
                    <li>Integrate complex third-party APIs to enhance platform functionalities, including payment gateways and CDN services.</li>
                </ul>
            </div>

            <!-- Job 4 -->
            <div class="mb-2 avoid-break">
                <div class="flex flex-col sm:flex-row justify-between items-baseline mb-1">
                    <h4 class="text-base font-bold text-gray-900">Backend Developer <span class="text-gray-500 font-normal">| Sainte Apparel LLC</span></h4>
                    <span class="text-sm text-gray-600 font-medium whitespace-nowrap">2020 - 2026</span>
                </div>
                <ul class="list-disc list-outside ml-4 text-gray-700 space-y-1.5 mt-2">
                    <li>Lead continuous backend development to meet evolving business needs.</li>
                    <li>Develop and adapt custom plugins using PHP to fulfill strict business requirements.</li>
                    <li>Assess web infrastructure, successfully improving site speed, user experience, and disaster management protocols.</li>
                </ul>
            </div>
        </section>

        <!-- Projects -->
        <section class="mb-6 avoid-break">
            <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider border-b border-gray-300 pb-1 mb-4">Featured Technical Projects</h3>
            <div class="space-y-3 text-gray-700">
                <p>
                    <span class="font-bold text-gray-900">Multi-Vendor eCommerce MVP:</span> 
                    Developed the MVC architecture utilizing the Laravel Framework. Used Laravel Livewire to create a seamless Single Page Application (SPA) experience. Implemented authentication with Laravel Breeze and integrated Paystack for secure payment processing and seller payouts.
                </p>
                <p>
                    <span class="font-bold text-gray-900">Invoicing & Customer Management System:</span> 
                    Engineered a comprehensive system for managing jobs, invoices, and customer data for a bespoke fashion brand using vanilla PHP, MySQL, and Tailwind CSS.
                </p>
                <p>
                    <span class="font-bold text-gray-900">Industry 4.0 Condition-Based Maintenance Survey:</span> 
                    Implemented a data-driven web application that generates reports and recommendations based on user inputs using PHP and MySQL.
                </p>
            </div>
        </section>

        <!-- Education -->
        <section class="avoid-break">
            <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider border-b border-gray-300 pb-1 mb-3">Education</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <h4 class="font-bold text-gray-900">Higher National Diploma, Computer Science</h4>
                    <p class="text-gray-700">Ken Saro Wiwa Polytechnic, Bori, Nigeria</p>
                </div>
                <div>
                    <h4 class="font-bold text-gray-900">National Diploma, Computer Science</h4>
                    <p class="text-gray-700">Ken Saro Wiwa Polytechnic, Bori, Nigeria</p>
                </div>
            </div>
        </section>

    </main>
</body>
</html>