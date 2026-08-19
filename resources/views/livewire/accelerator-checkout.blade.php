<?php

use Livewire\Volt\Component;
use App\Models\Enrollment;
use App\Support\Accelerator;
use Illuminate\Support\Facades\DB;

new class extends Component {
    public string $email = '';
    public string $full_name = '';
    public string $whatsapp = '';

    // Plan + currency
    public string $plan = 'full';      // full | installment
    public string $currency = 'NGN';   // NGN | USD

    // Required "no-surprises" acknowledgement (gates the pay button)
    public bool $acknowledged = false;

    // Derived pricing (set by recalculate())
    public float $amountToday = 0;     // charged now — also stored as Enrollment.amount
    public float $amountTotal = 0;     // total cost of the chosen plan
    public float $balanceDue = 0;      // outstanding after today's charge

    // Coupon (validated server-side against config — never trusted from the client)
    public string $couponCode = '';    // bound to the coupon input
    public ?array $coupon = null;      // the applied coupon config, or null
    public float $discount = 0;        // discount applied to the total
    public string $couponMessage = '';

    // Cohort state
    public string $cohortLabel = '';   // "Cohort 3"
    public string $cohortPadded = '';  // "Cohort 03"
    public bool $soldOut = false;
    public bool $registrationOpen = true;
    public int $seatsLeft = 0;
    public bool $earlybird = false;
    public ?string $closesLabel = null;

    public bool $isReturning = false;
    public string $statusMessage = '';

    protected $rules = [
        'full_name' => 'required|string|max:255',
        'email' => 'required|email',
        'whatsapp' => 'nullable|string|min:10',
    ];

    public function mount()
    {
        // Preselect the plan passed from the landing CTA (?plan=full|installment)
        $requested = request('plan');
        if (in_array($requested, ['full', 'installment'], true)) {
            $this->plan = $requested;
        }

        $this->refreshCohortState();
        $this->recalculate();
    }

    public function refreshCohortState(): void
    {
        $this->cohortLabel      = Accelerator::cohortLabel();
        $this->cohortPadded     = Accelerator::cohortLabelPadded();
        $this->soldOut          = Accelerator::isSoldOut();
        $this->registrationOpen = Accelerator::registrationOpen();
        $this->seatsLeft        = Accelerator::seatsLeft();
        $this->earlybird        = Accelerator::earlybirdActive();
        $closesAt               = Accelerator::cartClosesAt();
        $this->closesLabel      = ($closesAt && \Illuminate\Support\Carbon::now('Africa/Lagos')->lt($closesAt))
            ? $closesAt->format('l jS F') : null;
    }

    public function updatedPlan()
    {
        // Drop a coupon that doesn't apply to the newly-selected plan.
        if ($this->coupon && ! Accelerator::couponAppliesToPlan($this->coupon, $this->plan)) {
            $this->couponMessage = ($this->coupon['label'] ?? 'That coupon') . " isn't valid on this plan.";
            $this->coupon = null;
            $this->couponCode = '';
        }
        $this->recalculate();
    }

    public function updatedCurrency()
    {
        $this->recalculate();
    }

    public function applyCoupon(): void
    {
        $coupon = Accelerator::coupon($this->couponCode);

        if (! $coupon) {
            $this->coupon = null;
            $this->couponMessage = "That code isn't valid or has expired.";
            $this->recalculate();
            return;
        }

        if (! Accelerator::couponAppliesToPlan($coupon, $this->plan)) {
            $this->coupon = null;
            $this->couponMessage = 'This code applies to a different plan — switch plans to use it.';
            $this->recalculate();
            return;
        }

        $this->coupon = $coupon;
        $this->couponMessage = ($coupon['label'] ?? 'Coupon') . ' applied.';
        $this->recalculate();
    }

    public function removeCoupon(): void
    {
        $this->coupon = null;
        $this->couponCode = '';
        $this->couponMessage = '';
        $this->recalculate();
    }

    public function updatedEmail($value)
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) return;

        // Pre-fill from an existing waitlist/student record if we recognise them
        $student = DB::table('students')->where('email', $value)->first();
        if ($student) {
            $this->isReturning = true;
            $this->whatsapp = $student->whatsapp ?? $this->whatsapp;
            $this->full_name = $student->name ?? $this->full_name;
        } else {
            $this->isReturning = false;
        }
    }

    public function recalculate(): void
    {
        // Always recompute cohort state so prices never go stale mid-session
        $this->refreshCohortState();

        $count = Accelerator::installmentCount();
        $baseTotal = $this->plan === 'installment'
            ? Accelerator::installmentEach($this->currency) * $count
            : Accelerator::fullPrice($this->currency);

        // Coupon discount — revalidated on every recompute (including the final
        // price-lock before charging), so the client can never inject a price.
        $this->discount = 0;
        if ($this->coupon && Accelerator::couponAppliesToPlan($this->coupon, $this->plan)) {
            $this->discount = Accelerator::couponDiscount($this->coupon, $baseTotal, $this->currency);
        }
        $total = max(0, round($baseTotal - $this->discount, 2));

        if ($this->plan === 'installment') {
            $each = round($total / $count, 2);
            $this->amountToday = $each;
            $this->amountTotal = $total;
            $this->balanceDue  = round($total - $each, 2);
        } else {
            $this->amountToday = $total;
            $this->amountTotal = $total;
            $this->balanceDue  = 0;
        }
    }

    public function initiatePayment()
    {
        $this->validate();

        // Server-side guards (UI also enforces these)
        $this->refreshCohortState();
        if (! $this->registrationOpen) {
            $this->statusMessage = 'Registration is currently closed. Please join the waitlist.';
            return;
        }
        if ($this->soldOut) {
            $this->statusMessage = 'This cohort is full. Please join the waitlist.';
            return;
        }
        if (! $this->acknowledged) {
            $this->statusMessage = 'Please confirm you have read the Requirements & Costs.';
            return;
        }

        // Final server-side price lock right before generating the charge
        $this->recalculate();

        try {
            $prefix = ($this->currency === 'NGN') ? 'ACC_' : 'INT_';
            $reference = $prefix . bin2hex(random_bytes(8));

            Enrollment::create([
                'full_name'             => $this->full_name,
                'email'                 => $this->email,
                'whatsapp'              => $this->whatsapp,
                'payment_reference'     => $reference,
                'amount'                => $this->amountToday,   // charged now — matched by the webhook
                'plan_type'             => $this->plan,
                'cohort'                => (int) config('accelerator.cohort_number', 2),
                'amount_total'          => $this->amountTotal,
                'balance_due'           => $this->balanceDue,
                'second_payment_status' => $this->plan === 'installment' ? 'pending' : 'none',
                'currency'              => $this->currency,
                'coupon_code'           => $this->coupon['code'] ?? null,
                'discount_amount'       => $this->discount ?: null,
                'status'                => 'pending',
            ]);

            if ($this->currency === 'NGN') {
                $this->dispatch('launch-paystack', [
                    'email' => $this->email,
                    'amount' => $this->amountToday * 100, // kobo
                    'reference' => $reference,
                    'key' => config('services.paystack.public_key'),
                    'metadata' => ['full_name' => $this->full_name, 'plan' => $this->plan],
                ]);
            } else {
                $this->dispatch('launch-flutterwave', [
                    'email' => $this->email,
                    'amount' => $this->amountToday,
                    'currency' => 'USD',
                    'reference' => $reference,
                    'key' => config('services.flutterwave.public_key'),
                    'name' => $this->full_name,
                    'phone' => $this->whatsapp,
                ]);
            }
        } catch (\Exception $e) {
            $this->statusMessage = "Initialization failed. Please refresh.";
            \Log::error("Checkout Error: " . $e->getMessage());
        }
    }

    public function with(): array
    {
        return [
            'symbol'      => $this->currency === 'NGN' ? '₦' : '$',
            'cap'         => (int) config('accelerator.cohort_cap'),
            'fullNow'     => Accelerator::fullPrice($this->currency),
            'fullRegular' => Accelerator::regularFullPrice($this->currency),
            'instEach'    => Accelerator::installmentEach($this->currency),
            'instCount'   => Accelerator::installmentCount(),
        ];
    }
}; ?>

