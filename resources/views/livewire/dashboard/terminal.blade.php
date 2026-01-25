<?php

use function Livewire\Volt\{state, layout, mount};
use App\Models\Enrollment;
use Carbon\Carbon;

layout('components.layouts.dashboard');

state([
    'activeModule' => 0,
    'activeLesson' => 0,
    'currentVideoId' => '', 
    'curriculum' => [],
    'completedLessons' => [],
    'isLocked' => false,
    'unlockDate' => '',
]);

mount(function () {
    // 1. Fetch Enrollment & Progress
    $enrollment = Enrollment::where('email', auth()->user()->email)->first();
    $this->completedLessons = is_array($enrollment?->completed_lessons) 
        ? $enrollment->completed_lessons 
        : [];

    // 2. Load and Structure Curriculum
    // We maintain the nested structure so the existing Blade markup loops continue to function.
    $rawConfig = config('curriculum') ?? [];
    
    $this->curriculum = [
        [
            'title' => 'Course Roadmap',
            'lessons' => $rawConfig
        ]
    ];

    // 3. Initialize First Video / Module
    if (!empty($this->curriculum) && !empty($this->curriculum[0]['lessons'])) {
        $this->selectLesson(0, 0);
    }
});

$selectLesson = function ($modIndex, $lessIndex) {
    $this->activeModule = $modIndex;
    $this->activeLesson = $lessIndex;
    
    // In your structure, the 'lesson' is actually the module item from the flat config.
    $module = $this->curriculum[$modIndex];
    $lesson = $module['lessons'][$lessIndex];
    
    // NEW LOGIC: Use Africa/Lagos timezone to ensure 12:00 AM WAT release works accurately
    $now = Carbon::now('Africa/Lagos');
    $releaseAt = isset($lesson['release_at']) 
        ? Carbon::parse($lesson['release_at'], 'Africa/Lagos') 
        : $now->subDay(); // If no date, assume released

    if ($releaseAt->gt($now)) {
        $this->isLocked = true;
        $this->unlockDate = $releaseAt->format('M d, Y @ h:i A');
        $this->currentVideoId = ''; 
    } else {
        $this->isLocked = false;
        $this->updateVideoSource($lesson);
    }
};

$updateVideoSource = function ($lesson) {
    $videoId = trim($lesson['video_id'] ?? '');
    $libraryId = config('services.bunny.library_id'); 
    
    if (!$libraryId || !$videoId || $videoId === 'welcome_video_id' || str_contains($videoId, 'bunny_video_id')) {
        $this->currentVideoId = ""; 
        return;
    }

    $this->currentVideoId = "https://iframe.mediadelivery.net/embed/{$libraryId}/{$videoId}?autoplay=true&loop=false&muted=false&preload=true&responsive=true&context=true";
};

$toggleComplete = function ($lessonId) {
    // Logic check: only allow completion of unlocked content
    if ($this->isLocked) return;

    $enrollment = Enrollment::where('email', auth()->user()->email)->first();
    if (!$enrollment) return;

    $completed = collect($this->completedLessons);

    if ($completed->contains($lessonId)) {
        $completed = $completed->reject(fn($id) => $id === $lessonId);
    } else {
        $completed->push($lessonId);
    }

    $this->completedLessons = $completed->values()->all();
    $enrollment->update(['completed_lessons' => $this->completedLessons]);
};

?>

