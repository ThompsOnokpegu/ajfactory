<?php

return [
    /*
    |--------------------------------------------------------------------------
    | TAAB — The AI Automation Bootcamp (periodic masterclass)
    |--------------------------------------------------------------------------
    | Top-of-funnel lead-magnet tools (scorecard, ROI calculator, tool-stack
    | guide) that capture leads into the `students` waitlist and funnel toward
    | the paid Accelerator.
    */

    // ₦ per $1 — used by the ROI calculator. {{TODO: reconcile with the ~1400
    // implied by config/accelerator.php USD pricing if desired}}
    'fx_rate' => 1650,

    // Where the tools' CTAs point.
    'accelerator_url' => '/accelerator',
    'masterclass_url' => '/taab', // the TAAB hub + registration page

    /*
    | Next masterclass session. Drives the /taab hub copy and what each
    | registration is tagged with. Set `date` to null to show "Date to be
    | announced". {{TODO: confirm the next session date/time}}
    */
    'masterclass' => [
        'date' => '2026-06-27',                 // a Saturday
        'time' => '9:00 AM – 5:00 PM WAT',
        'location' => 'Live · Zoom',
        'registration_closes' => '2026-06-26',  // the Friday before
        'host' => 'AJ Thompson',
    ],
];
