{{--
    A small, non-functional rendering of the certificate, for the sales page.

    The name reads "Your Name Here" ON PURPOSE. A realistic name on a public page would
    be a fabricated record of a person who does not exist, and this way the buyer pictures
    themselves instead. The code is XXXXXXXX for the same reason: it resolves to nothing,
    and it must stay that way - never put a code here that /verify would accept.

    Visual only. The real certificate is resources/views/certificate.blade.php; if that
    design changes, this has to be updated with it. It is deliberately a simplified
    likeness rather than an include of the real page, which carries a full <html> document
    and print styles that cannot be nested inside the landing page.
--}}
@php $mockCohort = config('accelerator.cohort_number', 3); @endphp

<div class="relative w-full aspect-[1.414/1] bg-white rounded-xl border border-zinc-300 shadow-2xl overflow-hidden select-none"
     role="img"
     aria-label="Example of the Certificate of Completion awarded when every module build is approved">

    {{-- corner brand block --}}
    <div class="absolute top-0 right-0 w-[18%] h-[26%] bg-zinc-900 flex items-center justify-center">
        <div class="text-center leading-none">
            <div class="text-white font-bold tracking-tight text-[7px] sm:text-[10px]">AJBUILDS</div>
            <div class="text-cyan-400 font-bold text-[5px] sm:text-[7px] tracking-[0.3em] mt-0.5">AI</div>
        </div>
    </div>

    {{-- angular wedge --}}
    <div class="absolute bottom-0 left-0 w-[32%]" aria-hidden="true">
        <svg viewBox="0 0 150 110" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full">
            <path d="M0 0v110h150L0 0z" fill="#18181b"/>
            <path d="M0 62v48h66L0 62z" fill="#06b6d4"/>
        </svg>
    </div>

    <div class="absolute inset-[3%] border border-zinc-200 rounded-md pointer-events-none"></div>

    <div class="relative h-full flex flex-col items-center justify-center text-center px-[10%] py-[6%]">
        <p class="text-[5px] sm:text-[7px] font-bold uppercase tracking-[0.3em] text-zinc-500">Certificate of Completion</p>

        <p class="mt-[3%] text-[11px] sm:text-base font-bold tracking-tight text-zinc-900 leading-tight">
            AI Automation Accelerator
        </p>

        <p class="mt-[5%] text-[5px] sm:text-[7px] font-bold uppercase tracking-[0.25em] text-zinc-500">Issued to</p>
        <p class="mt-[1%] text-[13px] sm:text-xl text-zinc-400 italic">Your Name Here</p>

        <div class="mt-[3%] w-[14%] min-w-[34px]">
            @include('partials.certificate-seal', ['cohort' => $mockCohort, 'size' => 52])
        </div>

        <p class="mt-[3%] text-[4.5px] sm:text-[7px] text-zinc-500 leading-relaxed">
            Issued by: Deepr Web Services &nbsp;|&nbsp; Verify: ajbuildai.com/verify/AJ-XXXXXXXX
        </p>
    </div>
</div>
