<?php

use Livewire\Volt\Component;
use App\Models\Enrollment;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

new class extends Component {
    public string $email = '';
    public string $full_name = '';
    public string $whatsapp = '';
    
    // Currency State
    public string $currency = 'NGN'; 
    public float $amount = 79000; 
    
    public bool $isWaitlisted = false;
    public string $statusMessage = '';

    // Flash Sale State
    public string $deadline = '2026-03-18 23:59:59'; // SET YOUR DEADLINE HERE (Format: YYYY-MM-DD HH:MM:SS)
    public bool $isDiscountActive = false;

    protected $rules = [
        'full_name' => 'required|string|max:255',
        'email' => 'required|email',
        'whatsapp' => 'nullable|string|min:10',
    ];

    public function mount()
    {
        $this->checkDiscount();
        $this->recalculatePrice();
    }

    public function checkDiscount()
    {
        // Server-side check using African/Lagos timezone
        $now = Carbon::now('Africa/Lagos');
        $deadlineDate = Carbon::parse($this->deadline, 'Africa/Lagos');
        $this->isDiscountActive = false;//$now->lt($deadlineDate);
    }

    // Called by AlpineJS when timer hits zero
    public function expireDiscount()
    {
        $this->isDiscountActive = false;
        $this->recalculatePrice();
    }

    public function updatedCurrency($value)
    {
        $this->recalculatePrice();
    }

    public function updatedEmail($value)
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) return;

        $student = DB::table('students')->where('email', $value)->first();

        if ($student) {
            $this->isWaitlisted = true;
            $this->whatsapp = $student->whatsapp ?? $this->whatsapp;
            $this->full_name = $student->name ?? $this->full_name;
        } else {
            $this->isWaitlisted = false;
        }
        
        $this->recalculatePrice();
    }

    public function recalculatePrice()
    {
        // Always check the clock first before assigning price
        $this->checkDiscount();

        if ($this->currency === 'NGN') {
            $this->amount = $this->isDiscountActive ? 59000 : 79000;
        } else {
            // Equivalent USD pricing
            $this->amount = $this->isDiscountActive ? 42 : 57; 
        }
    }

    public function initiatePayment()
    {
        $this->validate();
        
        // Final server-side security check right before generating the payment
        $this->recalculatePrice();

        try {
            $prefix = ($this->currency === 'NGN') ? 'ACC_' : 'INT_';
            $reference = $prefix . bin2hex(random_bytes(8));

            Enrollment::create([
                'full_name' => $this->full_name,
                'email' => $this->email,
                'whatsapp' => $this->whatsapp,
                'payment_reference' => $reference,
                'amount' => $this->amount,
                'currency' => $this->currency, 
                'status' => 'pending',
            ]);

            if ($this->currency === 'NGN') {
                $this->dispatch('launch-paystack', [
                    'email' => $this->email,
                    'amount' => $this->amount * 100, 
                    'reference' => $reference,
                    'key' => config('services.paystack.public_key'),
                    'metadata' => ['full_name' => $this->full_name]
                ]);
            } else {
                $this->dispatch('launch-flutterwave', [
                    'email' => $this->email,
                    'amount' => $this->amount,
                    'currency' => 'USD',
                    'reference' => $reference,
                    'key' => env('FLW_PUBLIC_KEY'), 
                    'name' => $this->full_name,
                    'phone' => $this->whatsapp
                ]);
            }

        } catch (\Exception $e) {
            $this->statusMessage = "Initialization failed. Please refresh.";
            \Log::error("Checkout Error: " . $e->getMessage());
        }
    }
}; ?>

