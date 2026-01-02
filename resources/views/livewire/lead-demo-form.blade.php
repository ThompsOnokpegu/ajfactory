<?php

use Livewire\Volt\Component;
use App\Models\Lead;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;

new class extends Component {
    public string $name = '';
    public string $phone = '';
    public string $business_name = '';
    public string $email = '';
    public string $preference = 'voice'; 
    public string $volume = '';
    public string $statusMessage = '';
    public bool $isProcessing = false;

    public function submit()
    {
        // 1. Validate Inputs
        $this->validate([
            'name' => ['required', 'min:2'],
            'business_name' => ['required', 'min:2'],
            'email' => ['required', 'email'],
            'phone' => ['required', 'min:10'],
            'volume' => ['required'],
        ]);

        $this->isProcessing = true;
        $this->statusMessage = "Initializing Factory Protocol...";

        try {
            // 2. Save Lead to Database
            $lead = Lead::create([
                'name' => $this->name,
                'business_name' => $this->business_name,
                'email' => $this->email,
                'phone' => $this->phone,
                'contact_preference' => $this->preference,
                'estimated_leads_per_month' => $this->volume,
                'status' => 'initiated'
            ]);

            // 3. Trigger n8n Webhook
            // We use a timeout to prevent the UI from hanging if n8n is slow
            $response = Http::timeout(5)->post(config('services.n8n.webhook_url'), [
                'lead_id' => $lead->id,
                'name' => $this->name,
                'business_name' => $this->business_name,
                'email' => $this->email,
                'phone' => $this->phone,
                'preference' => $this->preference,
                'volume' => $this->volume,
                'source' => 'website_form'
            ]);

            // 4. Handle Success
            if ($response->successful()) {
                $this->statusMessage = $this->preference === 'voice' 
                    ? "RECEPTION CONFIRMED. Stand by for incoming call." 
                    : "PROTOCOL OPENED. Check your WhatsApp now.";
                
                // Optional: Reset form fields
                $this->reset(['name', 'business_name', 'email', 'phone', 'volume']);
            } else {
                // Handle n8n error (e.g. 500 or 404)
                $this->statusMessage = "System Busy. Your lead has been queued manually.";
            }

        } catch (\Exception $e) {
            // Handle connection failure
            $this->statusMessage = "Connection Interrupted. Re-attempting...";
            Log::error($e);
            // In production, you might log this error: \Log::error($e);
        }

        $this->isProcessing = false;
    }
}; ?>

<div class="relative group max-w-md mx-auto">
    <!-- BACKGROUND GLOW EFFECT -->
    <div class="absolute -inset-1 bg-gradient-to-r from-purple-600 via-orange-500 to-purple-600 rounded-2xl blur-sm opacity-25 group-hover:opacity-50 transition duration-1000 animate-pulse"></div>
    
    <!-- MAIN FORM CONTAINER -->
    <div class="relative bg-zinc-950 p-8 rounded-2xl border border-zinc-800 shadow-[0_25px_50px_rgba(0,0,0,0.8)]">
        
        <!-- STATUS MESSAGE DISPLAY -->
        @if ($statusMessage)
            <div class="mb-6 p-4 rounded bg-zinc-900 border-l-4 border-orange-500 text-orange-400 font-mono text-xs animate-pulse">
                > {{ $statusMessage }}
            </div>
        @endif

        <form wire:submit.prevent="submit" class="space-y-4">
            <!-- Full Name -->
            <div class="space-y-1">
                <input type="text" wire:model="name" placeholder="Contact Name" 
                    class="w-full bg-zinc-900 border-zinc-800 text-white placeholder-zinc-700 rounded-lg p-3 text-sm focus:border-orange-500 focus:ring-0 transition-colors">
                @error('name') <span class="text-[10px] text-red-500 font-bold uppercase tracking-tighter">{{ $message }}</span> @enderror
            </div>

            <!-- Business/Clinic Name -->
            <div class="space-y-1">
                <input type="text" wire:model="business_name" placeholder="Clinic / Business Name" 
                    class="w-full bg-zinc-900 border-zinc-800 text-white placeholder-zinc-700 rounded-lg p-3 text-sm focus:border-orange-500 focus:ring-0 transition-colors">
                @error('business_name') <span class="text-[10px] text-red-500 font-bold uppercase tracking-tighter">{{ $message }}</span> @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Email Address -->
                <div class="space-y-1">
                    <input type="email" wire:model="email" placeholder="Email Address" 
                        class="w-full bg-zinc-900 border-zinc-800 text-white placeholder-zinc-700 rounded-lg p-3 text-sm focus:border-orange-500 focus:ring-0 transition-colors">
                    @error('email') <span class="text-[10px] text-red-500 font-bold uppercase tracking-tighter">{{ $message }}</span> @enderror
                </div>
                <!-- Phone -->
                <div class="space-y-1">
                    <input type="text" wire:model="phone" placeholder="Mobile Number" 
                        class="w-full bg-zinc-900 border-zinc-900 text-white placeholder-zinc-700 rounded-lg p-3 text-sm focus:border-orange-500 focus:ring-0 transition-colors">
                    @error('phone') <span class="text-[10px] text-red-500 font-bold uppercase tracking-tighter">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="space-y-1">
                <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-zinc-600 mb-2 ml-1">Current Monthly Lead Volume</label>
                <select wire:model="volume" class="w-full bg-zinc-900 border-zinc-800 text-white rounded-lg p-3 text-sm focus:border-purple-500 focus:ring-0 transition-colors cursor-pointer appearance-none">
                    <option value="">Select Scale...</option>
                    <option value="1-20">1-20 (Getting Started)</option>
                    <option value="21-100">21-100 (Scaling Clinic)</option>
                    <option value="100+">100+ (High Velocity)</option>
                </select>
                @error('volume') <span class="text-[10px] text-red-500 font-bold uppercase tracking-tighter">{{ $message }}</span> @enderror
            </div>

            <div class="bg-zinc-900/50 p-1 rounded-xl flex gap-1 border border-zinc-800/50 mt-2">
                <button type="button" 
                    wire:click="$set('preference', 'voice')"
                    class="flex-1 py-2.5 px-4 rounded-lg text-[10px] font-black uppercase tracking-widest transition-all {{ $preference === 'voice' ? 'bg-orange-500 text-black shadow-lg shadow-orange-500/20' : 'text-zinc-500 hover:text-zinc-300' }}">
                    Voice Call
                </button>
                <button type="button" 
                    wire:click="$set('preference', 'whatsapp')"
                    class="flex-1 py-2.5 px-4 rounded-lg text-[10px] font-black uppercase tracking-widest transition-all {{ $preference === 'whatsapp' ? 'bg-green-600 text-white shadow-lg shadow-green-600/20' : 'text-zinc-500 hover:text-zinc-300' }}">
                    WhatsApp
                </button>
            </div>

            <button type="submit" wire:loading.attr="disabled" 
                class="group relative w-full bg-white text-black font-black py-4 rounded-lg overflow-hidden transition-all hover:bg-orange-500 hover:text-white mt-4">
                <div class="relative z-10 flex items-center justify-center gap-2">
                    <span wire:loading.remove class="uppercase tracking-tighter text-lg">Initiate Factory Audit</span>
                    <span wire:loading class="uppercase tracking-tighter text-lg animate-pulse italic">Connecting Core...</span>
                </div>
            </button>
        </form>
        
        <!-- FOOTER BRANDING -->
        <p class="mt-6 text-[9px] text-zinc-700 text-center uppercase tracking-[0.4em] font-black">
            System Core: Laravel + n8n + Vapi
        </p>
    </div>
</div>