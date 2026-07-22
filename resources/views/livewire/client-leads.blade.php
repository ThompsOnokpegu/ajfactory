<?php

use Livewire\Volt\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Captures the "how do I earn / get clients with AI automation" audience into
 * the students CRM, tagged interest=earn / source=clients so they segment
 * cleanly from masterclass and accelerator-waitlist leads. Same dedupe + n8n
 * pattern as student-waitlist. Funnels toward the free masterclass (clarity
 * first) then the Accelerator (the program that actually teaches this).
 */
new class extends Component {
    public string $name = '';
    public string $email = '';
    public string $whatsapp = '';
    public bool $joined = false;

    public function join(): void
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
                'interest' => 'earn',
                'source' => 'clients',
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
                        'interest' => 'earn',
                        'source' => 'clients',
                        'timestamp' => now()->toIso8601String(),
                    ]);
                } catch (\Throwable $e) {
                    Log::error('Client-leads webhook failed: ' . $e->getMessage());
                }
            }
        }

        $this->joined = true;
    }
}; ?>

<div class="relative w-full max-w-xl mx-auto py-10 lg:py-16">

    @if($joined)
        <!-- SUCCESS STATE -->
        <div class="text-center">
            <div class="w-16 h-16 mx-auto rounded-2xl bg-cyan-500/10 border border-cyan-500/30 flex items-center justify-center mb-6">
                <svg class="w-8 h-8 text-cyan-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </div>
            <h2 class="text-3xl md:text-4xl font-black text-white uppercase italic tracking-tighter">You're on the list.</h2>
            <p class="text-zinc-400 mt-4 max-w-md mx-auto leading-relaxed">
                Check your email and WhatsApp — the first thing I'll send is the free masterclass, so you can see the real path before committing to anything.
            </p>
            <a href="/taab" class="inline-block mt-8 px-6 py-3 rounded-xl bg-cyan-500 text-black font-black uppercase tracking-widest text-xs hover:bg-white transition">
                Watch the free masterclass →
            </a>
        </div>
    @else
        <!-- SECTION HEADER -->
        <div class="text-center mb-8">
            <span class="inline-block py-1 px-3 rounded-full bg-cyan-900/30 border border-cyan-500/30 text-cyan-400 text-[10px] font-mono uppercase tracking-widest mb-4">
                AI Automation · Get Paid
            </span>
            <h2 class="text-4xl md:text-5xl font-black text-white uppercase italic tracking-tighter leading-[0.95]">
                Get <span class="text-transparent bg-clip-text bg-gradient-to-r from-cyan-400 to-blue-500">paid</span> to build<br>AI automations.
            </h2>
            <p class="text-zinc-400 mt-5 max-w-md mx-auto leading-relaxed">
                The skill is learnable and businesses are already paying for it — no coding, no big following. Drop your details and I'll show you the exact path from zero to your first paying client.
            </p>
        </div>

        <!-- FORM -->
        <form wire:submit="join" class="space-y-3 bg-zinc-900/60 border border-zinc-800 rounded-2xl p-5 sm:p-6">
            <div>
                <input wire:model="name" type="text" placeholder="Your name"
                       class="w-full bg-zinc-950 border border-zinc-800 text-white px-4 py-3 rounded-xl text-sm focus:border-cyan-500 focus:ring-0 placeholder:text-zinc-600">
                @error('name') <span class="text-[10px] text-red-500 uppercase font-bold">{{ $message }}</span> @enderror
            </div>
            <div>
                <input wire:model="email" type="email" placeholder="you@email.com"
                       class="w-full bg-zinc-950 border border-zinc-800 text-white px-4 py-3 rounded-xl text-sm focus:border-cyan-500 focus:ring-0 placeholder:text-zinc-600">
                @error('email') <span class="text-[10px] text-red-500 uppercase font-bold">{{ $message }}</span> @enderror
            </div>
            <div>
                <input wire:model="whatsapp" type="text" placeholder="WhatsApp number"
                       class="w-full bg-zinc-950 border border-zinc-800 text-white px-4 py-3 rounded-xl text-sm focus:border-cyan-500 focus:ring-0 placeholder:text-zinc-600">
                @error('whatsapp') <span class="text-[10px] text-red-500 uppercase font-bold">{{ $message }}</span> @enderror
            </div>
            <button type="submit" wire:loading.attr="disabled"
                    class="w-full px-6 py-3.5 rounded-xl bg-cyan-500 text-black font-black uppercase tracking-widest text-xs hover:bg-white transition disabled:opacity-60">
                <span wire:loading.remove wire:target="join">Show me the path →</span>
                <span wire:loading wire:target="join">Sending…</span>
            </button>
            <p class="text-[11px] text-zinc-600 text-center leading-relaxed pt-1">
                No hype, no shortcuts. It's a real skill — I'll show you how to learn it and get paid for it.
            </p>
        </form>
    @endif
</div>
