<?php

use function Livewire\Volt\{state, layout, mount};
use App\Models\Enrollment;
use Carbon\Carbon;

layout('components.layouts.dashboard');

state([
    'activeSection' => 0,
    'activeModule' => 0,
    'activeVideo' => 0,
    'currentVideoId' => '',
    'curriculum' => [],
    'completedLessons' => [],
    'isLocked' => false,
    'unlockDate' => '',
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
    return $module;
};

// A module unlocks (all its videos) at its release_at.
$moduleReleaseAt = function ($module) {
    return isset($module['release_at'])
        ? Carbon::parse($module['release_at'], 'Africa/Lagos')
        : Carbon::now('Africa/Lagos')->subDay();
};

mount(function () use ($updateVideoSource, $normalizeModule, $moduleReleaseAt) {
    // 1. Progress
    $enrollment = Enrollment::where('email', auth()->user()->email)->first();
    $this->completedLessons = is_array($enrollment?->completed_lessons)
        ? $enrollment->completed_lessons
        : [];

    // 2. Load + normalize curriculum into sections of modules
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

    // 3. Initialize the first video of the first module
    if (!empty($this->curriculum[0]['modules'][0]['videos'][0])) {
        $firstModule = $this->curriculum[0]['modules'][0];
        $firstVideo = $firstModule['videos'][0];

        $now = Carbon::now('Africa/Lagos');
        $releaseAt = $moduleReleaseAt($firstModule);

        if ($releaseAt->gt($now)) {
            $this->isLocked = true;
            $this->unlockDate = $releaseAt->format('M d, Y @ h:i A');
            $this->currentVideoId = '';
        } else {
            $this->isLocked = false;
            $this->currentVideoId = $updateVideoSource($firstVideo, $firstModule);
        }
    }
});

$selectVideo = function ($sIndex, $mIndex, $vIndex) use ($updateVideoSource, $moduleReleaseAt) {
    $module = $this->curriculum[$sIndex]['modules'][$mIndex] ?? null;
    $video = $module['videos'][$vIndex] ?? null;

    if (!$module || !$video) {
        return;
    }

    $this->activeSection = $sIndex;
    $this->activeModule = $mIndex;
    $this->activeVideo = $vIndex;

    $now = Carbon::now('Africa/Lagos');
    $releaseAt = $moduleReleaseAt($module);

    if ($releaseAt->gt($now)) {
        $this->isLocked = true;
        $this->unlockDate = $releaseAt->format('M d, Y @ h:i A');
        $this->currentVideoId = '';
    } else {
        $this->isLocked = false;
        $this->currentVideoId = $updateVideoSource($video, $module);
    }
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
    @endphp

    <!-- SIDEBAR -->
    <aside
        class="fixed inset-y-0 left-0 z-50 w-80 bg-zinc-900 border-r border-zinc-800 flex flex-col transition-transform duration-300 transform lg:translate-x-0 lg:static lg:inset-0"
        :class="mobileMenuOpen ? 'translate-x-0' : '-translate-x-full'"
    >
        <div class="h-16 flex items-center justify-between px-6 border-b border-zinc-800 bg-zinc-950/50">
            <div class="text-sm font-black tracking-tighter italic text-white uppercase">
                AUTO<span class="text-cyan-500">MATION</span>.ACC
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
                                $releaseTime = isset($module['release_at']) ? \Carbon\Carbon::parse($module['release_at'], 'Africa/Lagos') : \Carbon\Carbon::now()->subDay();
                                $moduleLocked = $releaseTime->isFuture();
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

        <div class="p-4 border-t border-zinc-800 bg-zinc-950/50 mt-auto">
            <div class="flex items-center gap-3">
                <div class="h-8 w-8 rounded bg-cyan-600 flex items-center justify-center text-xs font-black text-white uppercase">
                    {{ substr(auth()->user()->name ?? 'U', 0, 1) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-[10px] font-bold text-white truncate uppercase tracking-wider">{{ auth()->user()->name ?? 'Builder' }}</p>
                    <p class="text-[8px] text-zinc-500 font-mono uppercase tracking-widest leading-none mt-1">Status: Online</p>
                </div>
            </div>
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

            <div class="flex items-center gap-4 lg:gap-6">
                <a href="https://t.me/yourlink" target="_blank" class="hidden md:flex text-[10px] font-black text-zinc-500 hover:text-cyan-500 transition uppercase tracking-widest items-center gap-2">
                    Telegram Community
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-[10px] font-black text-zinc-700 hover:text-red-500 transition uppercase tracking-widest">
                        Exit
                    </button>
                </form>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-4 lg:p-12 custom-scrollbar">
            <div class="max-w-5xl mx-auto space-y-8">

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
                            <h3 class="text-xl font-black text-white uppercase italic tracking-tighter">Classified Module</h3>
                            <p class="text-xs text-zinc-400 font-mono mt-2 uppercase tracking-widest">
                                Unlocks: {{ $unlockDate }}
                            </p>
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
                            <span class="text-[9px] font-mono text-zinc-100 mt-1 uppercase tracking-widest text-center">This video is being prepared. <br>Every Monday a new deployment module unlocks. <br>
                                Every Thursday we meet live for technical build sessions and real-time Q&A.</span>
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
                    </div>
                </div>
                @endif
            </div>
        </div>
    </main>
</div>
