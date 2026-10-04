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
    | announced".
    |
    | The offer attendees are sent to is TAAB100 (flat, see accelerator.coupons), which
    | has no expiry - so unlike the Sep edition there is no coupon deadline to line this
    | session up against.
    |
    | WHAT THIS SESSION FEEDS IS NOT SET. accelerator.cohort_starts_at and
    | cart_closes_at still describe Cohort 3, which began 12 Sep and closed 14 Sep, so
    | /accelerator currently shows a start date in the past to anyone this session sends
    | there. Set the Cohort 4 dates before this runs.
    */
    'masterclass' => [
        'date' => '2026-10-10',                 // Saturday 10 October 2026
        'time' => '9:00 AM - 11:00 AM WAT',
        'location' => 'Live · Google Meet',
        'registration_closes' => '2026-10-09',  // the Friday before
        'host' => 'AJ Thompson',

        // Real datetimes the reminder scheduler uses (display strings above are
        // for the page). Interpreted in `timezone`, since app default is UTC.
        'starts_at' => '2026-10-10 09:00',
        'ends_at' => '2026-10-10 11:00',
        'timezone' => 'Africa/Lagos',

        // Links sent in the reminders. These come from the SERVER .env, which a deploy
        // does NOT update — changing the session date here while the server still holds
        // the previous edition's TAAB_MEET_URL sends every attendee to a dead call.
        // Set them on the server, then `php artisan config:cache`, then verify with
        // `php artisan tinker --execute="echo config('taab.masterclass.meet_url');"`.
        'meet_url' => env('TAAB_MEET_URL'),
        'whatsapp_group_url' => env('TAAB_WA_GROUP_URL'),
        'recording_url' => env('TAAB_RECORDING_URL'), // optional, used in follow-up

        // When each automated touch fires, relative to starts_at / ends_at.
        'reminder_lead_hours' => 24,   // Meet link + WhatsApp group
        'dayof_lead_hours' => 2,       // "starting soon" nudge
        'followup_after_hours' => 2,   // post-session Accelerator follow-up

        // Delay between webhook sends (ms) so a burst (e.g. 30 nudges at once)
        // doesn't trip SMTP / n8n rate limits and silently drop messages.
        'send_throttle_ms' => 400,
    ],
];
