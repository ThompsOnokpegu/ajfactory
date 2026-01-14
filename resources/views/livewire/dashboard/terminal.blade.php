<?php

use function Livewire\Volt\{state, layout, mount};
use App\Models\Enrollment;

layout('components.layouts.dashboard');

state([
    'activeModule' => 0,
    'activeLesson' => 0,
    'currentVideoId' => '', 
    'curriculum' => [],
    'completedLessons' => [],
]);

mount(function () {
    // 1. Enrollment Check
    $enrollment = Enrollment::where('email', auth()->user()->email)->first();
    $this->completedLessons = is_array($enrollment?->completed_lessons) 
        ? $enrollment->completed_lessons 
        : [];

    // 2. Load and Structure Curriculum
    // We wrap the flat config list into a single "Main Track" to fit the UI structure
    $rawConfig = config('curriculum') ?? [];
    
    $this->curriculum = [
        [
            'title' => 'AI Automation Accelerator',
            'lessons' => $rawConfig
        ]
    ];

    // 3. Initialize First Video
    if (!empty($this->curriculum) && !empty($this->curriculum[0]['lessons'])) {
        $this->updateVideoSource($this->curriculum[0]['lessons'][0]);
    }
});

$selectLesson = function ($modIndex, $lessIndex) {
    $this->activeModule = $modIndex;
    $this->activeLesson = $lessIndex;
    $lesson = $this->curriculum[$modIndex]['lessons'][$lessIndex];
    $this->updateVideoSource($lesson);
};

// EXCLUSIVE BUNNY.NET LOGIC
$updateVideoSource = function ($lesson) {
    $videoId = trim($lesson['video_id']); 
    $libraryId = config('services.bunny.library_id'); 
    
    if (!$libraryId || !$videoId) {
        $this->currentVideoId = ""; 
        return;
    }

    $this->currentVideoId = "https://iframe.mediadelivery.net/embed/{$libraryId}/{$videoId}?autoplay=true&loop=false&muted=false&preload=true&responsive=true&context=true";
};

