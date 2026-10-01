{{--
    The certificate.

    Laid out the way issued credentials are (the WorldQuant/Credly reference AJ supplied):
    light sheet, centred, framed, angular brand blocks at two corners, the credential name
    large, "ISSUED TO" over the recipient, a seal, then a single footer line carrying issue
    date, issuer and the verification URL.

    LIGHT, not the dark Accelerator look, on purpose. A certificate reads as a document
    rather than a web page, it prints without eating a cartridge, and every credential this
    sits beside in a CV or a LinkedIn post is light. Cyan and zinc carry the brand instead.

    The module list is NOT here - it lives on the verify page, the same split Credly uses
    (clean credential, detail behind the verification link). Nine module titles crowded the
    lower third and made it read like a syllabus.

    Wording is "Certificate of Completion" and nothing stronger, and the sheet carries the
    "not an accredited qualification" line: Deepr Web Services is not an awarding body.

    Expects: $name, $code, $issuedAt, $cohort, $modules (unused here, kept for the route).
--}}
<!DOCTYPE html>
<html lang="en">
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
        body { background: #f4f4f5; font-family: 'Space Grotesk', ui-sans-serif, system-ui, sans-serif; }
        .sheet { aspect-ratio: 1.414 / 1; }
        @media (max-width: 860px) { .sheet { aspect-ratio: auto; } }
        @media print {
            @page { size: A4 landscape; margin: 0; }
            body { background: #fff; }
            .no-print { display: none !important; }
            .sheet {
                width: 100% !important; max-width: none !important;
                border: none !important; box-shadow: none !important;
                border-radius: 0 !important; aspect-ratio: 1.414 / 1;
            }
        }
    </style>
</head>
<body class="text-zinc-900 antialiased">

    <div class="no-print max-w-5xl mx-auto px-6 pt-8 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('dashboard') }}" class="text-[11px] font-bold uppercase tracking-widest text-zinc-500 hover:text-cyan-600 transition">&larr; Back to dashboard</a>
        <button onclick="window.print()"
                class="px-5 py-2.5 rounded-lg bg-zinc-900 text-white text-[11px] font-bold uppercase tracking-widest hover:bg-cyan-600 transition">
            Print or save as PDF
        </button>
    </div>

    <main class="max-w-5xl mx-auto p-6">
        <div class="sheet relative overflow-hidden bg-white border border-zinc-300 rounded-xl shadow-sm">

            {{-- Corner brand blocks, the angular device the reference uses --}}
            <div class="absolute top-0 right-0 w-28 h-28 sm:w-36 sm:h-36 bg-zinc-900 flex items-center justify-center">
                <div class="text-center leading-none">
                    <div class="text-white font-bold tracking-tight text-sm sm:text-base">AJBUILD</div>
                    <div class="text-cyan-400 font-bold text-[10px] sm:text-xs tracking-[0.3em] mt-0.5">AI</div>
                </div>
            </div>
            <div class="absolute bottom-0 left-0" aria-hidden="true">
                <svg width="150" height="110" viewBox="0 0 150 110" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M0 0v110h150L0 0z" fill="#18181b"/>
                    <path d="M0 62v48h66L0 62z" fill="#06b6d4"/>
                </svg>
            </div>

            {{-- Inner frame --}}
            <div class="absolute inset-3 sm:inset-5 border border-zinc-200 pointer-events-none rounded-lg"></div>

            <div class="relative h-full flex flex-col items-center justify-center text-center px-8 sm:px-20 py-14">

                <p class="text-[10px] sm:text-[11px] font-bold uppercase tracking-[0.3em] text-zinc-500">
                    Certificate of Completion
                </p>

                <h1 class="mt-4 text-2xl sm:text-4xl font-bold tracking-tight text-zinc-900 leading-tight max-w-2xl">
                    AI Automation Accelerator
                </h1>
                <p class="mt-2 text-xs sm:text-sm text-zinc-500">6 weeks &middot; 9 production workflows</p>

                <p class="mt-8 sm:mt-10 text-[10px] sm:text-[11px] font-bold uppercase tracking-[0.25em] text-zinc-500">
                    Issued to
                </p>
                <p class="mt-2 text-2xl sm:text-4xl text-zinc-900 break-words max-w-3xl">
                    {{ $name }}
                </p>

                <div class="mt-6 sm:mt-8">
                    @include('partials.certificate-seal', ['cohort' => $cohort, 'size' => 96])
                </div>

                <p class="mt-6 text-[11px] sm:text-xs text-zinc-600 max-w-xl leading-relaxed">
                    Completed every module of the programme, each requiring working build proof reviewed and approved individually.
                </p>

                <div class="mt-5 text-[10px] sm:text-[11px] text-zinc-500 leading-relaxed">
                    <p>Issued on: {{ strtoupper($issuedAt) }} &nbsp;|&nbsp; Issued by: Deepr Web Services</p>
                    <p class="mt-0.5">Verify: {{ url('/verify/' . $code) }}</p>
                </div>
            </div>
        </div>

        <p class="no-print text-[11px] text-zinc-500 mt-4 leading-relaxed">
            Anyone can confirm this is genuine at
            <a href="{{ url('/verify/' . $code) }}" class="text-cyan-600 hover:underline">{{ url('/verify/' . $code) }}</a>.
            This is a certificate of completion issued by Deepr Web Services. It is not an accredited qualification.
        </p>
    </main>
</body>
</html>
