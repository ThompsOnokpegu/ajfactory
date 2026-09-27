{{--
    The printable / screenshotable certificate.

    Dark on screen on purpose: the realistic use for this audience is a screenshot to
    WhatsApp status or LinkedIn, and dark + cyan is the Accelerator's look. The
    @media print block flips it to ink-friendly white, because printing a full-bleed
    zinc-950 page is both ugly and expensive.

    Wording is "Certificate of Completion" and nothing stronger. Deepr Web Services is
    not an awarding body, so it must never read as accredited or chartered.

    Expects: $name, $code, $issuedAt, $cohort, $modules (array of titles).
--}}
<!DOCTYPE html>
<html lang="en" class="bg-zinc-950">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Certificate of Completion - {{ $name }}</title>
    <meta name="robots" content="noindex">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
    <style>
        @media print {
            @page { size: A4 landscape; margin: 12mm; }
            body { background: #fff !important; }
            .no-print { display: none !important; }
            .sheet {
                background: #fff !important;
                border-color: #d4d4d8 !important;
                box-shadow: none !important;
                color: #18181b !important;
            }
            .sheet .ink-white { color: #09090b !important; }
            .sheet .ink-muted { color: #52525b !important; }
            .sheet .ink-accent { color: #0e7490 !important; }
            .sheet .rule { background: #0e7490 !important; }
            .sheet .chip { border-color: #d4d4d8 !important; color: #3f3f46 !important; }
        }
    </style>
</head>
<body class="bg-zinc-950 text-zinc-300 font-sans antialiased" style="font-family:'Space Grotesk',ui-sans-serif,system-ui,sans-serif;">

    <div class="no-print max-w-4xl mx-auto px-6 pt-8 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('dashboard') }}" class="text-[11px] font-black uppercase tracking-widest text-zinc-500 hover:text-cyan-400 transition">&larr; Back to dashboard</a>
        <button onclick="window.print()"
                class="px-5 py-2.5 rounded-xl bg-cyan-500 text-black text-[11px] font-black uppercase tracking-widest hover:bg-white transition">
            Print or save as PDF
        </button>
    </div>

    <main class="max-w-4xl mx-auto p-6">
        <div class="sheet relative overflow-hidden rounded-3xl border border-zinc-800 bg-zinc-900/40 p-8 sm:p-14">

            <div class="rule h-1 w-16 bg-cyan-500 rounded-full"></div>

            <p class="ink-accent mt-6 text-[11px] font-black uppercase tracking-[0.25em] text-cyan-400">
                AI Automation Accelerator
            </p>
            <h1 class="ink-white mt-2 text-2xl sm:text-3xl font-bold tracking-tight text-white">
                Certificate of Completion
            </h1>

            <p class="ink-muted mt-10 text-[11px] font-black uppercase tracking-[0.2em] text-zinc-500">
                Awarded to
            </p>
            <p class="ink-white mt-2 text-3xl sm:text-4xl font-bold tracking-tight text-white break-words">
                {{ $name }}
            </p>

            <p class="ink-muted mt-8 text-sm leading-relaxed text-zinc-400 max-w-2xl">
                for completing every module of the AI Automation Accelerator, Cohort {{ $cohort }}.
                Each module required working build proof, reviewed and approved individually.
            </p>

            <p class="ink-muted mt-10 text-[11px] font-black uppercase tracking-[0.2em] text-zinc-500">
                Workflows built and verified
            </p>
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach($modules as $m)
                    <span class="chip text-[11px] px-2.5 py-1 rounded-lg border border-zinc-800 text-zinc-400">{{ $m }}</span>
                @endforeach
            </div>

            <div class="mt-12 pt-6 border-t border-zinc-800 flex flex-wrap items-end justify-between gap-6">
                <div>
                    <p class="ink-white text-sm font-bold text-white">AJ Thompson</p>
                    <p class="ink-muted text-[11px] text-zinc-500">Instructor &middot; Deepr Web Services</p>
                    <p class="ink-muted text-[11px] text-zinc-600 mt-0.5">ajbuildai.com</p>
                </div>
                <div class="text-right">
                    <p class="ink-muted text-[11px] font-black uppercase tracking-widest text-zinc-500">Issued</p>
                    <p class="ink-white text-sm font-bold text-white">{{ $issuedAt }}</p>
                    <p class="ink-muted text-[11px] font-mono text-zinc-500 mt-2">{{ $code }}</p>
                    <p class="ink-muted text-[10px] text-zinc-600">Verify at {{ url('/verify') }}</p>
                </div>
            </div>
        </div>

        <p class="no-print text-[11px] text-zinc-600 mt-4 leading-relaxed">
            Anyone can confirm this is genuine at <a href="{{ route('certificate.verify.form') }}" class="text-cyan-500 hover:underline">{{ url('/verify') }}</a>
            using the code <span class="font-mono text-zinc-400">{{ $code }}</span>. This is a certificate of completion issued by Deepr Web Services, not an accredited qualification.
        </p>
    </main>
</body>
</html>
