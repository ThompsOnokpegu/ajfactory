<?php

use Livewire\Volt\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

new class extends Component {
    public string $name = '';
    public string $email = '';
    public string $whatsapp = '';
    public string $interest = 'accelerator';
    public bool $joined = false;

    public function join()
    {
        $this->validate([
            'name' => 'required|min:2',
            'email' => 'required|email',
            'whatsapp' => 'required|min:10',
        ]);

        $email = strtolower(trim($this->email));

        // Dedupe by email — returning visitors just see the success state.
        if (DB::table('students')->where('email', $email)->doesntExist()) {
            DB::table('students')->insert([
                'name' => $this->name,
                'email' => $email,
                'whatsapp' => $this->whatsapp,
                'interest' => $this->interest,
                'source' => 'accelerator_waitlist',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $url = config('services.n8n.student_webhook_url');
            if ($url) {
                try {
                    Http::post($url, [
                        'type' => 'student_signup',
                        'name' => $this->name,
                        'email' => $email,
                        'whatsapp' => $this->whatsapp,
                        'interest' => $this->interest,
                        'source' => 'accelerator_waitlist',
                        'timestamp' => now()->toIso8601String(),
                    ]);
                } catch (\Throwable $e) {
                    Log::error('Waitlist webhook failed: ' . $e->getMessage());
                }
            }
        }

        $this->joined = true;
    }
}; ?>

<div class="relative w-full max-w-2xl mx-auto py-12 lg:py-20">

    <!-- SECTION HEADER -->
    <div class="text-center mb-12">
        <span class="inline-block py-1 px-3 rounded-full bg-cyan-900/30 border border-cyan-500/30 text-cyan-400 text-[10px] font-mono uppercase tracking-widest mb-4">
            Accelerator · Waitlist
        </span>
        <h2 class="text-4xl md:text-5xl font-black text-white uppercase italic tracking-tighter">
            The next cohort <span class="text-transparent bg-clip-text bg-gradient-to-r from-cyan-400 to-blue-500">opens soon.</span>
        </h2>
        <p class="text-zinc-400 mt-6 max-w-lg mx-auto text-lg leading-relaxed">
            Seats are capped and the current cohort is closed. Join the waitlist to get notified first — and lock in early-bird pricing — the moment enrolment reopens.
        </p>
    </div>

    <!-- THE FORM -->
    <div class="bg-zinc-900/50 border border-zinc-800 p-4 rounded-xl max-w-lg mx-auto shadow-2xl shadow-cyan-900/20">
        @if($joined)
            <div class="w-full py-4 text-center text-cyan-400 font-bold uppercase tracking-widest animate-pulse">
                You're on the list. Check your email.
            </div>
        @else
            <div class="flex flex-col gap-3">
                <input type="text" wire:model="name" placeholder="Full Name"
                    class="w-full bg-zinc-950 border-zinc-800 text-white text-sm rounded-lg focus:ring-cyan-500 focus:border-cyan-500 block p-3 placeholder-zinc-600 font-mono">
                @error('name') <span class="text-[10px] text-red-500 ml-1">{{ $message }}</span> @enderror

                <input type="email" wire:model="email" placeholder="Email Address"
                    class="w-full bg-zinc-950 border-zinc-800 text-white text-sm rounded-lg focus:ring-cyan-500 focus:border-cyan-500 block p-3 placeholder-zinc-600 font-mono">
                @error('email') <span class="text-[10px] text-red-500 ml-1">{{ $message }}</span> @enderror

                <input type="text" wire:model="whatsapp" placeholder="WhatsApp Number"
                    class="w-full bg-zinc-950 border-zinc-800 text-white text-sm rounded-lg focus:ring-cyan-500 focus:border-cyan-500 block p-3 placeholder-zinc-600 font-mono">
                @error('whatsapp') <span class="text-[10px] text-red-500 ml-1">{{ $message }}</span> @enderror

                <button wire:click="join" wire:loading.attr="disabled"
                    class="w-full bg-cyan-600 hover:bg-cyan-500 text-white font-black uppercase tracking-wider text-xs py-4 rounded-lg transition-all shadow-lg shadow-cyan-600/20 mt-2 disabled:opacity-50">
                    <span wire:loading.remove wire:target="join">Join the Waitlist</span>
                    <span wire:loading wire:target="join">Adding you…</span>
                </button>
                <p class="text-[11px] text-zinc-600 text-center mt-1">No spam. We'll only email you about the Accelerator.</p>
            </div>
        @endif
    </div>

    <!-- SECONDARY: what is this? -->
    <div class="text-center mt-10">
        <a href="/accelerator" class="text-[11px] font-mono uppercase tracking-widest text-zinc-500 hover:text-cyan-400 transition">
            New here? See what's inside the Accelerator →
        </a>
    </div>

    <!-- SOCIAL PROOF -->
    <div class="flex justify-center items-center gap-3 mt-8 opacity-50 grayscale hover:grayscale-0 transition-all">
        <div class="text-[10px] text-zinc-500 font-mono uppercase tracking-widest">As seen on TikTok</div>
        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64 2.93 2.93 0 0 1 .88.13V9.4a6.84 6.84 0 0 0-1-.05A6.33 6.33 0 0 0 5 20.1a6.34 6.34 0 0 0 10.86-4.43v-7a8.16 8.16 0 0 0 4.77 1.52v-3.4a4.85 4.85 0 0 1-1-.1z"/></svg>
    </div>
</div>
