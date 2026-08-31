<?php

use function Livewire\Volt\{state, layout, mount};
use App\Models\Enrollment;
use App\Models\Checkpoint;
use App\Models\LiveAttendance;
use App\Models\StudentReview;
use Carbon\Carbon;
use Illuminate\Support\Facades\URL;

layout('components.layouts.dashboard');

state([
    'activeSection' => 0,
    'activeModule' => 0,
    'activeVideo' => 0,
    'currentVideoId' => '',
    'curriculum' => [],
    'completedLessons' => [],
    'isLocked' => false,
    'lockReason' => 'open',        // open | date | checkpoint
    'unlockLabel' => '',           // date string, or the title of the module that gates this one
    // Ship-to-unlock state
    'shipToUnlock' => false,       // false for Cohort 1 (legacy/open)
    'cohort' => null,              // THIS student's cohort — the module-01 date floor is
                                   // theirs, never the cohort currently being sold
    'approvedModuleIds' => [],
    'checkpoints' => [],           // module_id => ['status','proof_url','note']
    'lockMap' => [],               // "sIndex-mIndex" => ['locked','reason','label']
    'proofUrl' => '',              // bound to the submit form for the active module
    'telegramUrl' => '',           // group-level fallback link
    'telegramThreads' => [],       // module_id => per-module #help thread (questions)
    'telegramWinsUrl' => '',       // #wins thread — where build proof / checkpoints go
    'liveAttendance' => [],        // session_key[] the student has marked attendance for
    'attendanceCode' => '',        // bound to the live-attendance form
    'balanceNotice' => null,       // installment balance reminder shown from 3 days before due
    // Staged review "soft ask" (config/reviews.php)
    'reviewPrompt' => null,        // the one stage currently due, or null
    'reviewAnswers' => [],         // question key => free text
    'reviewRating' => 0,           // 1–5; 0 = not chosen yet
    'reviewConsent' => false,      // may we quote this publicly
    'reviewCreditAs' => 'full',    // full | first | anon
    'reviewThanks' => false,       // show the thank-you state after submitting
]);

// Build the embeddable Bunny URL for a given video (videos inherit the module's library_id).
$updateVideoSource = function ($video, $module = []) {
    $videoId = trim($video['video_id'] ?? '');
    $libraryId = $video['library_id'] ?? ($module['library_id'] ?? config('services.bunny.library_id'));

    if (!$libraryId || !$videoId || $videoId === 'welcome_video_id' || str_contains($videoId, 'bunny_video_id')) {
        return "";
    }

    return "https://iframe.mediadelivery.net/embed/{$libraryId}/{$videoId}?autoplay=true&loop=false&muted=false&preload=true&responsive=true&context=true";
};

// Ensure every module has a 'videos' array (wrap legacy single-video modules).
$normalizeModule = function ($module) {
    if (empty($module['videos']) || !is_array($module['videos'])) {
        $module['videos'] = [[
            'id' => $module['id'] ?? uniqid('video-'),
            'title' => $module['title'] ?? 'Lesson',
            'video_id' => $module['video_id'] ?? '',
            'duration' => $module['duration'] ?? '',
            'library_id' => $module['library_id'] ?? null,
        ]];
    }
    // Never expose the live-session attendance code (or the gated playbook) to the
    // browser — Livewire serializes public state to the client. Both are read
    // server-side from config('curriculum') when needed.
    unset($module['attendance_code'], $module['playbook_url']);

    return $module;
};

/*
 * Work out whether a review stage is currently due for this student.
 *
 * A stage is due when the module it hangs off has an APPROVED checkpoint — i.e.
 * they've verifiably shipped — and they haven't already answered it or declined
 * it too many times. We walk the stages BACKWARDS so the most recent milestone
 * wins: someone who blew past module 05 without answering the module 01 ask gets
 * the midpoint questions, not stale ones.
 *
 * Cohort 1 (legacy/open, no checkpoints) is never asked — there's no verified
 * "you just shipped" moment to hang the ask on.
 */
$resolveReviewPrompt = function () {
    $this->reviewPrompt = null;

    if (! $this->shipToUnlock || $this->reviewThanks) {
        return;
    }

    $enrollment = Enrollment::where('email', auth()->user()->email)->first();
    if (! $enrollment) {
        return;
    }

    $existing = $enrollment->reviews()->get()->keyBy('stage');
    $snoozeDays = (int) config('reviews.snooze_days', 5);
    $maxDismissals = (int) config('reviews.max_dismissals', 2);

    foreach (array_reverse(config('reviews.stages', [])) as $stage) {
        if (! ($stage['enabled'] ?? false)) continue;
        if (! in_array($stage['after_module'] ?? '', $this->approvedModuleIds, true)) continue;

        $row = $existing[$stage['key']] ?? null;

        if ($row) {
            if ($row->isSubmitted()) continue;
            if ($row->dismiss_count >= $maxDismissals) continue;
            if ($row->dismissed_at && now()->lt($row->dismissed_at->copy()->addDays($snoozeDays))) continue;
        }

        $this->reviewPrompt = $stage;

        // Seed a blank answer for every question so wire:model has somewhere to bind.
        $keys = collect($stage['questions'])->pluck('key')->push('improve');
        $this->reviewAnswers = $keys->mapWithKeys(fn ($k) => [$k => $this->reviewAnswers[$k] ?? ''])->all();

        return;
    }
};

