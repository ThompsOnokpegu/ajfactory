{{-- Code lookup form. Shared by the "no code yet" and "check another" states. Expects $code. --}}
<form method="GET" action="{{ route('certificate.verify.form') }}" class="mt-4 flex flex-col sm:flex-row gap-2">
    <input type="text" name="code" value="{{ $code }}" placeholder="AJ-XXXXXXXX" maxlength="32"
           class="flex-1 bg-white border border-zinc-300 text-zinc-900 p-3 rounded-lg text-sm font-mono focus:border-cyan-600 focus:ring-0 placeholder:text-zinc-400">
    <button type="submit" class="px-6 py-3 rounded-lg bg-zinc-900 text-white text-[11px] font-bold uppercase tracking-widest hover:bg-cyan-600 transition">
        Verify
    </button>
</form>