<div class="flex flex-col lg:flex-row max-w-6xl mx-auto w-full">
    <!-- LEFT: ENROLLMENT FORM -->
    <div class="flex-1 p-8 lg:p-16 border-r border-zinc-900">
        <div class="max-w-md mx-auto">
            
            <!-- MASTERCLASS FLASH SALE BANNER -->
            @if($isDiscountActive)
            <div x-data="countdown('{{ $deadline }}')" class="mb-8 p-4 bg-orange-500/10 border border-orange-500/30 rounded-xl relative overflow-hidden">
                <div class="absolute top-0 right-0 w-32 h-32 bg-orange-500/20 blur-3xl rounded-full pointer-events-none"></div>
                <div class="flex items-center justify-between relative z-10">
                    <div>
                        <p class="text-[10px] font-black uppercase text-orange-500 tracking-widest animate-pulse">Masterclass Offer Active</p>
                        <p class="text-white font-bold text-sm mt-1">Special price locks in:</p>
                    </div>
                    <div class="text-right">
                        <div class="text-2xl font-black font-mono text-white tracking-tighter" x-text="timeLeft">00:00:00</div>
                    </div>
                </div>
            </div>
            @endif

            <div class="mb-8">
                <h1 class="text-4xl font-black text-white uppercase italic tracking-tighter mb-4">Join Cohort 02.</h1>
                
                <!-- CURRENCY TOGGLE -->
                <div class="inline-flex bg-zinc-900 rounded-lg p-1 border border-zinc-800">
                    <button wire:click="$set('currency', 'NGN')" 
                        class="px-4 py-2 rounded text-[10px] font-black uppercase tracking-widest transition-all {{ $currency === 'NGN' ? 'bg-cyan-900/30 text-cyan-400 shadow-sm border border-cyan-500/20' : 'text-zinc-500 hover:text-zinc-300' }}">
                        NGN (Local)
                    </button>
                    <button wire:click="$set('currency', 'USD')" 
                        class="px-4 py-2 rounded text-[10px] font-black uppercase tracking-widest transition-all {{ $currency === 'USD' ? 'bg-cyan-900/30 text-cyan-400 shadow-sm border border-cyan-500/20' : 'text-zinc-500 hover:text-zinc-300' }}">
                        USD (Global)
                    </button>
                </div>
            </div>
            
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
                           placeholder="+1 555...">
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
                        <span wire:loading.remove>
                            {{ $currency === 'NGN' ? 'Pay ₦'.number_format($this->amount) : 'Pay $'.number_format($this->amount) }}
                        </span>
                        <span wire:loading>Syncing Gateways...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- RIGHT: ORDER SUMMARY -->
    <div class="w-full lg:w-[450px] p-8 lg:p-16 bg-zinc-900/10">
        <div class="sticky top-32">
            <h2 class="text-xs font-black uppercase tracking-[0.3em] text-zinc-600 mb-8">Summary // Cohort_002</h2>
            <div class="space-y-6">
                <div class="flex justify-between items-start">
                    <div>
                        <div class="text-lg font-bold text-white uppercase italic tracking-tighter">Accelerator</div>
                        <div class="text-[10px] text-zinc-500 uppercase mt-1">AI Automation Factory</div>
                    </div>
                    <div class="text-right">
                        @if($isDiscountActive)
                            <div class="text-xs text-zinc-500 line-through">
                                {{ $currency === 'NGN' ? '₦79,000' : '$57' }}
                            </div>
                            <div class="text-xl font-black text-cyan-400">
                                {{ $currency === 'NGN' ? '₦' . number_format($amount) : '$' . $amount }}
                            </div>
                        @else
                            <div class="text-xl font-black text-white">
                                {{ $currency === 'NGN' ? '₦' . number_format($amount) : '$' . $amount }}
                            </div>
                        @endif
                    </div>
                </div>

                <div class="space-y-4 pt-8 border-t border-zinc-900">
                    <div class="flex items-center gap-3 text-xs text-zinc-400">
                        <svg class="w-4 h-4 text-cyan-500" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/></svg>
                        n8n Snapshot Vault (deployment ready)
                    </div>
                    <div class="flex items-center gap-3 text-xs text-zinc-400">
                        <svg class="w-4 h-4 text-cyan-500" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/></svg>
                        Private Community Access
                    </div>
                    <div class="flex items-center gap-3 text-xs text-zinc-400">
                        <svg class="w-4 h-4 text-cyan-500" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/></svg>
                        Lifetime Strategy Updates
                    </div>
                    <div class="flex items-center gap-3 text-xs text-zinc-400">
                        <svg class="w-4 h-4 text-cyan-500" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/></svg>
                        Weekly Live Q&A Sessions
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ALPINE & PAYMENT SCRIPTS -->
    <script src="https://js.paystack.co/v1/inline.js"></script>
    <script src="https://checkout.flutterwave.com/v3.js"></script>
    
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('countdown', (deadline) => ({
                timeLeft: '00:00:00',
                init() {
                    // Adjust string format to ensure proper parsing across browsers (WAT is UTC+1)
                    const deadlineString = deadline.replace(' ', 'T') + '+01:00';
                    const endTime = new Date(deadlineString).getTime();
                    
                    const updateTimer = () => {
                        const now = new Date().getTime();
                        const distance = endTime - now;

                        if (distance < 0) {
                            this.timeLeft = '00:00:00';
                            clearInterval(this.interval);
                            // Tell Livewire the discount expired so it updates the price backend
                            @this.call('expireDiscount');
                            return;
                        }

                        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                        const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                        this.timeLeft = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
                    };

                    updateTimer(); // Initial call
                    this.interval = setInterval(updateTimer, 1000); // Tick every second
                }
            }));
        });

        document.addEventListener('livewire:init', () => {
            // PAYSTACK (NGN)
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

            // FLUTTERWAVE (USD)
            Livewire.on('launch-flutterwave', (event) => {
                const config = event[0];
                FlutterwaveCheckout({
                    public_key: config.key,
                    tx_ref: config.reference,
                    amount: config.amount,
                    currency: "USD",
                    payment_options: "card,mobilemoney,ussd",
                    customer: {
                        email: config.email,
                        phone_number: config.phone,
                        name: config.name,
                    },
                    customizations: {
                        title: "Automation Factory",
                        description: "Accelerator Enrollment",
                        logo: "https://ajbuildai.com/img/headshot.jpg",
                    },
                    callback: function (data) {
                        window.location.href = '/thank-you?reference=' + config.reference;
                    }
                });
            });
        });
    </script>
</div>