// Load this student's progress + checkpoint state from the DB.
$loadProgress = function () {
    $enrollment = Enrollment::where('email', auth()->user()->email)->first();

    $this->completedLessons = is_array($enrollment?->completed_lessons) ? $enrollment->completed_lessons : [];
    $this->shipToUnlock = $enrollment ? $enrollment->usesShipToUnlock() : false;
    $this->cohort = $enrollment ? (int) $enrollment->cohort : null;

    $cps = $enrollment ? $enrollment->checkpoints()->get() : collect();
    $this->checkpoints = $cps->mapWithKeys(fn($c) => [
        $c->module_id => ['status' => $c->status, 'proof_url' => $c->proof_url, 'note' => $c->note],
    ])->all();
    $this->approvedModuleIds = $cps->where('status', 'approved')->pluck('module_id')->all();

    $this->liveAttendance = $enrollment ? $enrollment->liveAttendances()->pluck('session_key')->all() : [];

    // Installment balance reminder — surfaces from 3 days before the due date,
    // while the student still has access (suspended students never reach here).
    $this->balanceNotice = null;
    if ($enrollment
        && $enrollment->plan_type === 'installment'
        && (float) $enrollment->balance_due > 0
        && $enrollment->second_payment_status !== 'paid'
        && $enrollment->second_payment_due_at
    ) {
        $due = $enrollment->second_payment_due_at;
        if (now()->gte($due->copy()->subDays(3))) {
            $this->balanceNotice = [
                'amount'  => (float) $enrollment->balance_due,
                'symbol'  => ($enrollment->currency ?: 'NGN') === 'NGN' ? '₦' : '$',
                'overdue' => now()->gt($due),
                'due'     => $due->diffForHumans(),
                'pay_url' => URL::signedRoute('installment.pay', ['enrollment' => $enrollment->id]),
            ];
        }
    }

    $this->resolveReviewPrompt();
};

/*
 * Compute the lock state of every module.
 *  - Cohort 1 (legacy/open): nothing is ever locked.
 *  - Cohort 2+ (ship-to-unlock): Core Training is proof-gated — module 1 is gated
 *    only by THIS STUDENT'S cohort start floor (see Accelerator::startFloorFor —
 *    past cohorts have no floor), every later module unlocks when the PREVIOUS
 *    module's checkpoint is approved. The Live Archive stays date-gated.
 */
$rebuildGate = function () {
    $now = Carbon::now('Africa/Lagos');
    // Resolved against THIS student's cohort: a past-cohort student has no date floor,
    // so scheduling the next cohort can never re-lock module 01 under them.
    $start = \App\Support\Accelerator::startFloorFor($this->cohort);
    $map = [];

    foreach ($this->curriculum as $sIndex => $section) {
        $isCore = ($section['title'] === 'Core Training');

        foreach ($section['modules'] as $mIndex => $module) {
            $key = "{$sIndex}-{$mIndex}";

            if (!$this->shipToUnlock) {
                $map[$key] = ['locked' => false, 'reason' => 'open', 'label' => ''];
                continue;
            }

            if ($isCore) {
                if ($mIndex === 0) {
                    $locked = $start ? $now->lt($start) : false;
                    $map[$key] = ['locked' => $locked, 'reason' => 'date', 'label' => $start ? $start->format('M d, Y') : ''];
                } else {
                    $prev = $section['modules'][$mIndex - 1];
                    $approved = in_array($prev['id'] ?? '', $this->approvedModuleIds, true);
                    $map[$key] = ['locked' => !$approved, 'reason' => 'checkpoint', 'label' => $prev['title'] ?? 'the previous module'];
                }
            } else {
                $releaseAt = isset($module['release_at'])
                    ? Carbon::parse($module['release_at'], 'Africa/Lagos')
                    : $now->copy()->subDay();
                $map[$key] = ['locked' => $releaseAt->isFuture(), 'reason' => 'date', 'label' => $releaseAt->format('M d, Y @ h:i A')];
            }
        }
    }

    $this->lockMap = $map;
};

// Sync the main-stage player + lock banner + proof field to the active module/video.
$applyActiveLock = function () {
    $module = $this->curriculum[$this->activeSection]['modules'][$this->activeModule] ?? null;
    $video  = $module['videos'][$this->activeVideo] ?? null;

    $info = $this->lockMap["{$this->activeSection}-{$this->activeModule}"] ?? ['locked' => false, 'reason' => 'open', 'label' => ''];
    $this->isLocked = $info['locked'];
    $this->lockReason = $info['reason'];
    $this->unlockLabel = $info['label'];
    $this->currentVideoId = (!$info['locked'] && $video) ? $this->updateVideoSource($video, $module) : '';

    // Pre-fill the proof field with any existing submission for this module.
    $this->proofUrl = $module ? ($this->checkpoints[$module['id']]['proof_url'] ?? '') : '';
};

mount(function () use ($normalizeModule) {
    $this->telegramUrl = config('accelerator.telegram_community_url') ?? '';
    $this->telegramThreads = config('accelerator.telegram_threads', []);
    $this->telegramWinsUrl = config('accelerator.telegram_wins_url') ?? '';

    // 1. Load + normalize curriculum into sections of modules
    $rawConfig = config('curriculum') ?? [];
    $this->curriculum = [];

    $buildSection = function ($title, $modules) use ($normalizeModule) {
        return [
            'title' => $title,
            'modules' => array_map($normalizeModule, array_values($modules)),
        ];
    };

    if (isset($rawConfig['core']) || isset($rawConfig['live'])) {
        if (!empty($rawConfig['core'])) {
            $this->curriculum[] = $buildSection('Core Training', $rawConfig['core']);
        }
        if (!empty($rawConfig['live'])) {
            $this->curriculum[] = $buildSection('Live Archive', $rawConfig['live']);
        }
    } elseif (!empty($rawConfig)) {
        $this->curriculum[] = $buildSection('Course Roadmap', $rawConfig);
    }

    // 2. Progress + gate
    $this->loadProgress();
    $this->rebuildGate();

    // 3. Initialize the first video of the first module
    if (!empty($this->curriculum[0]['modules'][0]['videos'][0])) {
        $this->applyActiveLock();
    }
});

$selectVideo = function ($sIndex, $mIndex, $vIndex) {
    $module = $this->curriculum[$sIndex]['modules'][$mIndex] ?? null;
    $video = $module['videos'][$vIndex] ?? null;

    if (!$module || !$video) {
        return;
    }

    $this->activeSection = $sIndex;
    $this->activeModule = $mIndex;
    $this->activeVideo = $vIndex;

    $this->applyActiveLock();
};

