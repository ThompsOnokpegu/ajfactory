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
    |   'type'       => 'percent' | 'fixed' | 'flat'
    |   'value'      => percent: a number (e.g. 25 = 25% off)
    |                   fixed:   ['NGN' => 10000, 'USD' => 8]  (discount, per-currency)
    |                   flat:    ['NGN' => 50000, 'USD' => 36] (the PRICE PAID, per-currency)
    |                   Use 'flat' for 'this costs X' promos - it holds that price even if
    |                   the base moves (early-bird ending, seats selling), which 'fixed'
    |                   does not. A currency with no entry gets no discount at all.
    |   'plans'      => which plans it applies to: ['full','installment']
    |   'expires_at' => optional Africa/Lagos cutoff; omit for no expiry
    |   'label'      => shown on the checkout when applied
    | Discount applies to the plan TOTAL at the current price (early-bird included).
    */
    'coupons' => [
        'TAAB59' => [
            'type' => 'fixed',
            // Every live currency needs an entry: one that is missing gets NO discount
            // rather than a converted one, which would quietly sell at full price there.
            'value' => ['NGN' => 10000, 'USD' => 7, 'GHS' => 80, 'KES' => 1000, 'ZAR' => 120], // ₦10,000 ≈ $7; the rest are the $7 equivalent, rounded up
            'plans' => ['full', 'installment'],
            'expires_at' => '2026-09-14 23:59:59',     // cart close (Mon 14 Sep, Africa/Lagos)
            'label' => 'TAAB masterclass discount',
        ],
        // Flat rate for TAAB attendees. 'flat' means 'value' is the PRICE PAID, not a
        // discount, so it stays 50,000 whether or not early-bird is still running.
        'TAAB50' => [
            'type' => 'flat',
            'value' => ['NGN' => 50000, 'USD' => 36], // ~N1,400/$ , matching config usd.*
            'plans' => ['full'],                      // deliberately NOT installment
            'expires_at' => '2026-08-29 23:59:59',    // midnight end of Sat 29 Aug, Africa/Lagos
            'label' => 'TAAB masterclass offer',
        ],
    ],

    // --- Scarcity / cohort ---
    'cohort_number'     => 3,     // stamped on new enrollments; >= 2 enables ship-to-unlock
    // Raised 25 -> 30 on 25 Aug 2026, deliberately BEFORE the Cohort 3 list campaign
    // sent anything. This number is the public scarcity counter ("N of 30 seats left")
    // on the landing page and checkout, and `Accelerator::isSoldOut()` hard-blocks
    // checkout when it's reached. Raising it mid-launch, after leads have already seen
    // a smaller number, reads as fake scarcity to an audience we sell "no surprises"
    // to — so if it moves again, move it before the next send, not during one.
    'cohort_cap'        => 30,
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
    | Currencies the checkout can charge in.
    |
    | NAIRA PRICES LIVE AT THE TOP OF THIS FILE, not here — this table only carries
    | NGN's symbol and provider, so there is exactly one source of truth for the Naira
    | sticker price (the landing page and Requirements copy read it directly). Every
    | other currency carries its own prices here.
    |
    | Prices are FIXED, never auto-converted. A live rate would change the sticker
    | price between page load and payment, and the webhook verifies the exact amount
    | it was told to charge — so a rate that moved mid-checkout would bounce the
    | payment as a mismatch. Set these by hand; revisit when a rate really moves.
    |
    | A currency is only offered once it has ALL THREE prices and a provider;
    | `Accelerator::enabledCurrencies()` filters out the rest. Leaving a price null
    | hides the currency rather than selling it at the wrong price.
    |
    |   'symbol'   => rendered before every amount
    |   'provider' => 'paystack' | 'flutterwave' — who collects it. Must be a currency
    |                 that account is actually enabled to settle.
    */
    'currencies' => [
        'NGN' => [
            'symbol' => '₦',
            'provider' => 'paystack',
            // prices: price_full / price_earlybird / installment_each at the top of this file
        ],
        'USD' => [
            'symbol' => '$',
            'provider' => 'flutterwave',
            'price_full' => 57,
            'price_earlybird' => 50,
            'installment_each' => 30,
        ],
        // Set 30 Aug 2026 as the USD price converted at that day's rate and rounded UP
        // (GHS 11.2508, KES 129.456, ZAR 16.1117 per USD), so none sits below the dollar
        // price. Rounding keeps the early-bird discount and the installment premium within
        // half a point of the USD ratios. These do NOT track the rate - revisit them when
        // one moves materially, the same as the USD figures.
        'GHS' => [
            'symbol' => 'GH₵',
            'provider' => 'flutterwave',
            'price_full' => 650,
            'price_earlybird' => 570,
            'installment_each' => 340,
        ],
        'KES' => [
            'symbol' => 'KSh',
            'provider' => 'flutterwave',
            'price_full' => 7400,
            'price_earlybird' => 6500,
            'installment_each' => 3900,
        ],
        'ZAR' => [
            'symbol' => 'R',
            'provider' => 'flutterwave',
            'price_full' => 920,
            'price_earlybird' => 810,
            'installment_each' => 490,
        ],
    ],

    /*
    | Testimonials / proof. DO NOT fabricate. Shape:
    | ['name' => '', 'role' => '', 'quote' => '', 'photo' => '', 'is_published' => true]
    |
    | These are real Cohort 2 review submissions, added 25 Aug 2026. Each student
    | rated 5/5 and ticked the consent box; `name` is their chosen credit line copied
    | verbatim from the Quotable card in Admin → Reviews, which honours their
    | full-name / first-name / anonymous choice. Don't "tidy" a name here.
    |
    | Quotes are TRIMMED, never rewritten. Cuts are marked with an ellipsis and
    | capitalisation is corrected; not one word has been added or changed. If a quote
    | needs a claim it doesn't make, the answer is a different quote.
    |
    | ⚠️ Adding entries here is only half the job — the Proof section in
    | resources/views/accelerator.blade.php must be uncommented, or these render
    | nowhere and the page silently keeps its empty state.
    */
    'testimonials' => [
        [
            // Answers the "I'll just learn it free on YouTube" objection, unprompted.
            'name' => 'Janet',
            'role' => 'Cohort 2',
            'quote' => 'The process explained is simpler than watching YouTube videos.',
            'photo' => '',
            'is_published' => true,
        ],
        [
            // Names the exact Module 01 wall (OAuth/API credentials) that stalls most
            // students. Left in his own plain phrasing on purpose — that's what makes
            // it read as a real person. Only OAuth/API were capitalised.
            'name' => 'James A.',
            'role' => 'Cohort 2',
            'quote' => 'In Module 1, I could not configure those OAuth and API properly - but now I am good.',
            'photo' => '',
            'is_published' => true,
        ],
        [
            // The core objection for this audience: "can I do this without being a
            // programmer?". Taken from his "what surprised you" answer, middle sentence
            // trimmed. NOT his Module 1 answer, which credits "the OpenAI API" — this
            // course teaches Gemini, and requirements-costs.blade.php advertises that
            // key at ₦0. Publishing it would contradict the costs table on the same page.
            'name' => 'Michael Egwuchukwu Ugochukwu',
            'role' => 'Cohort 2',
            'quote' => 'What surprised me most was how much you can build without being an expert programmer... Seeing my first automation actually work gave me the confidence that I can build solutions for real businesses.',
            'photo' => '',
            'is_published' => true,
        ],
    ],
];
