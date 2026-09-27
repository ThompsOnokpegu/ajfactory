{{--
    Public certificate verification.

    This page is the whole reason the certificate is worth anything: an unverifiable
    certificate is a JPEG. Public on purpose - a client or employer checking a code must
    not need an account.

    It shows only what it has to: the credited name, cohort, issue date and what was
    built. No email, no student id, nothing that turns a verification tool into a
    directory of students.

    Expects: $code (submitted string or null), $enrollment (verified match or null),
    $modules, $name, $issuedAt.
--}}
<!DOCTYPE html>
<html lang="en" class="bg-zinc-950">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verify a certificate - AI Automation Accelerator</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
</head>
<body class="bg-zinc-950 text-zinc-300 font-sans antialiased min-h-screen" style="font-family:'Space Grotesk',ui-sans-serif,system-ui,sans-serif;">

    <main class="max-w-xl mx-auto px-6 py-16">
        <a href="{{ url('/') }}" class="text-[11px] font-black uppercase tracking-widest text-zinc-500 hover:text-cyan-400 transition">ajbuildai.com</a>

        <h1 class="mt-6 text-2xl font-bold tracking-tight text-white">Verify a certificate</h1>
        <p class="mt-2 text-sm text-zinc-400 leading-relaxed">
            Enter the code printed on an AI Automation Accelerator certificate of completion.
        </p>

        <form method="GET" action="{{ route('certificate.verify.form') }}" class="mt-6 flex flex-col sm:flex-row gap-2">
            <input type="text" name="code" value="{{ $code }}" placeholder="AJ-XXXXXXXX" maxlength="32"
                   class="flex-1 bg-zinc-900 border border-zinc-800 text-white p-3 rounded-xl text-sm font-mono focus:border-cyan-500 focus:ring-0 placeholder:text-zinc-700">
            <button type="submit" class="px-6 py-3 rounded-xl bg-cyan-500 text-black text-[11px] font-black uppercase tracking-widest hover:bg-white transition">
                Verify
            </button>
        </form>

        @if($code)
            @if($enrollment)
                <div class="mt-8 rounded-2xl border border-green-500/40 bg-green-500/5 p-6">
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-green-500">Genuine certificate</p>

                    <p class="mt-4 text-[11px] font-black uppercase tracking-widest text-zinc-500">Awarded to</p>
                    <p class="text-xl font-bold text-white break-words">{{ $name }}</p>

                    <div class="mt-4 grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-[11px] font-black uppercase tracking-widest text-zinc-500">Cohort</p>
                            <p class="text-sm text-zinc-200">{{ $enrollment->cohort }}</p>
                        </div>
                        <div>
                            <p class="text-[11px] font-black uppercase tracking-widest text-zinc-500">Issued</p>
                            <p class="text-sm text-zinc-200">{{ $issuedAt }}</p>
                        </div>
                    </div>

                    <p class="mt-5 text-[11px] font-black uppercase tracking-widest text-zinc-500">Completed</p>
                    <p class="text-sm text-zinc-300 leading-relaxed">
                        Every module of the AI Automation Accelerator, each requiring working build proof reviewed and approved individually.
                    </p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach($modules as $m)
                            <span class="text-[11px] px-2.5 py-1 rounded-lg border border-zinc-800 text-zinc-400">{{ $m }}</span>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="mt-8 rounded-2xl border border-amber-500/40 bg-amber-500/5 p-6">
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-amber-400">No match</p>
                    <p class="mt-2 text-sm text-zinc-300 leading-relaxed">
                        No certificate was issued with that code. Check for typos - the code has no letter O or I and no zero or one, so those are easy to mistake for 0, 1, Q or J.
                    </p>
                </div>
            @endif
        @endif

        <p class="mt-10 text-[11px] text-zinc-600 leading-relaxed">
            The AI Automation Accelerator is a 6-week programme run by Deepr Web Services. A certificate of
            completion records that a student finished every module with reviewed build proof. It is not an
            accredited qualification.
        </p>
    </main>
</body>
</html>
