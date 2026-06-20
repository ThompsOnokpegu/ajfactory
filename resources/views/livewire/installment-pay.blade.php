<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\Enrollment;

new #[Layout('components.layouts.student')] class extends Component {
    public Enrollment $enrollment;

    public function mount(Enrollment $enrollment): void
    {
        $this->enrollment = $enrollment;
    }

    /** Is there still a second installment to collect on this enrollment? */
    public function isPayable(): bool
    {
        return $this->enrollment->plan_type === 'installment'
            && (float) $this->enrollment->balance_due > 0
            && $this->enrollment->second_payment_status !== 'paid';
    }

    public function pay(): void
    {
        if (! $this->isPayable()) {
            return;
        }

        $currency = $this->enrollment->currency ?: 'NGN';
        $amount = (float) $this->enrollment->balance_due;

        // Fresh reference per attempt — avoids Paystack "reference already used"
        // on retries. The webhook reconciles by matching this exact reference.
        $reference = 'INST2_' . bin2hex(random_bytes(8));
        $this->enrollment->update(['second_payment_reference' => $reference]);

        if ($currency === 'NGN') {
            $this->dispatch('launch-paystack', [
                'email' => $this->enrollment->email,
                'amount' => $amount * 100, // kobo
                'reference' => $reference,
                'key' => config('services.paystack.public_key'),
                'metadata' => ['full_name' => $this->enrollment->full_name, 'purpose' => 'installment_balance'],
            ]);
        } else {
            $this->dispatch('launch-flutterwave', [
                'email' => $this->enrollment->email,
                'amount' => $amount,
                'currency' => 'USD',
                'reference' => $reference,
                'key' => config('services.flutterwave.public_key'),
                'name' => $this->enrollment->full_name,
                'phone' => $this->enrollment->whatsapp,
            ]);
        }
    }

    public function with(): array
    {
        return [
            'payable' => $this->isPayable(),
            'symbol' => ($this->enrollment->currency ?: 'NGN') === 'NGN' ? '₦' : '$',
            'balance' => (float) $this->enrollment->balance_due,
        ];
    }
}; ?>

<div class="min-h-screen flex items-center justify-center p-6">
    <div class="max-w-md w-full">

        @if($payable)
            <div id="inst-pay-card">
                <div class="text-center mb-8">
                    <div class="inline-block px-3 py-1 rounded bg-cyan-500/10 border border-cyan-500/20 text-[10px] font-mono text-cyan-500 uppercase tracking-[0.3em] mb-4">
                        Installment · Balance Due
                    </div>
                    <h1 class="text-4xl font-black text-white uppercase italic tracking-tighter">Clear your balance</h1>
                </div>

                <div class="bg-zinc-900/50 border border-zinc-800 p-8 rounded-[2.5rem] backdrop-blur-sm shadow-2xl space-y-6">
                    <p class="text-sm text-zinc-400 leading-relaxed">
                        Hi <strong class="text-white">{{ $enrollment->full_name }}</strong> — this is the final payment on your
                        AI Automation Accelerator installment plan.
                    </p>

                    <div class="flex items-end justify-between pt-6 border-t border-zinc-800">
                        <span class="text-[10px] font-black uppercase text-zinc-600 tracking-widest">Amount due</span>
                        <span class="text-4xl font-black text-white tracking-tighter italic">{{ $symbol }}{{ number_format($balance) }}</span>
                    </div>

                    <button wire:click="pay" wire:loading.attr="disabled"
                            class="w-full py-5 bg-cyan-500 text-black font-black uppercase text-xl rounded-2xl hover:bg-white transition-all shadow-xl shadow-cyan-500/10 disabled:opacity-50">
                        <span wire:loading.remove wire:target="pay">Pay {{ $symbol }}{{ number_format($balance) }}</span>
                        <span wire:loading wire:target="pay">Opening secure checkout…</span>
                    </button>

                    <p class="text-center text-[10px] font-mono text-zinc-600 uppercase tracking-widest">
                        Secure payment · {{ ($enrollment->currency ?: 'NGN') === 'NGN' ? 'Paystack' : 'Flutterwave' }}
                    </p>
                </div>
            </div>

            <!-- Inline success (shown by the gateway callback, no redirect needed) -->
            <div id="inst-success" style="display:none" class="text-center space-y-6">
                <div class="inline-flex h-20 w-20 items-center justify-center rounded-full bg-cyan-500/10 border border-cyan-500 shadow-[0_0_40px_rgba(6,182,212,0.3)]">
                    <svg class="h-10 w-10 text-cyan-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                </div>
                <h1 class="text-3xl font-black text-white uppercase italic tracking-tighter">Payment received.</h1>
                <p class="text-zinc-500 text-sm max-w-xs mx-auto leading-relaxed">
                    Thank you — your balance is being confirmed and your access stays active. A receipt is on its way to your email.
                </p>
                <a href="/dashboard" class="inline-block px-8 py-3 bg-white text-black font-black uppercase tracking-widest text-[10px] rounded-xl hover:bg-cyan-500 transition-all">Enter the Terminal</a>
            </div>

        @else
            <!-- Nothing to pay -->
            <div class="text-center space-y-6">
                <div class="inline-flex h-20 w-20 items-center justify-center rounded-full bg-cyan-500/10 border border-cyan-500/40">
                    <svg class="h-10 w-10 text-cyan-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                </div>
                <h1 class="text-3xl font-black text-white uppercase italic tracking-tighter">You're all paid up.</h1>
                <p class="text-zinc-500 text-sm max-w-xs mx-auto leading-relaxed">There's no outstanding balance on this plan. If you think this is a mistake, reply to your enrolment email.</p>
                <a href="/dashboard" class="inline-block px-8 py-3 bg-white text-black font-black uppercase tracking-widest text-[10px] rounded-xl hover:bg-cyan-500 transition-all">Enter the Terminal</a>
            </div>
        @endif
    </div>

    <!-- PAYMENT SCRIPTS -->
    <script src="https://js.paystack.co/v1/inline.js"></script>
    <script src="https://checkout.flutterwave.com/v3.js"></script>
    <script>
        function installmentPaid() {
            var card = document.getElementById('inst-pay-card');
            var ok = document.getElementById('inst-success');
            if (card) card.style.display = 'none';
            if (ok) ok.style.display = 'block';
        }

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
                    callback: function (res) { installmentPaid(); },
                });
                handler.openIframe();
            });

            Livewire.on('launch-flutterwave', (event) => {
                const config = event[0];
                FlutterwaveCheckout({
                    public_key: config.key,
                    tx_ref: config.reference,
                    amount: config.amount,
                    currency: "USD",
                    payment_options: "card,mobilemoney,ussd",
                    customer: { email: config.email, phone_number: config.phone, name: config.name },
                    customizations: { title: "Accelerator", description: "Installment balance" },
                    callback: function (data) { installmentPaid(); },
                });
            });
        });
    </script>
</div>