$toggleComplete = function ($lessonId) {
    $enrollment = Enrollment::where('email', auth()->user()->email)->first();
    if (!$enrollment) return;

    $completed = collect($this->completedLessons);

    if ($completed->contains($lessonId)) {
        $completed = $completed->reject(fn($id) => $id === $lessonId);
    } else {
        $completed->push($lessonId);
    }

    $this->completedLessons = $completed->values()->all();
    
    $enrollment->update([
        'completed_lessons' => $this->completedLessons
    ]);
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
                            <button 
                                wire:click="selectLesson({{ $mIndex }}, {{ $lIndex }})"
                                @click="mobileMenuOpen = false"
                                class="w-full flex items-center gap-3 px-3 py-3 rounded-lg transition-all text-left group {{ ($activeModule === $mIndex && $activeLesson === $lIndex) ? 'bg-cyan-500/10 border border-cyan-500/20 shadow-[0_0_15px_rgba(6,182,212,0.1)]' : 'border border-transparent hover:bg-zinc-800/50' }}"
                            >
                                <div class="h-6 w-6 shrink-0 rounded border flex items-center justify-center text-[10px] font-mono 
                                    {{ in_array($lesson['id'], $completedLessons) ? 'bg-green-500/20 border-green-500/50 text-green-500' : 
                                       (($activeModule === $mIndex && $activeLesson === $lIndex) ? 'bg-cyan-500 border-cyan-400 text-black font-bold' : 'border-zinc-700 text-zinc-600 bg-zinc-800') }}">
                                    @if(in_array($lesson['id'], $completedLessons))
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                    @else
                                        {{ $lIndex + 1 }}
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-[11px] font-bold uppercase tracking-tight truncate {{ ($activeModule === $mIndex && $activeLesson === $lIndex) ? 'text-white' : 'text-zinc-400 group-hover:text-zinc-200' }}">
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

    <!-- MAIN VIEWPORT -->
    <main class="flex-1 flex flex-col min-w-0 h-full overflow-hidden">
        <header class="h-16 flex items-center justify-between px-6 lg:px-8 border-b border-zinc-800 bg-zinc-950/80 backdrop-blur sticky top-0 z-20">
            <div class="flex items-center gap-4">
                <button @click="mobileMenuOpen = true" class="lg:hidden p-2 -ml-2 text-zinc-400 hover:text-white transition">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                </button>
                <div class="hidden sm:block text-[10px] font-mono font-bold text-zinc-500 uppercase tracking-[0.2em] border border-zinc-800 px-2 py-1 rounded">
                    Terminal // Mod_{{ $activeModule + 1 }}
                </div>
                <div class="sm:hidden text-[10px] font-mono font-bold text-cyan-500 uppercase tracking-widest">
                    M{{ $activeModule + 1 }}.L{{ $activeLesson + 1 }}
                </div>
            </div>
            
            <div class="flex items-center gap-4 lg:gap-6">
                <a href="#" target="_blank" class="hidden md:flex text-[10px] font-black text-zinc-500 hover:text-cyan-500 transition uppercase tracking-widest items-center gap-2">
                    Private Community
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
                
                <!-- BUNNY.NET PLAYER -->
                <div class="relative w-full bg-black border border-cyan-500 rounded-xl overflow-hidden shadow-2xl"
                     style="padding-top: 56.25%;" 
                     wire:key="bunny-player-{{ $activeModule }}-{{ $activeLesson }}">
                    
                    @if($currentVideoId)
                        <iframe 
                            src="{{ $currentVideoId }}" 
                            loading="lazy" 
                            class="absolute top-0 left-0 w-full h-full border-none"
                            allow="accelerometer; gyroscope; autoplay; encrypted-media; picture-in-picture; clipboard-write;" 
                            allowfullscreen="true">
                        </iframe>
                    @else
                        <div class="absolute inset-0 flex flex-col items-center justify-center text-zinc-600">
                            <span class="text-[10px] font-mono uppercase tracking-widest">Stream Offline</span>
                            <span class="text-[9px] font-mono text-zinc-700 mt-1">Check Library ID Config</span>
                        </div>
                    @endif
                </div>

                <!-- INFO GRID -->
                <div class="grid lg:grid-cols-3 gap-10">
                    <div class="lg:col-span-2 space-y-6">
                        <h1 class="text-3xl lg:text-5xl font-black text-white uppercase italic tracking-tighter leading-[0.9]">
                            {{ $curriculum[$activeModule]['lessons'][$activeLesson]['title'] }}
                        </h1>
                        <!-- Dynamic Lesson Description -->
                        <div class="prose prose-invert prose-sm text-zinc-400 font-medium leading-relaxed max-w-2xl">
                            {{ $curriculum[$activeModule]['lessons'][$activeLesson]['description'] ?? 'No description available.' }}
                        </div>
                        <div class="pt-4">
                            <button 
                                wire:click="toggleComplete('{{ $curriculum[$activeModule]['lessons'][$activeLesson]['id'] }}')"
                                class="inline-flex items-center gap-3 px-6 py-3 rounded-xl border transition-all text-[11px] font-black uppercase tracking-widest 
                                {{ in_array($curriculum[$activeModule]['lessons'][$activeLesson]['id'], $completedLessons) 
                                    ? 'bg-zinc-900 border-green-500/50 text-green-500 hover:bg-green-500/10' 
                                    : 'bg-white text-black hover:bg-cyan-500' }}"
                            >
                                @if(in_array($curriculum[$activeModule]['lessons'][$activeLesson]['id'], $completedLessons))
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                    Mark as Incomplete
                                @else
                                    Complete Lesson
                                @endif
                            </button>
                        </div>
                    </div>

                    <!-- THE VAULT & PROGRESS -->
                    <div class="space-y-6">
                        @if($curriculum[$activeModule]['lessons'][$activeLesson]['has_blueprint'])
                            <div class="p-6 bg-zinc-900 border border-cyan-900/50 rounded-2xl relative overflow-hidden group shadow-lg">
                                <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-cyan-500 mb-3 flex items-center gap-2">
                                    <span class="h-1.5 w-1.5 rounded-full bg-cyan-500 animate-pulse"></span>
                                    Snapshot Vault
                                </h4>
                                <p class="text-[10px] text-zinc-400 mb-6 leading-relaxed">
                                    Download the production-ready n8n JSON file for this module.
                                </p>
                                <a href="{{ route('vault.download', $curriculum[$activeModule]['lessons'][$activeLesson]['id']) }}" 
                                   class="block w-full py-4 bg-white text-black text-center font-black uppercase text-[10px] tracking-widest rounded-lg hover:bg-cyan-500 transition-all">
                                    <span wire:loading.remove wire:target="vault.download">Download JSON</span>
                                    <span wire:loading wire:target="vault.download">Syncing...</span>
                                </a>
                            </div>
                        @endif
                        
                        <div class="p-6 border border-zinc-900 rounded-2xl bg-zinc-950/50">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-[9px] font-black uppercase text-zinc-500 tracking-widest">Foundry Progress</span>
                                <span class="text-[10px] font-mono text-cyan-500">{{ count($completedLessons) }} / 8</span>
                            </div>
                            <div class="h-1.5 w-full bg-zinc-900 rounded-full overflow-hidden">
                                <div class="h-full bg-cyan-500 transition-all duration-700 shadow-[0_0_10px_#06b6d4]" style="width: {{ (count($completedLessons) / 6) * 100 }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>