$toggleComplete = function ($videoId) {
    if ($this->isLocked) return;

    $enrollment = Enrollment::where('email', auth()->user()->email)->first();
    if (!$enrollment) return;

    $completed = collect($this->completedLessons);

    if ($completed->contains($videoId)) {
        $completed = $completed->reject(fn($id) => $id === $videoId);
    } else {
        $completed->push($videoId);
    }

    $this->completedLessons = $completed->values()->all();
    $enrollment->update(['completed_lessons' => $this->completedLessons]);
};

// Ship-to-unlock: submit (or resubmit) the proof checkpoint for the active module.
$submitCheckpoint = function () {
    if (!$this->shipToUnlock || $this->isLocked) return;

    $module = $this->curriculum[$this->activeSection]['modules'][$this->activeModule] ?? null;
    if (!$module) return;

    // Only Core Training modules carry checkpoints.
    if (($this->curriculum[$this->activeSection]['title'] ?? '') !== 'Core Training') return;

    $this->validate(['proofUrl' => 'required|url|max:2048']);

    $enrollment = Enrollment::where('email', auth()->user()->email)->first();
    if (!$enrollment) return;

    Checkpoint::updateOrCreate(
        ['enrollment_id' => $enrollment->id, 'module_id' => $module['id']],
        ['status' => 'submitted', 'proof_url' => $this->proofUrl, 'note' => null, 'submitted_at' => now(), 'reviewed_at' => null],
    );

    $this->loadProgress();
    $this->rebuildGate();
    $this->applyActiveLock();
};

// Live sessions: mark attendance by entering the code AJ announces at the end of
// the call. The code is validated server-side against config (never sent to the
// client), so knowing it ≈ having been there.
$markAttendance = function () {
    $module = $this->curriculum[$this->activeSection]['modules'][$this->activeModule] ?? null;
    if (!$module) return;
    if (($this->curriculum[$this->activeSection]['title'] ?? '') !== 'Live Archive') return;

    $sessionKey = $module['id'] ?? null;
    $cfg = collect(config('curriculum.live', []))->firstWhere('id', $sessionKey);
    $code = $cfg['attendance_code'] ?? null;
    if (!$code) return; // attendance not open for this session

    $this->validate(['attendanceCode' => 'required|string|max:100']);

    if (strcasecmp(trim($this->attendanceCode), trim($code)) !== 0) {
        $this->addError('attendanceCode', "That code isn't right — listen for it at the end of the live session.");
        return;
    }

    $enrollment = Enrollment::where('email', auth()->user()->email)->first();
    if (!$enrollment) return;

    LiveAttendance::firstOrCreate(
        ['enrollment_id' => $enrollment->id, 'session_key' => $sessionKey],
        ['attended_at' => now()],
    );

    $this->attendanceCode = '';
    $this->loadProgress();
};

/*
 * Staged review: save the answers. Nothing here gates or unlocks anything —
 * if this whole feature breaks, a student's progress is untouched.
 *
 * `consent_public` is only ever true when the student is HAPPY and ticked the
 * box. An unhappy score can't produce a quotable row no matter what's posted,
 * because the consent UI isn't rendered for them at all.
 */
$submitReview = function () {
    $stage = $this->reviewPrompt;
    if (! $stage) return;

    $unhappyAt = (int) config('reviews.unhappy_at_or_below', 3);
    $isUnhappy = $this->reviewRating > 0 && $this->reviewRating <= $unhappyAt;

    $rules = ['reviewRating' => 'required|integer|min:1|max:5'];
    $messages = ['reviewRating.required' => 'Pick a score first — it takes one tap.'];

    foreach ($stage['questions'] as $q) {
        $required = ($q['required'] ?? false) ? 'required' : 'nullable';
        $rules["reviewAnswers.{$q['key']}"] = "{$required}|string|max:1500";
        $messages["reviewAnswers.{$q['key']}.required"] = 'A sentence is plenty — but this one we do need.';
    }

    if ($isUnhappy) {
        $rules['reviewAnswers.improve'] = 'required|string|max:1500';
        $messages['reviewAnswers.improve.required'] = "Tell us what's not working — that's the whole point of asking.";
    }

    $this->validate($rules, $messages);

    $enrollment = Enrollment::where('email', auth()->user()->email)->first();
    if (! $enrollment) return;

    // Store only the keys this stage actually asked about.
    $keys = collect($stage['questions'])->pluck('key');
    if ($isUnhappy) {
        $keys->push('improve');
    }
    $answers = $keys->mapWithKeys(fn ($k) => [$k => trim((string) ($this->reviewAnswers[$k] ?? ''))])
        ->filter(fn ($v) => $v !== '')
        ->all();

    $existing = $enrollment->reviews()->where('stage', $stage['key'])->first();

    StudentReview::updateOrCreate(
        ['enrollment_id' => $enrollment->id, 'stage' => $stage['key']],
        [
            'status' => 'submitted',
            'rating' => $this->reviewRating,
            'answers' => $answers,
            'consent_public' => ! $isUnhappy && (bool) $this->reviewConsent,
            'credit_as' => $isUnhappy ? null : $this->reviewCreditAs,
            'dismiss_count' => $existing->dismiss_count ?? 0,
            'submitted_at' => now(),
        ],
    );

    $this->reviewThanks = true;
    $this->reviewPrompt = null;
    $this->reviewAnswers = [];
    $this->reviewRating = 0;
    $this->reviewConsent = false;
};

// "Not now" — the soft part of the soft ask. Snoozes the stage; after
// `max_dismissals` declines we stop asking it entirely.
$dismissReview = function () {
    $stage = $this->reviewPrompt;
    if (! $stage) return;

    $enrollment = Enrollment::where('email', auth()->user()->email)->first();
    if (! $enrollment) return;

    $row = StudentReview::firstOrNew([
        'enrollment_id' => $enrollment->id,
        'stage' => $stage['key'],
    ]);

    $row->fill([
        'status' => 'dismissed',
        'dismiss_count' => (int) $row->dismiss_count + 1,
        'dismissed_at' => now(),
    ])->save();

    $this->reviewPrompt = null;
};

