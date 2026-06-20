@props(['name' => '?'])
@php
    $initials = collect(explode(' ', trim((string) $name)))
        ->filter()
        ->take(2)
        ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))
        ->implode('') ?: '?';
@endphp
<div {{ $attributes->merge(['class' => 'shrink-0 h-9 w-9 rounded-full bg-cyan-500/10 border border-cyan-500/20 flex items-center justify-center text-[11px] font-black text-cyan-400']) }}>
    {{ $initials }}
</div>