<div x-data="{ ack: @entangle('acknowledged'), showReq: false }" class="flex flex-col lg:flex-row max-w-6xl mx-auto w-full">

    @if($soldOut || ! $registrationOpen)
        <!-- CLOSED STATE (sold out OR registration paused) -->
        <div class="flex-1 p-6 sm:p-8 lg:p-16 flex items-center justify-center">
            <div class="max-w-md text-center space-y-6">
                <div class="inline-flex h-16 w-16 items-center justify-center rounded-full bg-amber-500/10 border border-amber-500/40 text-amber-400">
                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                </div>
                @if($soldOut)
                    <h1 class="text-3xl font-black text-white uppercase italic tracking-tighter">{{ $cohortLabel }} is full.</h1>
                    <p class="text-zinc-500 text-sm leading-relaxed">All {{ $cap }} seats are taken. Join the waitlist and we'll reach out the moment a seat opens or the next cohort is announced.</p>
                @else
                    <h1 class="text-3xl font-black text-white uppercase italic tracking-tighter">Registration is paused.</h1>
                    <p class="text-zinc-500 text-sm leading-relaxed">Enrolment for this cohort is temporarily closed. Join the waitlist and we'll let you know the moment it reopens.</p>
                @endif
                <a href="/builders" class="inline-block px-8 py-4 bg-cyan-500 text-black font-black uppercase tracking-tighter text-lg rounded-2xl hover:bg-white transition-all">Join The Waitlist</a>
            </div>
        </div>
    @else
        <!-- LEFT: ENROLLMENT FORM -->
        <div class="flex-1 p-6 sm:p-8 lg:p-16 border-r border-zinc-900">
            <div class="max-w-md mx-auto">
                <div class="mb-8">
                    <h1 class="text-4xl font-black text-white uppercase italic tracking-tighter mb-2">Join {{ $cohortPadded }}.</h1>
                    <p class="text-[11px] font-mono uppercase tracking-widest {{ $earlybird ? 'text-cyan-400' : 'text-zinc-500' }}">
                        {{ $seatsLeft }} of {{ $cap }} seats left @if($earlybird) · early-bird active @endif
                    </p>
                    @if($closesLabel)
                        <p class="mt-1 text-[11px] font-mono font-bold uppercase tracking-widest text-amber-400">⏳ Enrolment closes {{ $closesLabel }}</p>
                    @endif
                </div>

                <!-- PLAN SELECTION -->
                <div class="grid grid-cols-2 gap-3 mb-6">
                    <button type="button" wire:click="$set('plan', 'full')"
                        class="text-left p-4 rounded-2xl border transition-all {{ $plan === 'full' ? 'border-cyan-500/60 bg-cyan-950/20' : 'border-zinc-800 bg-zinc-900/40 hover:border-zinc-700' }}">
                        <div class="text-[10px] font-black uppercase tracking-widest {{ $plan === 'full' ? 'text-cyan-400' : 'text-zinc-500' }}">Pay in full</div>
                        <div class="text-xl font-black text-white mt-1">{{ $symbol }}{{ number_format($fullNow) }}</div>
                        @if($earlybird)<div class="text-[10px] text-zinc-500 line-through">{{ $symbol }}{{ number_format($fullRegular) }}</div>@endif
                    </button>
                    <button type="button" wire:click="$set('plan', 'installment')"
                        class="text-left p-4 rounded-2xl border transition-all {{ $plan === 'installment' ? 'border-cyan-500/60 bg-cyan-950/20' : 'border-zinc-800 bg-zinc-900/40 hover:border-zinc-700' }}">
                        <div class="text-[10px] font-black uppercase tracking-widest {{ $plan === 'installment' ? 'text-cyan-400' : 'text-zinc-500' }}">{{ $instCount }} payments</div>
                        <div class="text-xl font-black text-white mt-1">{{ $symbol }}{{ number_format($instEach) }} <span class="text-sm text-zinc-500">× {{ $instCount }}</span></div>
                    </button>
                </div>

                <!-- CURRENCY TOGGLE -->
                <div class="inline-flex bg-zinc-900 rounded-lg p-1 border border-zinc-800 mb-8">
                    <button type="button" wire:click="$set('currency', 'NGN')"
                        class="px-4 py-2 rounded text-[10px] font-black uppercase tracking-widest transition-all {{ $currency === 'NGN' ? 'bg-cyan-900/30 text-cyan-400 shadow-sm border border-cyan-500/20' : 'text-zinc-500 hover:text-zinc-300' }}">
                        NGN (Local)
                    </button>
                    <button type="button" wire:click="$set('currency', 'USD')"
                        class="px-4 py-2 rounded text-[10px] font-black uppercase tracking-widest transition-all {{ $currency === 'USD' ? 'bg-cyan-900/30 text-cyan-400 shadow-sm border border-cyan-500/20' : 'text-zinc-500 hover:text-zinc-300' }}">
                        USD (Global)
                    </button>
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
                               placeholder="+234 80...">
                        @error('whatsapp') <span class="text-[10px] text-red-500 uppercase font-bold">{{ $message }}</span> @enderror
                    </div>

                    <!-- REQUIRED: No-surprises acknowledgement -->
                    <label class="flex items-start gap-3 p-4 rounded-xl border border-zinc-800 bg-zinc-900/40 cursor-pointer">
                        <input type="checkbox" x-model="ack"
                               class="mt-0.5 h-5 w-5 rounded border-zinc-700 bg-zinc-950 text-cyan-500 focus:ring-0 focus:ring-offset-0 shrink-0">
                        <span class="text-xs text-zinc-400 leading-snug">
                            I've read the
                            <button type="button" @click.prevent="showReq = true" class="text-cyan-400 underline underline-offset-2 hover:text-cyan-300">Requirements &amp; Costs</button>
                            and understand that beyond tuition I'll need ~one cheap domain (from ~$10/yr) plus a real international/USD card for free Google Cloud hosting (or a small paid host, ~$10/mo for ~3 months, if I don't have one).
                        </span>
                    </label>

                    @if($statusMessage)
                        <div class="p-3 bg-red-900/20 border border-red-500/50 text-red-500 text-xs font-mono rounded-lg">
                            {{ $statusMessage }}
                        </div>
                    @endif

                    <div class="pt-2">
                        <button type="submit" wire:loading.attr="disabled" x-bind:disabled="!ack"
                                class="w-full py-5 bg-cyan-500 text-black font-black uppercase text-xl rounded-2xl hover:bg-white transition-all shadow-xl shadow-cyan-500/10 disabled:opacity-40 disabled:cursor-not-allowed">
                            <span wire:loading.remove>
                                Pay {{ $symbol }}{{ number_format($amountToday) }}{{ $plan === 'installment' ? ' today' : '' }}
                            </span>
                            <span wire:loading>Syncing Gateways...</span>
                        </button>
                        <p x-show="!ack" x-cloak class="text-center text-[10px] text-zinc-600 uppercase tracking-widest mt-3">Tick the box above to continue</p>
                    </div>
                </form>
            </div>
        </div>

        <!-- RIGHT: ORDER SUMMARY -->
        <div class="w-full lg:w-[450px] p-6 sm:p-8 lg:p-16 bg-zinc-900/10">
            <div class="sticky top-32">
                <h2 class="text-xs font-black uppercase tracking-[0.3em] text-zinc-600 mb-8">Summary // Cohort_{{ str_pad((string) \App\Support\Accelerator::cohortNumber(), 3, '0', STR_PAD_LEFT) }}</h2>
                <div class="space-y-6">
                    <div class="flex justify-between items-start">
                        <div>
                            <div class="text-lg font-bold text-white uppercase italic tracking-tighter">Accelerator</div>
                            <div class="text-[10px] text-zinc-500 uppercase mt-1">{{ $plan === 'installment' ? $instCount.' payments' : 'Pay in full' }}@if($earlybird && $plan === 'full') · early-bird @endif</div>
                        </div>
                        <div class="text-right">
                            @if($earlybird && $plan === 'full')
                                <div class="text-xs text-zinc-500 line-through">{{ $symbol }}{{ number_format($fullRegular) }}</div>
                            @endif
                            <div class="text-xl font-black {{ $earlybird && $plan === 'full' ? 'text-cyan-400' : 'text-white' }}">{{ $symbol }}{{ number_format($amountTotal) }}</div>
                        </div>
                    </div>

                    <!-- Today / balance breakdown -->
                    <div class="space-y-2 pt-6 border-t border-zinc-900 text-sm">
                        <div class="flex justify-between">
                            <span class="text-zinc-500">Due today</span>
                            <span class="text-white font-bold">{{ $symbol }}{{ number_format($amountToday) }}</span>
                        </div>
                        @if($plan === 'installment')
                            <div class="flex justify-between">
                                <span class="text-zinc-500">Balance ({{ $instCount - 1 }} × {{ $symbol }}{{ number_format($instEach) }})</span>
                                <span class="text-zinc-400">{{ $symbol }}{{ number_format($balanceDue) }}</span>
                            </div>
                            <div class="flex justify-between pt-2 border-t border-zinc-900/60">
                                <span class="text-zinc-500">Total</span>
                                <span class="text-white font-bold">{{ $symbol }}{{ number_format($amountTotal) }}</span>
                            </div>
                        @endif
                    </div>

                    <!-- Coupon -->
                    <div class="pt-6 border-t border-zinc-900">
                        @if($coupon)
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-green-400 font-bold">{{ $coupon['code'] }} applied <span class="text-zinc-500 font-normal">(−{{ $symbol }}{{ number_format($discount) }})</span></span>
                                <button type="button" wire:click="removeCoupon" class="text-[11px] font-bold text-zinc-500 hover:text-red-400 uppercase tracking-widest">Remove</button>
                            </div>
                        @else
                            <div class="flex gap-2">
                                <input type="text" wire:model="couponCode" wire:keydown.enter.prevent="applyCoupon" placeholder="Coupon code"
                                    class="flex-1 bg-zinc-900 border border-zinc-800 text-white px-3 py-2.5 rounded-lg text-sm focus:border-cyan-500 focus:ring-0 placeholder:text-zinc-600 uppercase tracking-widest">
                                <button type="button" wire:click="applyCoupon" class="shrink-0 px-4 py-2.5 rounded-lg border border-zinc-700 text-zinc-300 text-[11px] font-black uppercase tracking-widest hover:border-cyan-500 hover:text-cyan-400 transition">Apply</button>
                            </div>
                        @endif
                        @if($couponMessage)
                            <p class="mt-2 text-[11px] {{ $coupon ? 'text-green-400' : 'text-amber-400' }}">{{ $couponMessage }}</p>
                        @endif
                    </div>

                    <div class="space-y-4 pt-8 border-t border-zinc-900">
                        @foreach([
                            '9 production workflows + full curriculum',
                            'Ship-to-unlock + weekly live clinics',
                            'n8n Snapshot Vault (deployment ready)',
                            'Lifetime LMS access + future updates',
                            'Completion guarantee',
                        ] as $perk)
                            <div class="flex items-center gap-3 text-xs text-zinc-400">
                                <svg class="w-4 h-4 text-cyan-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/></svg>
                                {{ $perk }}
                            </div>
                        @endforeach
                    </div>

                    <!-- Trust: completion guarantee + secure payment -->
                    <div class="pt-6 border-t border-zinc-900 space-y-3">
                        <p class="text-[11px] text-zinc-500 leading-relaxed"><strong class="text-zinc-300">Finish, or we finish with you.</strong> If your stack isn't live by the end, you get free 1-on-1 sessions until it works.</p>
                        <div class="flex items-center gap-2 text-[10px] font-mono text-zinc-600 uppercase tracking-widest">
                            <svg class="w-3.5 h-3.5 text-cyan-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            Secure payment · {{ $currency === 'NGN' ? 'Paystack' : 'Flutterwave' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- REQUIREMENTS & COSTS MODAL -->
    <div x-show="showReq" x-cloak x-transition.opacity
         class="fixed inset-0 z-[100] flex items-start justify-center overflow-y-auto bg-black/80 backdrop-blur-sm p-3 sm:p-6">
        <div @click.outside="showReq = false" class="relative w-full max-w-3xl my-6 rounded-3xl border border-zinc-800 bg-zinc-950 p-5 sm:p-8">
            <button type="button" @click="showReq = false" class="absolute top-5 right-5 text-zinc-500 hover:text-white text-2xl leading-none">&times;</button>
            <x-requirements-costs />
            <div class="mt-8 text-center">
                <button type="button" @click="ack = true; showReq = false" class="px-8 py-4 bg-cyan-500 text-black font-black uppercase tracking-tighter rounded-2xl hover:bg-white transition-all">Got it — I understand</button>
            </div>
        </div>
    </div>

    <!-- ALPINE & PAYMENT SCRIPTS -->
    <script src="https://js.paystack.co/v1/inline.js"></script>
    <script src="https://checkout.flutterwave.com/v3.js"></script>

    <script>
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
