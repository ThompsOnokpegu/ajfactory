{{--
    Public certificate verification - the page the "Verify:" line on the certificate points at.

    Built as the detail half of the credential, the way Credly's public badge page is: the
    seal and the facts up top, then what the credential actually required, then the modules
    as tags. The certificate itself stays clean; the substance lives here, one click away.

    Public and unauthenticated on purpose. A client or employer checking a code must not need
    an account, and an unverifiable certificate is a JPEG.

    Shows the credited name, cohort, issue date and modules - and never an email or student
    id, so it stays a verification tool rather than a directory of students.

    Expects: $code, $enrollment, $name, $issuedAt, $modules.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $enrollment ? 'Verified: ' . $name : 'Verify a certificate' }} - AI Automation Accelerator</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
    <style>body { background:#f4f4f5; font-family:'Space Grotesk',ui-sans-serif,system-ui,sans-serif; }</style>
</head>
<body class="text-zinc-900 antialiased min-h-screen">

    <header class="border-b border-zinc-200 bg-white">
        <div class="max-w-3xl mx-auto px-6 py-4 flex items-center justify-between">
            <a href="{{ url('/') }}" class="font-bold tracking-tight text-zinc-900">AJBUILD<span class="text-cyan-600">AI</span></a>
            <span class="text-[11px] font-bold uppercase tracking-widest text-zinc-500">Credential verification</span>
        </div>
    </header>

    <main class="max-w-3xl mx-auto px-6 py-10">

        @if($code && $enrollment)
            <div class="bg-white border border-zinc-200 rounded-2xl overflow-hidden">

                <div class="flex items-center gap-2 px-6 py-3 bg-green-50 border-b border-green-200">
                    <svg class="w-4 h-4 text-green-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-xs font-bold uppercase tracking-widest text-green-800">Verified credential</span>
                </div>

                <div class="p-6 sm:p-10 text-center">
                    <div class="flex justify-center">
                        @include('partials.certificate-seal', ['cohort' => $enrollment->cohort, 'size' => 110])
                    </div>

                    <h1 class="mt-6 text-xl sm:text-2xl font-bold tracking-tight text-zinc-900">
                        AI Automation Accelerator
                    </h1>
                    <p class="text-xs text-zinc-500 mt-1">Certificate of Completion</p>

                    <p class="mt-6 text-[11px] font-bold uppercase tracking-[0.25em] text-zinc-500">Issued to</p>
                    <p class="mt-1 text-2xl sm:text-3xl text-zinc-900 break-words">{{ $name }}</p>
                </div>

                <dl class="grid grid-cols-2 sm:grid-cols-4 border-t border-zinc-200 divide-x divide-zinc-200">
                    <div class="p-4 text-center">
                        <dt class="text-[10px] font-bold uppercase tracking-widest text-zinc-500">Issued on</dt>
                        <dd class="text-sm text-zinc-900 mt-1">{{ $issuedAt }}</dd>
                    </div>
                    <div class="p-4 text-center">
                        <dt class="text-[10px] font-bold uppercase tracking-widest text-zinc-500">Issued by</dt>
                        <dd class="text-sm text-zinc-900 mt-1">Deepr Web Services</dd>
                    </div>
                    <div class="p-4 text-center border-t sm:border-t-0 border-zinc-200">
                        <dt class="text-[10px] font-bold uppercase tracking-widest text-zinc-500">Cohort</dt>
                        <dd class="text-sm text-zinc-900 mt-1">{{ $enrollment->cohort }}</dd>
                    </div>
                    <div class="p-4 text-center border-t sm:border-t-0 border-zinc-200">
                        <dt class="text-[10px] font-bold uppercase tracking-widest text-zinc-500">Code</dt>
                        <dd class="text-sm font-mono text-zinc-900 mt-1">{{ $code }}</dd>
                    </div>
                </dl>

                <div class="border-t border-zinc-200 p-6 sm:p-8">
                    <h2 class="text-[11px] font-bold uppercase tracking-[0.2em] text-zinc-500">What this required</h2>
                    <p class="mt-2 text-sm text-zinc-700 leading-relaxed">
                        Every module below had to be built and submitted as working proof, then reviewed and
                        approved individually before the next one opened. This is a record of completed builds,
                        not of attendance or video watch time.
                    </p>

                    <h2 class="mt-6 text-[11px] font-bold uppercase tracking-[0.2em] text-zinc-500">Modules completed</h2>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach($modules as $m)
                            <span class="text-[11px] px-2.5 py-1 rounded-lg bg-zinc-100 border border-zinc-200 text-zinc-700">{{ $m }}</span>
                        @endforeach
                    </div>
                </div>
            </div>

            <details class="mt-6 bg-white border border-zinc-200 rounded-2xl p-5">
                <summary class="text-xs font-bold uppercase tracking-widest text-zinc-500 cursor-pointer">Check another code</summary>
                @include('partials.verify-form', ['code' => null])
            </details>

        @else
            <div class="bg-white border border-zinc-200 rounded-2xl p-6 sm:p-10">
                <h1 class="text-xl font-bold tracking-tight text-zinc-900">Verify a certificate</h1>
                <p class="mt-2 text-sm text-zinc-600 leading-relaxed">
                    Enter the code printed on an AI Automation Accelerator certificate of completion.
                </p>

                @include('partials.verify-form', ['code' => $code])

                @if($code)
                    <div class="mt-6 rounded-xl border border-amber-300 bg-amber-50 p-4">
                        <p class="text-xs font-bold uppercase tracking-widest text-amber-800">No match</p>
                        <p class="mt-1 text-sm text-zinc-700 leading-relaxed">
                            No certificate was issued with that code. Check for typos - a code contains no letter
                            O, I or L and no zero or one, so those are easy to mistake for 0, 1, Q or J.
                        </p>
                    </div>
                @endif
            </div>
        @endif

        <p class="mt-8 text-[11px] text-zinc-500 leading-relaxed">
            The AI Automation Accelerator is an 8-week programme run by Deepr Web Services
            (<a href="{{ url('/') }}" class="text-cyan-600 hover:underline">ajbuildai.com</a>). A certificate of
            completion records that a student finished every module with reviewed build proof. It is not an
            accredited qualification.
        </p>
    </main>
</body>
</html>