<div class="flex h-screen w-full bg-zinc-950 overflow-hidden" x-data="{ mobileMenuOpen: false }">
    
    <!-- MOBILE OVERLAY -->
    <div x-show="mobileMenuOpen" class="fixed inset-0 z-40 bg-zinc-950/90 backdrop-blur-sm lg:hidden" @click="mobileMenuOpen = false" x-cloak></div>

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
            @foreach($curriculum as $mIndex => $module)
                <div class="space-y-3">
                    <h3 class="px-2 text-[10px] font-black uppercase tracking-[0.2em] text-zinc-500 italic">
                        {{ $module['title'] }}
                    </h3>
                    <div class="space-y-1">
                        @foreach($module['lessons'] as $lIndex => $lesson)
                            @php
                                // Logic: Check individual item release time against current time in Nigeria
                                $lessonLocked = isset($lesson['release_at']) && \Carbon\Carbon::parse($lesson['release_at'], 'Africa/Lagos')->isFuture();
                            @endphp
                            <button 
                                wire:click="selectLesson({{ $mIndex }}, {{ $lIndex }})"
                                @click="mobileMenuOpen = false"
                                class="w-full flex items-center gap-3 px-3 py-3 rounded-lg transition-all text-left group {{ ($activeModule === $mIndex && $activeLesson === $lIndex) ? 'bg-cyan-500/10 border border-cyan-500/20 shadow-[0_0_15px_rgba(6,182,212,0.1)]' : 'border border-transparent hover:bg-zinc-800/50' }}"
                            >
                                <div class="h-6 w-6 shrink-0 rounded border flex items-center justify-center text-[10px] font-mono 
                                    {{ in_array($lesson['id'], $completedLessons) ? 'bg-green-500/20 border-green-500/50 text-green-500' : 
                                       ($lessonLocked ? 'border-zinc-800 text-zinc-700 bg-zinc-900/50' : 
                                       (($activeModule === $mIndex && $activeLesson === $lIndex) ? 'bg-cyan-500 border-cyan-400 text-black font-bold' : 'border-zinc-700 text-zinc-600 bg-zinc-800')) }}">
                                    @if(in_array($lesson['id'], $completedLessons))
                                         <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                    @elseif($lessonLocked)
                                         <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                                    @else
                                        {{ $lIndex + 1 }}
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-[11px] font-bold uppercase tracking-tight truncate {{ ($activeModule === $mIndex && $activeLesson === $lIndex) ? 'text-white' : ($lessonLocked ? 'text-zinc-600' : 'text-zinc-400 group-hover:text-zinc-200') }}">
                                        {{ $lesson['title'] }}
                                    </p>
                                    <p class="text-[9px] font-mono text-zinc-600 tracking-tighter">{{ $lesson['duration'] }}</p>
                                </div>
                            </button>
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
                    Terminal // Batch_001
                </div>
                <div class="sm:hidden text-[10px] font-mono font-bold text-cyan-500 uppercase tracking-widest">
                    M{{ $activeLesson + 1 }}
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
                     wire:key="player-area-{{ $activeModule }}-{{ $activeLesson }}">
                    
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
                            <span class="text-[9px] font-mono text-zinc-100 mt-1 uppercase tracking-widest text-center">The core mission begins on Monday, January 26th. <br>Our protocol is simple: Every Monday, a new deployment module unlocks. <br>
                                Every Thursday, we meet live for technical build sessions and real-time Q&A.</span>
                        </div>
                    @endif
                </div>

                <!-- INFO GRID -->
                <div class="grid lg:grid-cols-3 gap-10">
                    <div class="lg:col-span-2 space-y-6">
                        <div class="flex items-center gap-3">
                            @if(in_array($curriculum[$activeModule]['lessons'][$activeLesson]['id'], $completedLessons))
                                <span class="px-2 py-0.5 rounded bg-green-500/10 text-green-500 border border-green-500/20 text-[9px] font-black uppercase tracking-widest flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                    Module Verified
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded {{ $isLocked ? 'bg-zinc-800 text-zinc-500' : 'bg-cyan-500/10 text-cyan-500 border border-cyan-500/20' }} text-[9px] font-black uppercase tracking-widest">
                                    Status: {{ $isLocked ? 'Encrypted' : 'Sync_In_Progress' }}
                                </span>
                            @endif
                            <span class="text-[10px] font-mono text-zinc-600 uppercase">
                                ID: {{ $curriculum[$activeModule]['lessons'][$activeLesson]['id'] }}
                            </span>
                        </div>

                        <h1 class="text-3xl lg:text-5xl font-black text-white uppercase italic tracking-tighter leading-[0.9]">
                            {{ $curriculum[$activeModule]['lessons'][$activeLesson]['title'] }}
                        </h1>
                        <div class="prose prose-invert prose-sm text-zinc-400 font-medium leading-relaxed max-w-2xl">
                            {{ $curriculum[$activeModule]['lessons'][$activeLesson]['description'] }}
                        </div>
                        
                        <!-- Progress Button -->
                        <div class="pt-4">
                            @if(!$isLocked)
                                <button 
                                    wire:click="toggleComplete('{{ $curriculum[$activeModule]['lessons'][$activeLesson]['id'] }}')"
                                    class="inline-flex items-center gap-3 px-6 py-3 rounded-xl border transition-all text-[11px] font-black uppercase tracking-widest 
                                    {{ in_array($curriculum[$activeModule]['lessons'][$activeLesson]['id'], $completedLessons) 
                                        ? 'bg-zinc-900 border-green-500/50 text-green-500' 
                                        : 'bg-white text-black hover:bg-cyan-500' }}"
                                >
                                    @if(in_array($curriculum[$activeModule]['lessons'][$activeLesson]['id'], $completedLessons))
                                        Mark as Incomplete
                                    @else
                                        Complete Module
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
                        @if(!$isLocked && $curriculum[$activeModule]['lessons'][$activeLesson]['has_blueprint'])
                            <div class="p-6 bg-zinc-900 border border-cyan-900/50 rounded-2xl relative overflow-hidden group shadow-lg">
                                <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-cyan-500 mb-3 flex items-center gap-2 relative z-10">
                                    <span class="h-1.5 w-1.5 rounded-full bg-cyan-500 animate-pulse"></span>
                                    Snapshot Vault
                                </h4>
                                <p class="text-[10px] text-zinc-400 mb-6 leading-relaxed">
                                    Access the technical blueprints for this module.
                                </p>
                                <a href="https://drive.google.com/drive/folders/1-LCglWb4khbJEoN3Kqwdi-XVSWfYtR6v?usp=drive_link" target="_blank"
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
                            @php
                                $totalItems = count($curriculum[0]['lessons']);
                                $completedCount = count($completedLessons);
                                $percent = ($totalItems > 0) ? ($completedCount / $totalItems) * 100 : 0;
                            @endphp
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
            </div>
        </div>
    </main>
</div>