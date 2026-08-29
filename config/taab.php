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
    | Feeds Cohort 3, and sits tight against it on purpose: the cohort starts the same
    | day (12 Sep) and the cart closes 14 Sep, so attendees have a 2-day window and the
    | TAAB59 coupon is still valid when they land on checkout. Nothing to extend.
    */
    'masterclass' => [
        'date' => '2026-09-12',                 // Saturday 12 September 2026
        'time' => '9:00 AM - 11:00 AM WAT',
        'location' => 'Live · Google Meet',
        'registration_closes' => '2026-09-11',  // the Friday before
        'host' => 'AJ Thompson',

        // Real datetimes the reminder scheduler uses (display strings above are
        // for the page). Interpreted in `timezone`, since app default is UTC.
        'starts_at' => '2026-09-12 09:00',
        'ends_at' => '2026-09-12 11:00',
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
