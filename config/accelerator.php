<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AI Automation Accelerator — Cohort 03 Offer Config
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
    // Days a student gets to clear the 2nd installment. Counted from the COHORT
    // START, not from when they paid — see Accelerator::installmentDueAt(). Changing
    // this only affects enrollments stamped AFTER the change; run
    // `php artisan installments:realign` to bring existing students onto the new
    // window (it never shortens an existing deadline).
    'installment_due_days'   => 21,
    'installment_grace_hours' => 24, // suspend access this long after the due date if still unpaid

    // Completion guarantee: weekly live sessions a student must attend (in
    // addition to finishing all module checkpoints) to qualify. Surfaced on the
    // dashboard progress card.
    //
    // MUST stay below the number of sessions a student can ACTUALLY still attend,
    // which is not the same as the number of sessions in the curriculum. Attendance
    // only exists for sessions that (a) ran after the student's cohort started and
    // (b) had an `attendance_code` set — there is no retroactive path.
    //
    // Cohort 2 learned this the hard way: it was set to 6 when Cohort 2 had exactly
    // 6 attendable sessions, so the guarantee demanded 100% attendance and failed
    // anyone who got ill once. 4 leaves real margin while still requiring genuine
    // participation.
    //
    // For Cohort 3 (starts Sat 12 Sep 2026) the attendable set is live-11..live-16,
    // the six Saturdays from 19 Sep to 24 Oct 2026. live-01..live-09 all ran before the
    // start; live-10 is Cohort 2's closing session on the morning of the start day and
    // carries no attendance_code, so it credits nobody either. If you shift the cohort
    // start, RE-COUNT the sessions that fall after it before trusting this number.
    'guarantee_min_live_sessions' => 4,

    /*
    | Checkout coupons (server-side only — the discount is computed from here,
    | never trusted from the client). Keyed by the code the buyer types
    | (case-insensitive). Each:
    |   'type'       => 'percent' | 'fixed'
    |   'value'      => percent: a number (e.g. 25 = 25% off)
    |                   fixed:   ['NGN' => 10000, 'USD' => 8]  (per-currency)
    |   'plans'      => which plans it applies to: ['full','installment']
    |   'expires_at' => optional Africa/Lagos cutoff; omit for no expiry
    |   'label'      => shown on the checkout when applied
    | Discount applies to the plan TOTAL at the current price (early-bird included).
    | {{TODO: set the TAAB masterclass code + discount, then uncomment}}
    */
    'coupons' => [
        'TAAB59' => [
            'type' => 'fixed',
            'value' => ['NGN' => 10000, 'USD' => 7], // ₦10,000 ≈ $7 at ~₦1,400/$
            'plans' => ['full', 'installment'],
            'expires_at' => '2026-09-14 23:59:59',     // cart close (Mon 14 Sep, Africa/Lagos)
            'label' => 'TAAB masterclass discount',
        ],
    ],

    // --- Scarcity / cohort ---
    'cohort_number'     => 3,     // stamped on new enrollments; >= 2 enables ship-to-unlock
    'cohort_cap'        => 25,
    'earlybird_seats'   => 10,    // early-bird active while seats_sold < this
    'earlybird_ends_at' => '2026-08-31 23:59:59', // Monday 31st August 2026 (Africa/Lagos), or until earlybird_seats sell — whichever first
    'cohort_starts_at'  => '2026-09-12', // Saturday 12th September 2026
    'cart_closes_at'    => '2026-09-14 23:59:59', // Monday 14th September 2026 (doors stay open 2 days into the cohort)

    'payment_provider'  => 'paystack', // or 'flutterwave'

    // Telegram community group invite link (fallback for any thread below).
    // Hardcoded default — like the #wins/thread URLs — so it renders in production
    // without depending on the server .env being set + config-cached. Env overrides.
    'telegram_community_url' => env('ACCELERATOR_TELEGRAM_URL') ?: 'https://t.me/+EH57E-1mkn02Mjg0',

    // #wins thread — where students post build proof for their checkpoints.
    // The "Ship it to unlock" panel links here; falls back to the group url.
    'telegram_wins_url' => 'https://t.me/c/3619461825/6',

    /*
    | Per-module Telegram #help topic/thread deep links — where students ask
    | questions for that module (NOT where proof goes; that's telegram_wins_url).
    | Enable Topics in the group, then paste each module's thread URL here (forum
    | topic links look like https://t.me/c/<chatId>/<topicId> for private
    | supergroups). Any module left null falls back to telegram_community_url.
    |
    | Keyed by the curriculum module ID, which is a STABLE KEY and not a position —
    | so these stay correct when modules are reordered. The displayed module number
    | is in the comment after each line; don't reorder these to "fix" them.
    */
    'telegram_threads' => [
        'module-01' => 'https://t.me/c/3619461825/3',    // Module 01 · Welcome to the Factory
        'module-02' => 'https://t.me/c/3619461825/5',    // Module 02 · Basics of n8n
        // 'module-lead-qualifier' => '',                // Module 03 · The Lead Qualifier — no thread yet;
        //                                              //   falls back to telegram_community_url until set
        'module-03' => 'https://t.me/c/3619461825/433',  // Module 04 · API Calls With n8n
        'module-04' => 'https://t.me/c/3619461825/304',  // Module 05 · Knowledge Base (RAG)
        'module-05' => 'https://t.me/c/3619461825/305',  // Module 06 · WhatsApp Automation
        'module-06' => 'https://t.me/c/3619461825/435',  // Module 07 · AI Voice Support Agent
        'module-07' => 'https://t.me/c/3619461825/438',  // Module 08 · AI Chat Support Agent
        'module-08' => 'https://t.me/c/3619461825/440',  // Module 09 · Deploy Your Automation
        // 'module-09' retired — the standalone hosting module was folded into Deploy.
        // Its Telegram topic (…/771) still exists in the group; point students at the
        // Deploy thread above, or re-map this line if you'd rather keep using it.
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
