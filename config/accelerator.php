<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AI Automation Accelerator — Cohort 02 Offer Config
    |--------------------------------------------------------------------------
    | Single source of truth for pricing, scarcity, and cohort dates. Drives
    | both the landing page (/accelerator) and the checkout (/checkout). Derived
    | state (seats left, early-bird active, current price) lives in
    | App\Support\Accelerator so the two pages never drift apart.
    |
    | All NGN amounts are in NAIRA here. Paystack is charged in kobo (×100) at
    | the call site. {{TODO}} markers are values the owner must supply.
    */

    // --- Core NGN pricing ---
    'price_full'        => 79000,   // Pay in full
    'price_earlybird'   => 69000,   // Early-bird (first 10 seats OR first 72h)
    'installment_each'  => 42000,   // ₦42,000 × 2
    'installment_count' => 2,
    'currency'          => 'NGN',

    // Installment scheduling
    'installment_due_days'   => 14,  // 2nd payment is due this many days after the 1st
    'installment_grace_hours' => 24, // suspend access this long after the due date if still unpaid

    // --- Scarcity / cohort ---
    'cohort_number'     => 2,     // stamped on new enrollments; >= 2 enables ship-to-unlock
    'cohort_cap'        => 25,
    'earlybird_seats'   => 10,    // early-bird active while seats_sold < this
    'earlybird_ends_at' => '2026-07-20 23:59:59', // Monday 20th July 2026 (Africa/Lagos), or until earlybird_seats sell — whichever first
    'cohort_starts_at'  => '2026-07-31', // Friday 31st July 2026
    'cart_closes_at'    => '2026-08-03 23:59:59', // Monday 3rd August 2026

    'payment_provider'  => 'paystack', // or 'flutterwave'

    // Telegram community — where students post their proof checkpoints.
    'telegram_community_url' => env('ACCELERATOR_TELEGRAM_URL'), // {{TODO: Telegram group invite link}}

    /*
    | Per-module Telegram topic/thread deep links. Enable Topics in the group,
    | then paste each module's thread URL here (forum topic links look like
    | https://t.me/c/<chatId>/<topicId> for private supergroups, or
    | https://t.me/<groupUsername>/<topicId> for public ones). Any module left
    | null falls back to telegram_community_url. {{TODO: paste thread URLs}}
    */
    'telegram_threads' => [
        'module-01' => null,
        'module-02' => null,
        'module-03' => null,
        'module-04' => null,
        'module-05' => null,
        'module-06' => null,
        'module-07' => null,
        'module-08' => null,
        'module-09' => null,
    ],

    /*
    | USD equivalents — powers the existing NGN/USD toggle (Flutterwave path).
    | Fixed values (not auto-converted). Adjust here if the FX rate moves.
    | Implied rate ≈ ₦1,400/$.
    */
    'usd' => [
        'price_full'       => 57,
        'price_earlybird'  => 50,
        'installment_each' => 30,
    ],

    /*
    | Testimonials / proof. Empty by default — DO NOT fabricate. The owner adds
    | entries here with 'is_published' => true to make them appear. Shape:
    | ['name' => '', 'role' => '', 'quote' => '', 'photo' => '', 'is_published' => true]
    */
    'testimonials' => [],
];
