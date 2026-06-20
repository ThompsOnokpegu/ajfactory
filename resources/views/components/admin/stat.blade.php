@props(['label', 'value', 'sub' => null, 'accent' => 'cyan', 'href' => null])
@php
    $valColors = [
        'cyan' => 'text-cyan-400', 'amber' => 'text-amber-400',
        'red' => 'text-red-400', 'green' => 'text-green-400', 'white' => 'text-white',
    ];
    $iconTints = [
        'cyan' => 'bg-cyan-500/10 text-cyan-400 border-cyan-500/20',
        'amber' => 'bg-amber-500/10 text-amber-400 border-amber-500/20',
        'red' => 'bg-red-500/10 text-red-400 border-red-500/20',
        'green' => 'bg-green-500/10 text-green-400 border-green-500/20',
        'white' => 'bg-zinc-800 text-zinc-400 border-zinc-700',
    ];
    $valColor = $valColors[$accent] ?? 'text-white';
    $iconTint = $iconTints[$accent] ?? $iconTints['white'];
@endphp
<div {{ $attributes->merge(['class' => 'group relative p-5 rounded-2xl bg-zinc-900/50 border border-zinc-800 ' . ($href ? 'hover:border-cyan-500/40 hover:bg-zinc-900 transition' : '')]) }}>
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <div class="text-[10px] font-black uppercase tracking-[0.15em] text-zinc-500 mb-2 truncate">{{ $label }}</div>
            <div class="text-2xl sm:text-[1.7rem] leading-none font-black tracking-tighter {{ $valColor }} truncate">{{ $value }}</div>
            @isset($sub)
                <div class="text-[11px] text-zinc-500 mt-2 leading-snug">{{ $sub }}</div>
            @endisset
        </div>
        @isset($icon)
            <div class="shrink-0 h-9 w-9 rounded-lg border hidden sm:flex items-center justify-center {{ $iconTint }}">{{ $icon }}</div>
        @endisset
    </div>

    @isset($footer)
        <div class="mt-4">{{ $footer }}</div>
    @endisset

    @if($href)
        <a href="{{ $href }}" class="absolute inset-0 rounded-2xl" aria-label="{{ $label }}"></a>
        <svg class="absolute bottom-4 right-4 w-4 h-4 text-zinc-700 group-hover:text-cyan-500 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    @endif
</div>
