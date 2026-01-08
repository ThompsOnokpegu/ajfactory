<?php

use function Livewire\Volt\{state, mount, layout};
use App\Models\Enrollment;

// Explicitly tell Volt to use our new layout
layout('components.layouts.student');

state([
    'enrollment' => null,
    'status' => 'verifying', // verifying, success, processing, error
    'title' => 'Processing Enrollment...'
]);

mount(function () {
    $reference = request()->query('reference');

    if (!$reference) {
        $this->status = 'error';
        $this->title = 'Invalid Session';
        return;
    }

    $this->enrollment = Enrollment::where('payment_reference', $reference)->first();

    if (!$this->enrollment) {
        $this->status = 'error';
        $this->title = 'Transaction Not Found';
        return;
    }

    // Check if the Webhook has updated the DB yet
    if ($this->enrollment->status === 'paid') {
        $this->status = 'success';
        $this->title = 'Welcome to the Batch!';
    } else {
        $this->status = 'processing';
        $this->title = 'Verifying Payment...';
    }
});

?>

<div class="min-h-screen flex items-center justify-center p-6">
    <div class="max-w-xl w-full text-center">
        
        @if($status === 'success')
            <!-- SUCCESS STATE -->
            <div class="space-y-8 animate-in fade-in zoom-in duration-700">
                <div class="inline-flex h-24 w-24 items-center justify-center rounded-full bg-cyan-500/10 border border-cyan-500 shadow-[0_0_40px_rgba(6,182,212,0.3)]">
                    <svg class="h-12 w-12 text-cyan-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                
                <div class="space-y-2">
                    <h1 class="text-5xl font-black uppercase italic tracking-tighter text-white">Entry Granted.</h1>
                    <p class="text-zinc-500 font-mono text-xs uppercase tracking-[0.3em] glow-text-cyan">Deployment_Protocol_Active</p>
                </div>

                <div class="p-10 bg-zinc-900/40 border border-zinc-800 rounded-[2.5rem] text-left space-y-6 backdrop-blur-sm">
                    <p class="text-zinc-400 leading-relaxed">
                        Welcome, <strong class="text-white">{{ $enrollment->full_name }}</strong>. 
                        Your payment of <span class="text-white font-bold">N{{ number_format($enrollment->amount) }}</span> has been confirmed by the factory core.
                    </p>
                    
                    <div class="pt-6 border-t border-zinc-800 space-y-4">
                        <div class="flex items-start gap-4">
                            <div class="mt-1 h-5 w-5 rounded bg-cyan-500/20 flex items-center justify-center text-cyan-500 text-[10px] font-black">01</div>
                            <p class="text-xs text-zinc-500 leading-snug">Credentials for the <strong class="text-zinc-300 uppercase">Snapshot Vault</strong> have been sent to <span class="text-cyan-400">{{ $enrollment->email }}</span>.</p>
                        </div>
                        <div class="flex items-start gap-4">
                            <div class="mt-1 h-5 w-5 rounded bg-cyan-500/20 flex items-center justify-center text-cyan-500 text-[10px] font-black">02</div>
                            <p class="text-xs text-zinc-500 leading-snug">Join the <strong class="text-zinc-300 uppercase">Private Community</strong> via the link in your welcome email to start the 30-day roadmap.</p>
                        </div>
                    </div>
                </div>
                
                <a href="/dashboard" class="inline-block px-10 py-5 bg-white text-black font-black uppercase tracking-widest text-xs rounded-xl hover:bg-cyan-500 transition-all shadow-2xl shadow-white/5">
                    Enter Member Terminal
                </a>
            </div>

        @elseif($status === 'processing')
            <!-- PROCESSING STATE -->
            <div class="space-y-8 py-20">
                <div class="relative h-20 w-20 mx-auto">
                    <div class="absolute inset-0 rounded-full border-4 border-cyan-500/10"></div>
                    <div class="absolute inset-0 rounded-full border-4 border-t-cyan-500 animate-spin"></div>
                </div>
                <div class="space-y-2">
                    <h2 class="text-3xl font-black uppercase italic tracking-tighter text-white">Verifying Payment...</h2>
                    <p class="text-zinc-500 text-sm max-w-xs mx-auto leading-relaxed font-mono uppercase tracking-widest text-[10px]">Bank_Handshake_In_Progress</p>
                </div>
                <script>setTimeout(() => window.location.reload(), 4000);</script>
            </div>

        @else
            <!-- ERROR STATE -->
            <div class="space-y-8 py-20">
                <div class="h-20 w-20 bg-red-500/10 border border-red-500/50 flex items-center justify-center rounded-full mx-auto">
                    <svg class="w-10 h-10 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                </div>
                <div class="space-y-2">
                    <h2 class="text-3xl font-black uppercase italic tracking-tighter text-red-500">Handshake Interrupted.</h2>
                    <p class="text-zinc-500 text-sm max-w-xs mx-auto uppercase tracking-widest text-[10px]">Error: Session_Verification_Failed</p>
                </div>
                <div class="pt-4">
                    <a href="/checkout" class="px-6 py-3 border border-zinc-800 text-zinc-500 hover:text-white hover:border-zinc-500 transition-all uppercase font-black text-[10px] tracking-widest rounded-lg">Return to Checkout</a>
                </div>
            </div>
        @endif

    </div>
</div>