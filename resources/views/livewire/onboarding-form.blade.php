<?php

use Livewire\Volt\Component;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

new class extends Component {
    public string $name = '';
    public string $email = '';
    public string $age_group = '';
    public string $experience = '';
    public string $expectation = '';
    
    public string $statusMessage = '';
    public bool $isSuccess = false;

    public function submit()
    {
        $this->validate([
            'name' => 'required|string|min:2',
            'email' => 'required|email',
            'age_group' => 'required|in:Teen,Adult',
            'experience' => 'required|string',
            'expectation' => 'required|string|min:10',
        ]);

        $this->statusMessage = "Transmitting profile to Factory Core...";

        try {
            // We use a fallback to env() just in case it isn't registered in config/services.php yet
            $webhookUrl = config('services.n8n.coaching_webhook_url', env('N8N_COACHING_WEBHOOK_URL'));
            
            if (!$webhookUrl) {
                throw new \Exception("N8N_COACHING_WEBHOOK_URL is not set in .env");
            }

            $response = Http::timeout(10)->post($webhookUrl, [
                'type' => 'coaching_onboarding',
                'name' => $this->name,
                'email' => $this->email,
                'age_group' => $this->age_group,
                'experience' => $this->experience,
                'expectation' => $this->expectation,
                'timestamp' => now()->toIso8601String(),
            ]);

            if ($response->successful()) {
                $this->isSuccess = true;
                $this->statusMessage = "PROFILE SYNCED. Welcome to the Inner Circle.";
                $this->reset(['name', 'email', 'age_group', 'experience', 'expectation']);
            } else {
                $this->statusMessage = "Handshake failed. Please try again.";
            }

        } catch (\Exception $e) {
            $this->statusMessage = "Connection Interrupted. " . ($e->getMessage() === "N8N_COACHING_WEBHOOK_URL is not set in .env" ? "System misconfiguration." : "Please try again.");
            Log::error('Coaching Onboarding Error: ' . $e->getMessage());
        }
    }
}; ?>

