<?php

use function Livewire\Volt\{state, mount};
use App\Models\Resource;
use App\Models\ResourcePurchase;
use Illuminate\Support\Str;

state([
    'resourceId' => null,
    'title' => '',
    'emoji' => '',
    'description' => '',
    'priceNgn' => null,
    'priceUsd' => null,
    'currency' => 'NGN',
    'amount' => 0.0,
    'name' => '',
    'email' => '',
    'whatsapp' => '',
    'statusMessage' => '',
]);

mount(function (Resource $resource) {
    abort_unless($resource->is_published && $resource->isPaid(), 404);

    $this->resourceId  = $resource->id;
    $this->title       = $resource->title;
    $this->emoji       = $resource->emoji ?: '📦';
    $this->description = $resource->description;
    $this->priceNgn    = $resource->priceFor('NGN');
    $this->priceUsd    = $resource->priceFor('USD');
    $this->currency    = 'NGN';
    $this->recalc();
});

$recalc = function () {
    $r = Resource::find($this->resourceId);
    $this->amount = (float) ($r?->priceFor($this->currency) ?? 0);
};

$updatedCurrency = function () {
    $this->recalc();
};

$buy = function () {
    $this->validate([
        'name'     => 'required|string|max:255',
        'email'    => 'required|email|max:255',
        'whatsapp' => 'nullable|string|max:40',
        'currency' => 'required|in:NGN,USD',
    ]);

    $resource = Resource::find($this->resourceId);
    abort_unless($resource && $resource->is_published && $resource->isPaid(), 404);

    // Server-side price lock (never trust the client amount).
    $amount = $resource->priceFor($this->currency);
    if (! $amount || $amount <= 0) {
        $this->statusMessage = 'This resource isn’t available in the selected currency.';
        return;
    }

    $reference = 'RES_' . bin2hex(random_bytes(8));
    $token = Str::random(48);

    ResourcePurchase::create([
        'resource_id'       => $resource->id,
        'name'              => $this->name,
        'email'             => strtolower(trim($this->email)),
        'whatsapp'          => $this->whatsapp ?: null,
        'payment_reference' => $reference,
        'access_token'      => $token,
        'amount'            => $amount,
        'currency'          => $this->currency,
        'status'            => 'pending',
    ]);

    if ($this->currency === 'NGN') {
        $this->dispatch('launch-paystack', [
            'email'       => strtolower(trim($this->email)),
            'amount'      => (int) round($amount * 100), // kobo
            'reference'   => $reference,
            'key'         => config('services.paystack.public_key'),
            'accessToken' => $token,
        ]);
    } else {
        $this->dispatch('launch-flutterwave', [
            'email'       => strtolower(trim($this->email)),
            'amount'      => $amount,
            'reference'   => $reference,
            'key'         => config('services.flutterwave.public_key'),
            'name'        => $this->name,
            'phone'       => $this->whatsapp,
            'accessToken' => $token,
        ]);
    }
};

?>

<div class="w-full max-w-md mx-auto">
    <div class="text-center mb-8">
        <div class="text-4xl mb-3">{{ $emoji }}</div>
        <h1 class="text-2xl font-black text-white uppercase italic tracking-tighter">{{ $title }}</h1>
        @if($description)
            <p class="text-sm text-zinc-400 mt-2 leading-relaxed">{{ $description }}</p>
        @endif
    </div>

    <form wire:submit.prevent="buy" class="space-y-4">
        <div>
            <input type="text" wire:model="name" placeholder="Full name"
                   class="w-full bg-zinc-900 border border-zinc-800 text-white px-4 py-3 rounded-xl text-sm focus:border-cyan-500 focus:ring-0 placeholder:text-zinc-600">
            @error('name') <p class="text-[11px] text-amber-400 mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <input type="email" wire:model="email" placeholder="Email address"
                   class="w-full bg-zinc-900 border border-zinc-800 text-white px-4 py-3 rounded-xl text-sm focus:border-cyan-500 focus:ring-0 placeholder:text-zinc-600">
            @error('email') <p class="text-[11px] text-amber-400 mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <input type="tel" wire:model="whatsapp" placeholder="WhatsApp (optional)"
                   class="w-full bg-zinc-900 border border-zinc-800 text-white px-4 py-3 rounded-xl text-sm focus:border-cyan-500 focus:ring-0 placeholder:text-zinc-600">
        </div>

        @if($priceUsd !== null)
            <div class="flex gap-2">
                @foreach(['NGN' => '₦ Naira', 'USD' => '$ USD'] as $cur => $label)
                    <button type="button" wire:click="$set('currency', '{{ $cur }}')"
                        class="flex-1 py-2.5 rounded-xl border text-[11px] font-black uppercase tracking-widest transition {{ $currency === $cur ? 'border-cyan-500 text-cyan-400 bg-cyan-950/20' : 'border-zinc-800 text-zinc-500 hover:border-zinc-600' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        @endif

        <div class="flex items-center justify-between pt-2 pb-1">
            <span class="text-[10px] font-black uppercase tracking-widest text-zinc-500">Price</span>
            <span class="text-2xl font-black text-white">{{ $currency === 'USD' ? '$' : '₦' }}{{ number_format($amount) }}</span>
        </div>

        <button type="submit"
                class="w-full py-4 bg-cyan-500 text-black font-black uppercase tracking-tighter text-lg rounded-xl hover:bg-white transition-all">
            Pay {{ $currency === 'USD' ? '$' : '₦' }}{{ number_format($amount) }}
        </button>

        @if($statusMessage)
            <p class="text-center text-[12px] text-amber-400">{{ $statusMessage }}</p>
        @endif
        <p class="text-center text-[10px] font-mono text-zinc-600 uppercase tracking-widest">Secure payment · Instant access link by email</p>
    </form>
</div>
