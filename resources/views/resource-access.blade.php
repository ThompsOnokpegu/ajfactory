<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Your resource — AJBuildAI</title>
    @vite(['resources/css/app.css'])
    @if($purchase->status !== 'paid')
        <meta http-equiv="refresh" content="6">
    @endif
</head>
<body class="bg-zinc-950 text-zinc-300 font-sans antialiased min-h-screen flex items-center justify-center p-6">
    <div class="w-full max-w-lg text-center">
        <div class="text-xl font-black tracking-tighter italic text-white uppercase mb-8">AJBUILD<span class="text-cyan-500">AI</span></div>

        @if($purchase->status === 'paid')
            <div class="inline-flex h-16 w-16 items-center justify-center rounded-full bg-green-500/10 border border-green-500/40 text-green-400 mb-6">
                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </div>
            <h1 class="text-3xl font-black text-white uppercase italic tracking-tighter mb-3">You're in.</h1>
            <p class="text-zinc-400 mb-8">Your purchase of <strong class="text-white">{{ $purchase->resource?->title }}</strong> is confirmed. Here's your access — we've also emailed this link to {{ $purchase->email }}.</p>
            @if($purchase->resource?->url)
                <a href="{{ $purchase->resource->url }}" target="_blank"
                   class="inline-block px-10 py-5 bg-cyan-500 text-black font-black uppercase tracking-tighter text-lg rounded-xl hover:bg-white transition-all">
                    Open your resource →
                </a>
            @endif
            <p class="mt-8 text-[11px] font-mono text-zinc-600 uppercase tracking-widest">Bookmark this page — your link stays here.</p>
        @else
            <div class="inline-flex h-16 w-16 items-center justify-center rounded-full bg-amber-500/10 border border-amber-500/40 text-amber-400 mb-6">
                <svg class="w-8 h-8 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.4 0 0 5.4 0 12h4z"/></svg>
            </div>
            <h1 class="text-2xl font-black text-white uppercase italic tracking-tighter mb-3">Confirming your payment…</h1>
            <p class="text-zinc-400">This usually takes a few seconds — the page refreshes itself. Your link will also arrive by email at {{ $purchase->email }}. If it's been a few minutes, reply to that email and we'll sort it out.</p>
        @endif
    </div>
</body>
</html>
