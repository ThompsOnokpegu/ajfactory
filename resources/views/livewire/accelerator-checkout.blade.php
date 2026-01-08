<?php

use Livewire\Volt\Component;
use App\Models\Enrollment;
use Illuminate\Support\Facades\DB;

new class extends Component {
    public string $email = '';
    public string $full_name = '';
    public string $whatsapp = '';
    public int $amount = 150000; // Default Price
    public bool $isWaitlisted = false;
    public string $statusMessage = '';

    protected $rules = [
        'full_name' => 'required|string|max:255',
        'email' => 'required|email',
        'whatsapp' => 'nullable|string|min:10',
    ];

    /**
     * Listen for email updates to check waitlist status
     */
    public function updatedEmail($value)
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) return;

        $student = DB::table('students')->where('email', $value)->first();

        if ($student) {
            $this->isWaitlisted = true;
            $this->amount = 120000; // Apply N30,000 Discount
            $this->whatsapp = $student->whatsapp ?? '';
            // Note: If you captured names on the waitlist, prefill here too
            $this->full_name = $student->name ?? '';
        } else {
            $this->isWaitlisted = false;
            $this->amount = 150000;
        }
    }

    public function initiatePayment()
    {
        $this->validate();

        try {
            $reference = 'ACC_' . bin2hex(random_bytes(8));

            // Save enrollment with the calculated amount (130k or 150k)
            Enrollment::create([
                'full_name' => $this->full_name,
                'email' => $this->email,
                'whatsapp' => $this->whatsapp,
                'payment_reference' => $reference,
                'amount' => $this->amount,
                'status' => 'pending',
            ]);

            $this->dispatch('launch-paystack', [
                'email' => $this->email,
                'amount' => $this->amount * 100, // Paystack uses Kobo
                'reference' => $reference,
                'key' => config('services.paystack.public_key'),
                'metadata' => [
                    'full_name' => $this->full_name,
                    'whatsapp' => $this->whatsapp,
                    'waitlisted' => $this->isWaitlisted
                ]
            ]);

        } catch (\Exception $e) {
            $this->statusMessage = "Factory handshake failed. Try again.";
            \Log::error($e->getMessage());
        }
    }
}; ?>

<div class="flex flex-col lg:flex-row max-w-6xl mx-auto w-full">
    <!-- LEFT: ENROLLMENT FORM -->
    <div class="flex-1 p-8 lg:p-16 border-r border-zinc-900">
        <div class="max-w-md mx-auto">
            <h1 class="text-4xl font-black text-white uppercase italic tracking-tighter mb-2">Join the Batch.</h1>
            
            @if($isWaitlisted)
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded bg-cyan-500/10 border border-cyan-500/30 text-cyan-400 text-[10px] font-black uppercase tracking-widest mb-6 animate-bounce">
                    ✨ Waitlist Discount Applied: -N30,000
                </div>
            @else
                <p class="text-zinc-500 mb-10 text-sm font-medium uppercase tracking-wider">Waitlist Member Discount: <span class="text-cyan-500">Active</span></p>
            @endif
            
            <form wire:submit.prevent="initiatePayment" class="space-y-6">
                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase text-zinc-600 tracking-widest ml-1">Email Address</label>
                    <input type="email" wire:model.blur="email" required 
                           class="w-full bg-zinc-900 border border-zinc-800 text-white p-4 rounded-xl focus:border-cyan-500 focus:ring-0 transition-all font-medium placeholder:text-zinc-800" 
                           placeholder="john@example.com">
                    @error('email') <span class="text-[10px] text-red-500 uppercase font-bold">{{ $message }}</span> @enderror
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase text-zinc-600 tracking-widest ml-1">Full Name</label>
                    <input type="text" wire:model="full_name" required 
                           class="w-full bg-zinc-900 border border-zinc-800 text-white p-4 rounded-xl focus:border-cyan-500 focus:ring-0 transition-all font-medium placeholder:text-zinc-800" 
                           placeholder="John Doe">
                    @error('full_name') <span class="text-[10px] text-red-500 uppercase font-bold">{{ $message }}</span> @enderror
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-black uppercase text-zinc-600 tracking-widest ml-1">WhatsApp Number</label>
                    <input type="text" wire:model="whatsapp" 
                           class="w-full bg-zinc-900 border border-zinc-800 text-white p-4 rounded-xl focus:border-cyan-500 focus:ring-0 transition-all font-medium placeholder:text-zinc-800" 
                           placeholder="+234...">
                    @error('whatsapp') <span class="text-[10px] text-red-500 uppercase font-bold">{{ $message }}</span> @enderror
                </div>

                @if($statusMessage)
                    <div class="p-3 bg-red-900/20 border border-red-500/50 text-red-500 text-xs font-mono">
                        {{ $statusMessage }}
                    </div>
                @endif

                <div class="pt-6">
                    <button type="submit" wire:loading.attr="disabled" 
                            class="w-full py-5 bg-cyan-500 text-black font-black uppercase text-xl rounded-2xl hover:bg-white transition-all shadow-xl shadow-cyan-500/10 disabled:opacity-50">
                        <span wire:loading.remove>Initiate Deployment</span>
                        <span wire:loading>Syncing with Core...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- RIGHT: ORDER SUMMARY -->
    <div class="w-full lg:w-[450px] p-8 lg:p-16 bg-zinc-900/10">
        <div class="sticky top-32">
            <h2 class="text-xs font-black uppercase tracking-[0.3em] text-zinc-600 mb-8">Summary // Cohort 001</h2>
            <div class="space-y-6">
                <div class="flex justify-between items-start">
                    <div>
                        <div class="text-lg font-bold text-white uppercase italic tracking-tighter">Automation Accelerator</div>
                        <div class="text-[10px] text-zinc-500 uppercase mt-1">Full Foundry Access</div>
                    </div>
                    <div class="text-right">
                        @if($isWaitlisted)
                            <div class="text-xs text-zinc-500 line-through">N150,000</div>
                            <div class="text-xl font-black text-cyan-400">N120,000</div>
                        @else
                            <div class="text-xl font-black text-white">N150,000</div>
                        @endif
                    </div>
                </div>

                <div class="space-y-4 pt-8 border-t border-zinc-900">
                    <div class="flex items-center gap-3 text-xs text-zinc-400">
                        <svg class="w-4 h-4 text-cyan-500" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/></svg>
                        n8n Snapshot Vault
                    </div>
                    <div class="flex items-center gap-3 text-xs text-zinc-400 font-bold">
                        <svg class="w-4 h-4 text-cyan-500" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/></svg>
                        Waitlist Member Discount
                    </div>
                    <div class="flex items-center gap-3 text-xs text-zinc-400">
                        <svg class="w-4 h-4 text-cyan-500" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/></svg>
                        Private Community Access
                    </div>
                    <div class="flex items-center gap-3 text-xs text-zinc-400">
                        <svg class="w-4 h-4 text-cyan-500" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/></svg>
                        Lifetime Strategy Updates
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- PAYSTACK JS HANDLER -->
    <script src="https://js.paystack.co/v1/inline.js"></script>
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('launch-paystack', (event) => {
                const config = event[0];
                const handler = PaystackPop.setup({
                    key: config.key,
                    email: config.email,
                    amount: config.amount,
                    currency: "NGN",
                    ref: config.reference,
                    metadata: config.metadata,
                    callback: function(res) {
                        window.location.href = '/thank-you?reference=' + config.reference;
                    }
                });
                handler.openIframe();
            });
        });
    </script>
</div>