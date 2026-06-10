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
    'masterclass_url' => '/builders', // {{TODO: confirm TAAB masterclass signup/waitlist URL}}
];
