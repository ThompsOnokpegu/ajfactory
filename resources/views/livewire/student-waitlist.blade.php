<?php

use Livewire\Volt\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

new class extends Component {
    public string $name = '';
    public string $email = '';
    public string $whatsapp = '';
    public string $interest = 'course';
    public bool $joined = false;

    public function join()
    {
        $this->validate([
            'name' => 'required|min:2',
            'email' => 'required|email',
            'whatsapp' => 'required|min:10',
        ]);

        // Check if student already exists to prevent duplicates
        if (DB::table('students')->where('email', $this->email)->doesntExist()) {
            // 1. Save to Database
            DB::table('students')->insert([
                'name' => $this->name,
                'email' => $this->email,
                'whatsapp' => $this->whatsapp, // Ensure you run a migration for this column
                'interest' => $this->interest,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 2. Trigger n8n Webhook for Email Confirmation
            try {
                // Using a specific student webhook URL or the main one with a 'type'
                Http::post(config('services.n8n.student_webhook_url', env('N8N_STUDENT_WEBHOOK_URL')), [
                    'type' => 'student_signup',
                    'name' => $this->name,
                    'email' => $this->email,
                    'whatsapp' => $this->whatsapp,
                    'interest' => $this->interest,
                    'timestamp' => now()->toIso8601String(),
                ]);
            } catch (\Exception $e) {
                // Log error but allow UI to show success
                \Log::error('Student webhook failed: ' . $e->getMessage());
            }
        }

        $this->joined = true;
    }
}; ?>

<div class="relative w-full max-w-2xl mx-auto py-12 lg:py-20">
    
    <!-- SECTION HEADER -->
    <div class="text-center mb-12">
        <span class="inline-block py-1 px-3 rounded-full bg-cyan-900/30 border border-cyan-500/30 text-cyan-400 text-[10px] font-mono uppercase tracking-widest mb-4">
            For Developers & Builders
        </span>
        <h2 class="text-4xl md:text-5xl font-black text-white uppercase italic tracking-tighter">
            Want to build <span class="text-transparent bg-clip-text bg-gradient-to-r from-cyan-400 to-blue-500">systems like this?</span>
        </h2>
        <p class="text-zinc-400 mt-6 max-w-lg mx-auto text-lg leading-relaxed">
            I'm documenting the exact stack (Laravel + n8n + Vapi) I use to scale service businesses. Join the list for the upcoming workshop & community.
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
                <select wire:model="interest" class="w-full bg-zinc-950 border-zinc-800 text-zinc-400 text-sm rounded-lg focus:ring-cyan-500 focus:border-cyan-500 block p-3">
                    <option value="course">I want a Course</option>
                    <option value="mentorship">1-on-1 Mentorship</option>
                    <option value="community">Community Access</option>
                </select>
                
                <input type="email" wire:model="email" placeholder="Email Address" 
                    class="w-full bg-zinc-950 border-zinc-800 text-white text-sm rounded-lg focus:ring-cyan-500 focus:border-cyan-500 block p-3 placeholder-zinc-600 font-mono">
                @error('email') <span class="text-[10px] text-red-500 ml-1">{{ $message }}</span> @enderror

                <input type="text" wire:model="whatsapp" placeholder="WhatsApp Number" 
                    class="w-full bg-zinc-950 border-zinc-800 text-white text-sm rounded-lg focus:ring-cyan-500 focus:border-cyan-500 block p-3 placeholder-zinc-600 font-mono">
                @error('whatsapp') <span class="text-[10px] text-red-500 ml-1">{{ $message }}</span> @enderror
                <input type="text" wire:model="name" placeholder="Full Name" 
                    class="w-full bg-zinc-950 border-zinc-800 text-white text-sm rounded-lg focus:ring-cyan-500 focus:border-cyan-500 block p-3 placeholder-zinc-600 font-mono">
                @error('name') <span class="text-[10px] text-red-500 ml-1">{{ $message }}</span> @enderror
                
                <button wire:click="join" class="w-full bg-cyan-600 hover:bg-cyan-500 text-white font-black uppercase tracking-wider text-xs py-4 rounded-lg transition-all shadow-lg shadow-cyan-600/20 mt-2">
                    Join Waitlist
                </button>
            </div>
        @endif
    </div>

    <!-- SOCIAL PROOF -->
    <div class="flex justify-center items-center gap-6 mt-12 opacity-50 grayscale hover:grayscale-0 transition-all">
        <div class="text-[10px] text-zinc-500 font-mono uppercase tracking-widest">
            As seen on TikTok
        </div>
        <!-- Simple Tikok Icon representation -->
        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64 2.93 2.93 0 0 1 .88.13V9.4a6.84 6.84 0 0 0-1-.05A6.33 6.33 0 0 0 5 20.1a6.34 6.34 0 0 0 10.86-4.43v-7a8.16 8.16 0 0 0 4.77 1.52v-3.4a4.85 4.85 0 0 1-1-.1z"/></svg>
    </div>
</div>