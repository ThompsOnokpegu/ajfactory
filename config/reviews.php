<?php

/*
|--------------------------------------------------------------------------
| STUDENT REVIEWS — the staged "soft ask"
|--------------------------------------------------------------------------
| We do NOT ask for one big testimonial at the end. We ask three small,
| guided sets during the cohort, each triggered by an APPROVED proof
| checkpoint — i.e. the moment the student has just verifiably shipped
| something. That's when they feel it, and it's the only point where we
| know the answer comes from a real, active student.
|
| Each stage asks a DIFFERENT set, so across a cohort the three stack into
| a before → middle → after arc per student — which is what Cohort 3
| marketing actually needs (objection, proof, outcome), not "great course!".
|
| Stage schema:
|   'key'           => stable slug, stored on the review row. NEVER rename a
|                      key that already has rows — it orphans them.
|   'after_module'  => curriculum module id whose checkpoint must be APPROVED
|   'enabled'       => flip to false to silence a stage without a deploy-risk edit
|   'eyebrow' / 'headline' / 'intro' => panel copy
|   'questions'     => [ ['key','label','placeholder','required','rows'] ]
|                      'key' is stored inside the answers JSON — same rename rule.
|
| The ask is SOFT by design: it never gates anything, "Not now" always works,
| and after `max_dismissals` declines we stop asking for that stage entirely.
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Ask behaviour
    |--------------------------------------------------------------------------
    */

    // Days to wait before re-showing a stage the student dismissed.
    'snooze_days' => 5,

    // How many times a student may decline a stage before we stop asking it.
    'max_dismissals' => 2,

    // Ratings at or below this are treated as unhappy: we skip the
    // "can we use this publicly?" ask entirely and instead ask what would
    // fix it. Never chase a testimonial from someone who's struggling —
    // that response is a save opportunity, not marketing copy.
    'unhappy_at_or_below' => 3,

    /*
    |--------------------------------------------------------------------------
    | Credit options — how a student wants to be named if they consent
    |--------------------------------------------------------------------------
    */

    'credit_options' => [
        'full' => 'My full name',
        'first' => 'First name + last initial',
        'anon' => 'Keep me anonymous',
    ],

    /*
    |--------------------------------------------------------------------------
    | The three stages
    |--------------------------------------------------------------------------
    */

    'stages' => [

        [
            'key' => 'first-win',
            'after_module' => 'module-01',
            'enabled' => true,
            'eyebrow' => 'Quick one — 60 seconds',
            'headline' => 'You just shipped your first automation',
            'intro' => "No pressure and this changes nothing about your access — but while it's fresh, three short questions. Plain English is perfect; you don't have to write nicely.",
            'questions' => [
                [
                    'key' => 'before',
                    'label' => "Before you joined, what was the one thing you weren't sure about?",
                    'placeholder' => "e.g. whether I could really do it with no coding background, or whether it was worth the money",
                    'required' => true,
                    'rows' => 3,
                ],
                [
                    'key' => 'win',
                    'label' => "What did you get working in Module 1 that you couldn't do two weeks ago?",
                    'placeholder' => "Be specific — name the actual thing you built and what it does.",
                    'required' => true,
                    'rows' => 3,
                ],
                [
                    'key' => 'surprise',
                    'label' => 'What surprised you most so far?',
                    'placeholder' => "Anything that was easier, harder, or just different from what you expected. Optional.",
                    'required' => false,
                    'rows' => 2,
                ],
            ],
        ],

        [
            'key' => 'midpoint',
            'after_module' => 'module-05',
            'enabled' => true,
            'eyebrow' => "You're past the halfway mark",
            'headline' => 'Five modules in — how is it really going?',
            'intro' => "Same deal: short, honest, no pressure. Your answers go straight to AJ.",
            'questions' => [
                [
                    'key' => 'hardest',
                    'label' => "What's been the hardest part — and what got you through it?",
                    'placeholder' => "The bit where you nearly gave up, and what actually unstuck you.",
                    'required' => true,
                    'rows' => 3,
                ],
                [
                    'key' => 'real_use',
                    'label' => 'Have you used any of this for real yet?',
                    'placeholder' => "Your own work, a client, a side hustle, a friend's business — or 'not yet', which is a completely fine answer.",
                    'required' => true,
                    'rows' => 3,
                ],
                [
                    'key' => 'worth_it',
                    'label' => "If a friend asked you 'is it worth the money?', what would you tell them?",
                    'placeholder' => "Say it the way you'd actually say it to them.",
                    'required' => true,
                    'rows' => 3,
                ],
            ],
        ],

        [
            'key' => 'finish',
            'after_module' => 'module-09',
            'enabled' => true,
            'eyebrow' => "You finished the build track",
            'headline' => 'Nine modules. Nine workflows. Last ask.',
            'intro' => "This is the big one — and the last time we'll ask. Take five minutes if you can.",
            'questions' => [
                [
                    'key' => 'outcome',
                    'label' => "What can you do now that you couldn't do before you started?",
                    'placeholder' => "The honest version — skills, confidence, tools, whatever changed.",
                    'required' => true,
                    'rows' => 3,
                ],
                [
                    'key' => 'result',
                    'label' => 'Any concrete result yet?',
                    'placeholder' => "A client, a paid job, an interview, a process you automated, hours saved a week. 'Not yet' is fine — we'd rather know.",
                    'required' => false,
                    'rows' => 3,
                ],
                [
                    'key' => 'who_for',
                    'label' => 'Who should join the next cohort? Describe them in one line.',
                    'placeholder' => "e.g. someone with a small business drowning in WhatsApp messages",
                    'required' => true,
                    'rows' => 2,
                ],
                [
                    'key' => 'one_line',
                    'label' => "One line to describe the Accelerator to someone exactly like you were in week 1:",
                    'placeholder' => "Your words, not marketing words.",
                    'required' => true,
                    'rows' => 2,
                ],
            ],
        ],

    ],
];