?>

<div class="flex h-screen w-full bg-zinc-950 overflow-hidden" x-data="{ mobileMenuOpen: false }">

    <!-- MOBILE OVERLAY -->
    <div x-show="mobileMenuOpen" class="fixed inset-0 z-40 bg-zinc-950/90 backdrop-blur-sm lg:hidden" @click="mobileMenuOpen = false" x-cloak></div>

    @php
        // Resolve the current module/video for the main stage (guarded).
        $curModule = $curriculum[$activeSection]['modules'][$activeModule] ?? null;
        $curVideo  = $curModule['videos'][$activeVideo] ?? null;

        // Foundry progress = completed videos across the Core Training section.
        $coreModules  = $curriculum[0]['modules'] ?? [];
        $coreVideoIds = collect($coreModules)->flatMap(fn($m) => collect($m['videos'])->pluck('id'))->all();
        $totalItems   = count($coreVideoIds);
        $completedCount = count(array_intersect($coreVideoIds, $completedLessons));
        $percent = ($totalItems > 0) ? ($completedCount / $totalItems) * 100 : 0;

        // Live-session attendance for the active session (code + playbook read
        // server-side from config, never from client state).
        $isLiveSection  = ($curriculum[$activeSection]['title'] ?? '') === 'Live Archive';
        $liveSessionKey = $curModule['id'] ?? null;
        $liveCfg        = $isLiveSection ? collect(config('curriculum.live', []))->firstWhere('id', $liveSessionKey) : null;
        $hasAttendanceCode = ! empty($liveCfg['attendance_code']);
        $alreadyAttended   = in_array($liveSessionKey, $liveAttendance, true);
        $playbookUrl       = $liveCfg['playbook_url'] ?? null;

        // Snippets shared for this module (plus any global ones). Read-only, so
        // it's resolved per render rather than held in Livewire state.
        $snippets = ($curModule && ! $isLocked)
            ? \App\Models\Snippet::visibleFor($curModule['id'])
            : collect();

        // Completion-guarantee progress: all core checkpoints approved + enough
        // live sessions attended. Display-only; the guarantee is honoured by a human.
        $coreModuleIds     = collect($coreModules)->pluck('id')->all();
        $coreTotal         = count($coreModuleIds);
        $approvedCount     = count(array_intersect($coreModuleIds, $approvedModuleIds));
        $liveAttendedCount = count($liveAttendance);
        $guaranteeMinLive  = (int) config('accelerator.guarantee_min_live_sessions', 0);
        $guaranteeMet      = $shipToUnlock && $coreTotal > 0 && $approvedCount === $coreTotal && $liveAttendedCount >= $guaranteeMinLive;
    @endphp

    <!-- SIDEBAR -->
    <aside
        class="fixed inset-y-0 left-0 z-50 w-80 bg-zinc-900 border-r border-zinc-800 flex flex-col transition-transform duration-300 transform lg:translate-x-0 lg:static lg:inset-0"
        :class="mobileMenuOpen ? 'translate-x-0' : '-translate-x-full'"
    >
        <div class="h-16 flex items-center justify-between px-6 border-b border-zinc-800 bg-zinc-950/50">
            <div class="text-sm font-black tracking-tighter italic text-white uppercase">
                AJBUILD<span class="text-cyan-500">AI</span>
            </div>
            <button @click="mobileMenuOpen = false" class="lg:hidden text-zinc-500 hover:text-white">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>

        <nav class="flex-1 overflow-y-auto custom-scrollbar p-4 space-y-8">
            @foreach($curriculum as $sIndex => $section)
                <div class="space-y-3">
                    <h3 class="px-2 text-[10px] font-black uppercase tracking-[0.2em] text-zinc-500 italic">
                        {{ $section['title'] }}
                    </h3>

                    <div class="space-y-2">
                        @foreach($section['modules'] as $mIndex => $module)
                            @php
                                $moduleLocked = $lockMap["{$sIndex}-{$mIndex}"]['locked'] ?? false;
                                $moduleVideoIds = collect($module['videos'])->pluck('id')->all();
                                $moduleDone = $totalVids = count($moduleVideoIds);
                                $moduleCompletedCount = count(array_intersect($moduleVideoIds, $completedLessons));
                                $moduleComplete = $totalVids > 0 && $moduleCompletedCount === $totalVids;
                                $isActiveModule = ($activeSection === $sIndex && $activeModule === $mIndex);
                            @endphp

                            <div x-data="{ open: {{ $isActiveModule ? 'true' : 'false' }} }" class="rounded-lg border {{ $isActiveModule ? 'border-cyan-500/20 bg-cyan-500/5' : 'border-zinc-800/60 bg-zinc-900/30' }}">
                                <!-- Module header (toggles videos) -->
                                <button type="button" @click="open = !open"
                                    class="w-full flex items-center gap-3 px-3 py-3 text-left">
                                    <div class="h-6 w-6 shrink-0 rounded border flex items-center justify-center text-[10px] font-mono
                                        {{ $moduleComplete ? 'bg-green-500/20 border-green-500/50 text-green-500' :
                                           ($moduleLocked ? 'border-zinc-800 text-zinc-700 bg-zinc-900/50' : 'border-zinc-700 text-zinc-500 bg-zinc-800') }}">
                                        @if($moduleComplete)
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                        @elseif($moduleLocked)
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                                        @else
                                            {{ $mIndex + 1 }}
                                        @endif
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-[11px] font-bold uppercase tracking-tight truncate {{ $moduleLocked ? 'text-zinc-600' : 'text-zinc-300' }}">
                                            {{ $module['title'] }}
                                        </p>
                                        <p class="text-[9px] font-mono text-zinc-600 tracking-tighter">
                                            {{ $moduleCompletedCount }}/{{ $totalVids }} {{ \Illuminate\Support\Str::plural('video', $totalVids) }}
                                        </p>
                                    </div>
                                    <svg class="w-3.5 h-3.5 text-zinc-600 transition-transform shrink-0" :class="open ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                </button>

                                <!-- Videos within the module -->
                                <div x-show="open" x-cloak x-transition class="pb-2 pl-3 pr-2 space-y-1">
                                    @foreach($module['videos'] as $vIndex => $video)
                                        @php $videoComplete = in_array($video['id'], $completedLessons); @endphp
                                        <button
                                            wire:click="selectVideo({{ $sIndex }}, {{ $mIndex }}, {{ $vIndex }})"
                                            @click="mobileMenuOpen = false"
                                            class="w-full flex items-center gap-3 pl-2 pr-2 py-2 rounded-md transition-all text-left group {{ ($isActiveModule && $activeVideo === $vIndex) ? 'bg-cyan-500/10 border border-cyan-500/20' : 'border border-transparent hover:bg-zinc-800/50' }}"
                                        >
                                            <div class="h-5 w-5 shrink-0 rounded-full border flex items-center justify-center text-[9px] font-mono
                                                {{ $videoComplete ? 'bg-green-500/20 border-green-500/50 text-green-500' :
                                                   ($moduleLocked ? 'border-zinc-800 text-zinc-700' :
                                                   (($isActiveModule && $activeVideo === $vIndex) ? 'bg-cyan-500 border-cyan-400 text-black font-bold' : 'border-zinc-700 text-zinc-600')) }}">
                                                @if($videoComplete)
                                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                                @elseif($moduleLocked)
                                                    <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                                                @else
                                                    {{ $vIndex + 1 }}
                                                @endif
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-[11px] font-medium tracking-tight truncate {{ ($isActiveModule && $activeVideo === $vIndex) ? 'text-white' : ($moduleLocked ? 'text-zinc-600' : 'text-zinc-400 group-hover:text-zinc-200') }}">
                                                    {{ $video['title'] }}
                                                </p>
                                                @if(!empty($video['duration']))
                                                    <p class="text-[9px] font-mono text-zinc-600 tracking-tighter">{{ $video['duration'] }}</p>
                                                @endif
                                            </div>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </nav>

        <div class="p-4 border-t border-zinc-800 bg-zinc-950/50 mt-auto space-y-3">
            <div class="flex items-center gap-3">
                <div class="h-8 w-8 rounded bg-cyan-600 flex items-center justify-center text-xs font-black text-white uppercase shrink-0">
                    {{ substr(auth()->user()->name ?? 'U', 0, 1) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-[10px] font-bold text-white truncate uppercase tracking-wider">{{ auth()->user()->name ?? 'Builder' }}</p>
                    <p class="text-[8px] text-zinc-500 font-mono uppercase tracking-widest leading-none mt-1">Status: Online</p>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="shrink-0">
                    @csrf
                    <button type="submit" class="text-[9px] font-black text-zinc-600 hover:text-red-500 transition uppercase tracking-widest">
                        Log out
                    </button>
                </form>
            </div>
            @if(!empty($telegramUrl))
                <a href="{{ $telegramUrl }}" target="_blank"
                   class="flex items-center justify-center gap-2 w-full py-2 rounded-lg border border-zinc-800 bg-zinc-950/40 text-[10px] font-black text-cyan-500 hover:border-cyan-500/40 hover:bg-cyan-500/5 transition uppercase tracking-widest">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M9.78 18.65l.28-4.23 7.68-6.92c.34-.31-.07-.46-.52-.19L7.74 13.3 3.64 12c-.88-.25-.89-.86.2-1.3l15.97-6.16c.73-.33 1.43.18 1.15 1.3l-2.72 12.81c-.19.91-.74 1.13-1.5.71L12.6 16.3l-1.99 1.93c-.23.23-.42.42-.83.42z"/></svg>
                    Telegram Community
                </a>
            @endif
        </div>
    </aside>

    <!-- MAIN STAGE -->
    <main class="flex-1 flex flex-col min-w-0 h-full overflow-hidden">
        <header class="h-16 flex items-center justify-between px-6 lg:px-8 border-b border-zinc-800 bg-zinc-950/80 backdrop-blur sticky top-0 z-20">
            <div class="flex items-center gap-4">
                <button @click="mobileMenuOpen = true" class="lg:hidden p-2 -ml-2 text-zinc-400 hover:text-white transition">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                </button>
                <div class="hidden sm:block text-[10px] font-mono font-bold text-zinc-500 uppercase tracking-[0.2em] border border-zinc-800 px-2 py-1 rounded">
                    Terminal // Mod_{{ $activeModule + 1 }}.{{ $activeVideo + 1 }}
                </div>
                <div class="sm:hidden text-[10px] font-mono font-bold text-cyan-500 uppercase tracking-widest">
                    M{{ $activeModule + 1 }}.{{ $activeVideo + 1 }}
                </div>
            </div>

            <!-- Telegram + logout now live in the sidebar footer (visible on mobile via the menu). -->
        </header>

        <div class="flex-1 overflow-y-auto p-4 lg:p-12 custom-scrollbar">
            <div class="max-w-5xl mx-auto space-y-8">

                <!-- INSTALLMENT BALANCE NOTICE -->
                @if($balanceNotice)
                    <div class="rounded-2xl border p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4
                        {{ $balanceNotice['overdue'] ? 'border-red-500/40 bg-red-500/5' : 'border-amber-500/40 bg-amber-500/5' }}">
                        <div>
                            <div class="text-[10px] font-black uppercase tracking-widest mb-1 {{ $balanceNotice['overdue'] ? 'text-red-400' : 'text-amber-400' }}">
                                {{ $balanceNotice['overdue'] ? 'Balance overdue' : 'Installment balance due' }}
                            </div>
                            <p class="text-sm text-zinc-300 leading-snug">
                                @if($balanceNotice['overdue'])
                                    Your final payment of <strong class="text-white">{{ $balanceNotice['symbol'] }}{{ number_format($balanceNotice['amount']) }}</strong> is overdue ({{ $balanceNotice['due'] }}). Pay now to keep your access active.
                                @else
                                    Your final payment of <strong class="text-white">{{ $balanceNotice['symbol'] }}{{ number_format($balanceNotice['amount']) }}</strong> is due {{ $balanceNotice['due'] }}. Clear it to avoid any interruption to your access.
                                @endif
                            </p>
                        </div>
                        <a href="{{ $balanceNotice['pay_url'] }}" class="shrink-0 inline-block text-center px-6 py-3 rounded-xl bg-cyan-500 text-black font-black uppercase tracking-tighter text-sm hover:bg-white transition-all">
                            Pay {{ $balanceNotice['symbol'] }}{{ number_format($balanceNotice['amount']) }}
                        </a>
                    </div>
                @endif

                <!-- DYNAMIC PLAYER AREA -->
                <div class="relative w-full bg-black border border-zinc-800 rounded-2xl overflow-hidden shadow-2xl"
                     style="padding-bottom: 56.25%;"
                     wire:key="player-area-{{ $activeSection }}-{{ $activeModule }}-{{ $activeVideo }}">

                    @if($isLocked)
                        <!-- LOCKED STATE -->
                        <div class="absolute inset-0 flex flex-col items-center justify-center text-center bg-zinc-900/80 backdrop-blur-sm p-6">
                            <div class="w-16 h-16 rounded-full bg-zinc-800 flex items-center justify-center mb-4 border border-zinc-700">
                                <svg class="w-8 h-8 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            </div>
                            <h3 class="text-xl font-black text-white uppercase italic tracking-tighter">
                                {{ $lockReason === 'checkpoint' ? 'Ship to Unlock' : 'Classified Module' }}
                            </h3>
                            <p class="text-xs text-zinc-400 font-mono mt-2 uppercase tracking-widest max-w-sm">
                                @if($lockReason === 'checkpoint')
                                    Unlocks when your <span class="text-cyan-500">{{ $unlockLabel }}</span> proof checkpoint is approved.
                                @else
                                    Unlocks: {{ $unlockLabel }}
                                @endif
                            </p>
                        </div>

                    @elseif(!empty($curVideo['guide_url']))
                        <!-- WRITTEN GUIDE LESSON -->
                        <div class="absolute inset-0 flex flex-col items-center justify-center text-center bg-zinc-950 p-6">
{{-- Defaults suit a follow-along guide. A lesson that is something else - the
                                 capstone brief, say - overrides them in config/curriculum.php. --}}
                            <div class="text-4xl mb-3">{{ $curVideo['guide_icon'] ?? '📘' }}</div>
                            <h3 class="text-lg font-black text-white uppercase italic tracking-tighter">{{ $curVideo['guide_heading'] ?? 'Written step-by-step guide' }}</h3>
                            <p class="text-xs text-zinc-400 mt-2 max-w-sm leading-relaxed">{{ $curVideo['guide_blurb'] ?? 'This lesson is a hands-on guide you follow at your own pace - copy-paste, nothing skipped.' }}</p>
                            <a href="{{ $curVideo['guide_url'] }}" target="_blank" rel="noopener"
                               class="inline-flex items-center gap-2 mt-5 px-6 py-3 rounded-xl bg-cyan-500 text-black font-black uppercase tracking-tighter text-sm hover:bg-white transition-all">
                                {{ $curVideo['guide_cta'] ?? 'Open the guide' }} →
                            </a>
                        </div>

                    @elseif($currentVideoId)
                        <!-- ACTIVE BUNNY PLAYER -->
                        <iframe
                            src="{{ $currentVideoId }}"
                            loading="lazy"
                            class="absolute top-0 left-0 w-full h-full border-none"
                            allow="accelerometer; gyroscope; autoplay; encrypted-media; picture-in-picture; clipboard-write;"
                            allowfullscreen="true">
                        </iframe>

                    @else
                        <!-- UPLOAD PENDING STATE (No 404) -->
                        <div class="absolute inset-0 flex flex-col items-center justify-center text-zinc-600 bg-zinc-950">
                            <span class="text-[10px] font-mono uppercase tracking-widest animate-pulse">Stream Offline</span>
                            <span class="text-[9px] font-mono text-zinc-100 mt-1 uppercase tracking-widest text-center">This video is being prepared. <br>Check back shortly.</span>
                        </div>
                    @endif
                </div>

                @if($curModule && $curVideo)
                <!-- INFO GRID -->
                <div class="grid lg:grid-cols-3 gap-10">
                    <div class="lg:col-span-2 space-y-6">
                        <div class="flex items-center gap-3 flex-wrap">
                            @if(in_array($curVideo['id'], $completedLessons))
                                <span class="px-2 py-0.5 rounded bg-green-500/10 text-green-500 border border-green-500/20 text-[9px] font-black uppercase tracking-widest flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                    Video Verified
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded {{ $isLocked ? 'bg-zinc-800 text-zinc-500' : 'bg-cyan-500/10 text-cyan-500 border border-cyan-500/20' }} text-[9px] font-black uppercase tracking-widest">
                                    Status: {{ $isLocked ? 'Encrypted' : 'Sync_In_Progress' }}
                                </span>
                            @endif
                            <span class="text-[10px] font-mono text-zinc-600 uppercase">
                                {{ $curModule['title'] }}
                            </span>
                        </div>

                        <h1 class="text-3xl lg:text-5xl font-black text-white uppercase italic tracking-tighter leading-[0.9]">
                            {{ $curVideo['title'] }}
                        </h1>
                        <div class="prose prose-invert prose-sm text-zinc-400 font-medium leading-relaxed max-w-2xl whitespace-pre-line">
                            {{ $curVideo['description'] ?? $curModule['description'] ?? '' }}
                        </div>

                        <!-- Progress Button -->
                        <div class="pt-4">
                            @if(!$isLocked)
                                <button
                                    wire:click="toggleComplete('{{ $curVideo['id'] }}')"
                                    class="inline-flex items-center gap-3 px-6 py-3 rounded-xl border transition-all text-[11px] font-black uppercase tracking-widest
                                    {{ in_array($curVideo['id'], $completedLessons)
                                        ? 'bg-zinc-900 border-green-500/50 text-green-500 hover:bg-green-500/10'
                                        : 'bg-white text-black hover:bg-cyan-500' }}"
                                >
                                    @if(in_array($curVideo['id'], $completedLessons))
                                        Mark as Incomplete
                                    @else
                                        Mark Video Complete
                                    @endif
                                </button>
                            @else
                                <button disabled class="inline-flex items-center gap-3 px-6 py-3 rounded-xl border border-zinc-800 text-zinc-600 text-[11px] font-black uppercase tracking-widest cursor-not-allowed">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                    Protocol Locked
                                </button>
                            @endif
                        </div>
                    </div>

                    <!-- THE VAULT -->
                    <div class="space-y-6">
                        @if(!$isLocked && ($curModule['has_blueprint'] ?? false))
                            <div class="p-6 bg-zinc-900 border border-cyan-900/50 rounded-2xl relative overflow-hidden group shadow-lg">
                                <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-cyan-500 mb-3 flex items-center gap-2 relative z-10">
                                    <span class="h-1.5 w-1.5 rounded-full bg-cyan-500 animate-pulse"></span>
                                    Snapshot Vault
                                </h4>
                                <p class="text-[10px] text-zinc-400 mb-6 leading-relaxed">
                                    Access the technical blueprints for this module.
                                </p>
                                <a href="{{ $curModule['blueprint_url'] ?? 'https://drive.google.com/drive/folders/1-LCglWb4khbJEoN3Kqwdi-XVSWfYtR6v?usp=drive_link' }}"
                                   target="_blank"
                                   class="block w-full py-4 bg-white text-black text-center font-black uppercase text-[10px] tracking-widest rounded-lg hover:bg-cyan-500 transition-all relative z-10">
                                    Download JSON
                                </a>
                            </div>
                        @elseif($isLocked)
                            <div class="p-6 bg-zinc-900/30 border border-zinc-800 rounded-2xl flex items-center justify-center min-h-[120px]">
                                <p class="text-[9px] font-mono text-zinc-600 uppercase">Assets Encrypted</p>
                            </div>
                        @endif

                        <div class="p-6 border border-zinc-900 rounded-2xl bg-zinc-950/50">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-[9px] font-black uppercase text-zinc-500 tracking-widest">Foundry Progress</span>
                                <span class="text-[10px] font-mono text-cyan-500">{{ $completedCount }} / {{ $totalItems }}</span>
                            </div>
                            <div class="h-1.5 w-full bg-zinc-900 rounded-full overflow-hidden">
                                <div class="h-full bg-cyan-500 transition-all duration-700 shadow-[0_0_10px_#06b6d4]" style="width: {{ $percent }}%"></div>
                            </div>
                        </div>

                        {{-- Completion guarantee: checkpoints + live attendance (Cohort 2+) --}}
                        @if($shipToUnlock)
                            <div class="p-6 border rounded-2xl {{ $guaranteeMet ? 'border-green-500/30 bg-green-500/5' : 'border-zinc-900 bg-zinc-950/50' }}">
                                <div class="flex items-center justify-between mb-3">
                                    <span class="text-[9px] font-black uppercase text-zinc-500 tracking-widest">Completion Guarantee</span>
                                    @if($guaranteeMet)<span class="text-[9px] font-black uppercase text-green-500 tracking-widest">On track &check;</span>@endif
                                </div>
                                <div class="space-y-2">
                                    <div class="flex items-center justify-between text-[11px]">
                                        <span class="text-zinc-400">Checkpoints approved</span>
                                        <span class="font-mono {{ ($coreTotal > 0 && $approvedCount === $coreTotal) ? 'text-green-500' : 'text-zinc-300' }}">{{ $approvedCount }}/{{ $coreTotal }}</span>
                                    </div>
                                    <div class="flex items-center justify-between text-[11px]">
                                        <span class="text-zinc-400">Live sessions attended</span>
                                        <span class="font-mono {{ $liveAttendedCount >= $guaranteeMinLive ? 'text-green-500' : 'text-zinc-300' }}">{{ $liveAttendedCount }}/{{ $guaranteeMinLive }}</span>
                                    </div>
                                </div>
                                <p class="text-[10px] text-zinc-600 mt-3 leading-relaxed">Finish every checkpoint and attend {{ $guaranteeMinLive }} live sessions to lock in your completion guarantee.</p>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- SNIPPETS: prompts / code shared for this module --}}
                @include('livewire.dashboard.partials.snippets')

                {{-- STAGED REVIEW: the soft ask, due only after a checkpoint is approved --}}
                @include('livewire.dashboard.partials.review-prompt')

                {{-- SHIP-TO-UNLOCK: proof checkpoint panel (Core Training, Cohort 2+ only) --}}
                @php $isCoreSection = ($curriculum[$activeSection]['title'] ?? '') === 'Core Training'; @endphp
                @if($shipToUnlock && $isCoreSection && !$isLocked && $curModule)
                    @php
                        $cp = $checkpoints[$curModule['id']] ?? null;
                        $cpStatus = $cp['status'] ?? null;
                    @endphp
                    <div class="border rounded-2xl p-6 lg:p-8
                        {{ $cpStatus === 'approved' ? 'border-green-500/30 bg-green-500/5' : ($cpStatus === 'rejected' ? 'border-amber-500/30 bg-amber-500/5' : 'border-cyan-900/40 bg-zinc-900/40') }}">
                        <div class="flex items-center gap-2 mb-3">
                            <span class="h-1.5 w-1.5 rounded-full {{ $cpStatus === 'approved' ? 'bg-green-500' : 'bg-cyan-500' }} animate-pulse"></span>
                            <span class="text-[10px] font-black uppercase tracking-[0.2em] {{ $cpStatus === 'approved' ? 'text-green-500' : 'text-cyan-500' }}">Proof Checkpoint</span>
                        </div>

                        @if($cpStatus === 'approved')
                            <h3 class="text-lg font-black text-white uppercase italic tracking-tighter">Checkpoint approved &check;</h3>
                            <p class="text-xs text-zinc-400 mt-2 leading-relaxed">Nice work — the next module is unlocked. Keep shipping.</p>

                        @elseif($cpStatus === 'submitted')
                            <h3 class="text-lg font-black text-white uppercase italic tracking-tighter">Submitted — pending review</h3>
                            <p class="text-xs text-zinc-400 mt-2 leading-relaxed">We're reviewing your proof. The next module unlocks once it's approved. Need to fix your link? Resubmit below.</p>
                            @if(!empty($cp['proof_url']))
                                <a href="{{ $cp['proof_url'] }}" target="_blank" class="inline-block mt-3 text-[11px] font-bold text-cyan-500 hover:underline break-all">View your submission →</a>
                            @endif
                            @include('livewire.dashboard.partials.checkpoint-form', ['label' => 'Update link'])

                        @elseif($cpStatus === 'rejected')
                            <h3 class="text-lg font-black text-white uppercase italic tracking-tighter">Needs another look</h3>
                            <p class="text-xs text-amber-400 mt-2 leading-relaxed">{{ $cp['note'] ?: 'Your proof needs a tweak — please review and resubmit.' }}</p>
                            @include('livewire.dashboard.partials.checkpoint-form', ['label' => 'Resubmit proof'])

                        @else
                            <h3 class="text-lg font-black text-white uppercase italic tracking-tighter">Ship it to unlock the next module</h3>
                            <p class="text-xs text-zinc-400 mt-2 leading-relaxed">
                                Post a short screen-record (Loom) or screenshot proving your build works in <strong class="text-zinc-300">#wins</strong>, then paste the link below.
                            </p>
                            @php
                                $winsUrl = $telegramWinsUrl ?: $telegramUrl;
                                $helpUrl = $telegramThreads[$curModule['id']] ?? $telegramUrl;
                            @endphp
                            <div class="flex flex-wrap items-center gap-x-5 gap-y-2 mt-3">
                                @if(!empty($winsUrl))
                                    <a href="{{ $winsUrl }}" target="_blank" class="inline-flex items-center gap-2 text-[11px] font-bold text-cyan-500 hover:underline">Post your proof in #wins →</a>
                                @endif
                                @if(!empty($helpUrl))
                                    <a href="{{ $helpUrl }}" target="_blank" class="inline-flex items-center gap-2 text-[11px] font-bold text-zinc-400 hover:text-cyan-500 hover:underline">Stuck? Ask in the {{ $curModule['title'] ?? 'module' }} thread →</a>
                                @endif
                            </div>
                            @include('livewire.dashboard.partials.checkpoint-form', ['label' => 'Submit proof'])
                        @endif
                    </div>
                @endif

                {{-- LIVE ATTENDANCE: mark with the code AJ announces at the end of the session --}}
                @if($isLiveSection && !$isLocked && $curModule)
                    <div class="border rounded-2xl p-6 lg:p-8 {{ $alreadyAttended ? 'border-green-500/30 bg-green-500/5' : 'border-cyan-900/40 bg-zinc-900/40' }}">
                        <div class="flex items-center gap-2 mb-3">
                            <span class="h-1.5 w-1.5 rounded-full {{ $alreadyAttended ? 'bg-green-500' : 'bg-cyan-500' }} animate-pulse"></span>
                            <span class="text-[10px] font-black uppercase tracking-[0.2em] {{ $alreadyAttended ? 'text-green-500' : 'text-cyan-500' }}">Live Attendance</span>
                        </div>

                        @if($alreadyAttended)
                            <h3 class="text-lg font-black text-white uppercase italic tracking-tighter">Attendance recorded &check;</h3>
                            <p class="text-xs text-zinc-400 mt-2 leading-relaxed">Thanks for showing up live — this one counts toward your completion guarantee.</p>
                            @if(!empty($playbookUrl))
                                <a href="{{ $playbookUrl }}" target="_blank"
                                   class="inline-flex items-center gap-2 mt-4 px-6 py-3 rounded-xl bg-white text-black hover:bg-cyan-500 transition-all text-[11px] font-black uppercase tracking-widest">
                                    Get the session playbook →
                                </a>
                            @endif

                        @elseif($hasAttendanceCode)
                            <h3 class="text-lg font-black text-white uppercase italic tracking-tighter">Mark your attendance</h3>
                            <p class="text-xs text-zinc-400 mt-2 leading-relaxed">
                                Enter the code AJ shares at the end of the live session{{ !empty($playbookUrl) ? ' to record it and unlock this session’s playbook' : ' to record it' }}.
                            </p>
                            <form wire:submit.prevent="markAttendance" class="mt-3 flex flex-col sm:flex-row gap-2 max-w-md">
                                <input type="text" wire:model="attendanceCode" placeholder="Session code"
                                       class="flex-1 bg-zinc-900 border border-zinc-800 text-white px-3 py-2.5 rounded-lg text-sm focus:border-cyan-500 focus:ring-0 placeholder:text-zinc-600 uppercase tracking-widest">
                                <button type="submit"
                                        class="shrink-0 px-6 py-2.5 rounded-lg bg-white text-black hover:bg-cyan-500 transition-all text-[11px] font-black uppercase tracking-widest">
                                    Mark attendance
                                </button>
                            </form>
                            @error('attendanceCode') <p class="text-[11px] text-amber-400 mt-2">{{ $message }}</p> @enderror

                        @else
                            <h3 class="text-lg font-black text-white uppercase italic tracking-tighter">Attendance closed</h3>
                            <p class="text-xs text-zinc-400 mt-2 leading-relaxed">Attendance for this session isn’t open. Catch the next live session to earn your attendance toward the guarantee.</p>
                        @endif
                    </div>
                @endif
                @endif
            </div>
        </div>
    </main>
</div>
