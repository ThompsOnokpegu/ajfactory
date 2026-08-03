<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Get {{ $resource->title }} — AJBuildAI</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-zinc-950 text-zinc-300 font-sans antialiased min-h-screen flex items-center justify-center p-6">

    <div class="w-full">
        <div class="text-center text-xl font-black tracking-tighter italic text-white uppercase mb-10">AJBUILD<span class="text-cyan-500">AI</span></div>
        <livewire:resource-checkout :resource="$resource" />
    </div>

    <!-- PAYMENT SCRIPTS -->
    <script src="https://js.paystack.co/v1/inline.js"></script>
    <script src="https://checkout.flutterwave.com/v3.js"></script>
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('launch-paystack', (event) => {
                const c = event[0];
                PaystackPop.setup({
                    key: c.key, email: c.email, amount: c.amount, currency: 'NGN', ref: c.reference,
                    callback: function () { window.location.href = '/resources/access/' + c.accessToken; },
                }).openIframe();
            });

            Livewire.on('launch-flutterwave', (event) => {
                const c = event[0];
                FlutterwaveCheckout({
                    public_key: c.key, tx_ref: c.reference, amount: c.amount, currency: 'USD',
                    payment_options: 'card,mobilemoney,ussd',
                    customer: { email: c.email, phone_number: c.phone, name: c.name },
                    customizations: { title: 'AJBuildAI', description: 'Resource purchase' },
                    callback: function () { window.location.href = '/resources/access/' + c.accessToken; },
                });
            });
        });
    </script>
</body>
</html>
