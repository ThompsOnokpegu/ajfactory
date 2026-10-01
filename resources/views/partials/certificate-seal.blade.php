{{--
    The Accelerator seal.

    A shield in the shape credential badges use (WorldQuant/Credly and most issuers),
    built from our own marks - dark zinc band over cyan, the AJBuildAI wordmark, and the
    cohort. Inline SVG so it prints and screenshots without loading an image, and so it
    stays crisp at any size.

    Deliberately NOT a gold-foil rosette: this credential's weight comes from being
    verifiable, and fake embossing would undercut the "no hype" positioning.

    Expects: $cohort. Optional: $size (px, default 120).
--}}
@php $s = $size ?? 120; @endphp
<svg width="{{ $s }}" height="{{ $s * 1.18 }}" viewBox="0 0 120 142" fill="none" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="AI Automation Accelerator seal">
    {{-- shield body --}}
    <path d="M4 4h112v78c0 24-26 38-56 56C30 120 4 106 4 82V4z" fill="#ffffff" stroke="#d4d4d8" stroke-width="2"/>
    {{-- dark header band --}}
    <path d="M8 8h104v34H8z" fill="#18181b"/>
    <text x="60" y="26" text-anchor="middle" font-family="'Space Grotesk',Arial,sans-serif" font-size="12" font-weight="700" fill="#ffffff" letter-spacing="0.5">AJBUILD</text>
    <text x="60" y="37" text-anchor="middle" font-family="'Space Grotesk',Arial,sans-serif" font-size="9" font-weight="700" fill="#06b6d4" letter-spacing="3">AI</text>
    {{-- cyan panel --}}
    <path d="M8 46h104v36H8z" fill="#06b6d4"/>
    <text x="60" y="62" text-anchor="middle" font-family="'Space Grotesk',Arial,sans-serif" font-size="10" font-weight="700" fill="#06293a" letter-spacing="0.3">AI AUTOMATION</text>
    <text x="60" y="75" text-anchor="middle" font-family="'Space Grotesk',Arial,sans-serif" font-size="11" font-weight="700" fill="#06293a" letter-spacing="0.3">ACCELERATOR</text>
    {{-- cohort + marks --}}
    <text x="60" y="99" text-anchor="middle" font-family="'Space Grotesk',Arial,sans-serif" font-size="9" font-weight="700" fill="#3f3f46" letter-spacing="2">COHORT {{ $cohort }}</text>
    <circle cx="48" cy="110" r="3.5" fill="#06b6d4"/>
    <circle cx="60" cy="110" r="3.5" fill="#06b6d4"/>
    <circle cx="72" cy="110" r="3.5" fill="#06b6d4"/>
</svg>