<div class="relative group max-w-2xl mx-auto w-full">
    <!-- BACKGROUND GLOW EFFECT -->
    <div class="absolute -inset-1 bg-gradient-to-r from-cyan-600 via-blue-500 to-cyan-600 rounded-2xl blur opacity-20 group-hover:opacity-40 transition duration-1000"></div>
    
    <!-- MAIN FORM CONTAINER -->
    <div class="relative bg-zinc-950 p-8 lg:p-12 rounded-2xl border border-zinc-800 shadow-2xl">
        
        <div class="mb-8">
            <div class="inline-block px-3 py-1 rounded bg-cyan-500/10 border border-cyan-500/20 text-[10px] font-mono text-cyan-500 uppercase tracking-[0.3em] mb-4">
                Secure_Intake_Protocol
            </div>
            <h2 class="text-3xl font-black text-white uppercase italic tracking-tighter">
                Private <span class="text-cyan-500">Coaching Intake</span>
            </h2>
            <p class="text-zinc-400 mt-2 text-sm leading-relaxed">
                Complete your profile to initialize your customized roadmap. This data is synced directly to AJ's terminal.
            </p>
        </div>

        @if ($isSuccess)
            <div class="p-8 text-center bg-cyan-500/10 border border-cyan-500/30 rounded-xl space-y-4 animate-in fade-in zoom-in duration-500">
                <div class="w-16 h-16 bg-cyan-500/20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-cyan-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                </div>
                <h3 class="text-xl font-black text-white uppercase tracking-tighter italic">Data Received.</h3>
                <p class="text-sm text-cyan-400 font-mono">{{ $statusMessage }}</p>
                <p class="text-xs text-zinc-500 uppercase tracking-widest mt-4">Check your email inbox shortly.</p>
            </div>
        @else
            @if ($statusMessage)
                <div class="mb-6 p-4 rounded bg-zinc-900 border-l-4 border-red-500 text-red-400 font-mono text-xs animate-pulse">
                    > {{ $statusMessage }}
                </div>
            @endif

            <form wire:submit="submit" class="space-y-6">
                
                <!-- IDENTITY INPUTS -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase text-zinc-500 tracking-widest ml-1">Full Name</label>
                        <input type="text" wire:model.blur="name" 
                               class="w-full bg-zinc-900 border border-zinc-800 text-white placeholder-zinc-700 rounded-xl p-4 text-sm focus:border-cyan-500 focus:ring-0 transition-all"
                               placeholder="e.g. John Doe">
                        @error('name') <span class="text-[10px] text-red-500 font-black uppercase tracking-widest">{{ $message }}</span> @enderror
                    </div>
                    
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase text-zinc-500 tracking-widest ml-1">Identity (Email)</label>
                        <input type="email" wire:model.blur="email" 
                               class="w-full bg-zinc-900 border border-zinc-800 text-white placeholder-zinc-700 rounded-xl p-4 text-sm focus:border-cyan-500 focus:ring-0 transition-all font-mono"
                               placeholder="deploy@factory.io">
                        @error('email') <span class="text-[10px] text-red-500 font-black uppercase tracking-widest">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- AGE GROUP -->
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase text-zinc-500 tracking-widest ml-1">Age Category</label>
                        <div class="relative">
                            <select wire:model="age_group"
                                    class="w-full bg-zinc-900 border border-zinc-800 text-white rounded-xl p-4 text-sm focus:border-cyan-500 focus:ring-0 transition-all cursor-pointer appearance-none">
                                <option value="">Select Bracket...</option>
                                <option value="Teen">Teen (Under 18)</option>
                                <option value="Adult">Adult (18+)</option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-cyan-500">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            </div>
                        </div>
                        @error('age_group') <span class="text-[10px] text-red-500 font-black uppercase tracking-widest">{{ $message }}</span> @enderror
                    </div>

                    <!-- EXPERIENCE LEVEL -->
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase text-zinc-500 tracking-widest ml-1">AI Automation Experience</label>
                        <div class="relative">
                            <select wire:model="experience"
                                    class="w-full bg-zinc-900 border border-zinc-800 text-white rounded-xl p-4 text-sm focus:border-cyan-500 focus:ring-0 transition-all cursor-pointer appearance-none">
                                <option value="">Select Level...</option>
                                <option value="None">None (Starting from zero)</option>
                                <option value="Beginner">Beginner (Used ChatGPT/Basic Zapier)</option>
                                <option value="Intermediate">Intermediate (Built in n8n/Make)</option>
                                <option value="Advanced">Advanced (Custom API integrations)</option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-cyan-500">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            </div>
                        </div>
                        @error('experience') <span class="text-[10px] text-red-500 font-black uppercase tracking-widest">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- EXPECTATIONS -->
                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase text-zinc-500 tracking-widest ml-1">Primary Objective / Expectation</label>
                    <textarea wire:model.blur="expectation" rows="4"
                              class="w-full bg-zinc-900 border border-zinc-800 text-white placeholder-zinc-700 rounded-xl p-4 text-sm focus:border-cyan-500 focus:ring-0 transition-all resize-none"
                              placeholder="What is your #1 goal for this coaching program?"></textarea>
                    @error('expectation') <span class="text-[10px] text-red-500 font-black uppercase tracking-widest">{{ $message }}</span> @enderror
                </div>

                <!-- SUBMIT ACTION -->
                <div class="pt-4">
                    <button type="submit" 
                            wire:loading.attr="disabled" 
                            class="w-full relative group/btn bg-white text-black font-black py-4 rounded-xl overflow-hidden transition-all hover:bg-cyan-500 hover:text-white disabled:opacity-50 shadow-xl shadow-cyan-500/5">
                        <div class="relative z-10 flex items-center justify-center gap-2">
                            <span wire:loading.remove class="uppercase tracking-tighter text-lg">Transmit Profile</span>
                            <span wire:loading class="uppercase tracking-tighter text-lg animate-pulse italic">Connecting Factory...</span>
                        </div>
                    </button>
                </div>
            </form>
        @endif
        
        <!-- FOOTER BRANDING -->
        <p class="mt-8 text-[9px] text-zinc-700 text-center uppercase tracking-[0.4em] font-black border-t border-zinc-900 pt-6">
            Intake System: Operational // AES-256 Encrypted
        </p>
    </div>
</div>