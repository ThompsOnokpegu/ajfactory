<?php

use function Livewire\Volt\{state, layout, mount};
use App\Models\Enrollment;

layout('components.layouts.dashboard');

state([
    'activeModule' => 0,
    'activeLesson' => 0,
    'currentVideoId' => '', 
    'curriculum' => [],
]);

mount(function () {
    // 1. Enrollment Check (Production Safety)
    /*
    $enrollment = Enrollment::where('email', auth()->user()->email)->first();
    if (!$enrollment || $enrollment->status !== 'paid') {
        return redirect()->route('checkout');
    }
    */

    // 2. Curriculum Data
    $this->curriculum = [
        [
            'title' => 'Module 01: The Foundation',
            'lessons' => [
                ['id' => '01-01', 'title' => 'Local n8n Setup vs Cloud', 'video_id' => 'w0H1-b044KY', 'duration' => '12:45', 'has_blueprint' => false],
                ['id' => '01-02', 'title' => 'API Handshakes Explained', 'video_id' => 'w0H1-b044KY', 'duration' => '15:20', 'has_blueprint' => true],
            ]
        ],
        [
            'title' => 'Module 02: WhatsApp Revenue',
            'lessons' => [
                ['id' => '02-01', 'title' => 'Meta Cloud API Auth', 'video_id' => 'S8r0-fC_6iE', 'duration' => '22:10', 'has_blueprint' => true],
                ['id' => '02-02', 'title' => 'Inbound Webhook Logic', 'video_id' => 'S8r0-fC_6iE', 'duration' => '18:05', 'has_blueprint' => true],
            ]
        ],
        [
            'title' => 'Module 03: High-Ticket Voice',
            'lessons' => [
                ['id' => '03-01', 'title' => 'Vapi Core Architecture', 'video_id' => 'S8r0-fC_6iE', 'duration' => '25:30', 'has_blueprint' => true],
                ['id' => '03-02', 'title' => 'Deploying the Dental Agent', 'video_id' => 'S8r0-fC_6iE', 'duration' => '30:15', 'has_blueprint' => true],
            ]
        ]
    ];

    $this->currentVideoId = $this->curriculum[0]['lessons'][0]['video_id'];
});

$selectLesson = function ($modIndex, $lessIndex) {
    $this->activeModule = $modIndex;
    $this->activeLesson = $lessIndex;
    $this->currentVideoId = $this->curriculum[$modIndex]['lessons'][$lessIndex]['video_id'];
};

?>

<div class="flex h-screen w-full bg-zinc-950 overflow-hidden" x-data="{ mobileMenuOpen: false }">
    
    <!-- MOBILE SIDEBAR OVERLAY -->
    <div x-show="mobileMenuOpen" 
         x-transition:enter="transition-opacity ease-linear duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-40 bg-zinc-950/90 backdrop-blur-sm lg:hidden" 
         @click="mobileMenuOpen = false"></div>

    <!-- SIDEBAR (Desktop & Mobile Drawer) -->
    <aside 
        class="fixed inset-y-0 left-0 z-50 w-80 bg-zinc-900 border-r border-zinc-800 flex flex-col transition-transform duration-300 transform lg:translate-x-0 lg:static lg:inset-0"
        :class="mobileMenuOpen ? 'translate-x-0' : '-translate-x-full'"
    >
        <div class="h-16 flex items-center justify-between px-6 border-b border-zinc-800 bg-zinc-950/50">
            <div class="text-sm font-black tracking-tighter italic text-white uppercase">
                AUTO<span class="text-cyan-500">MATION</span>.FACTORY
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
                                class="w-full flex items-center gap-3 px-3 py-3 rounded-lg transition-all text-left group {{ ($activeModule === $mIndex && $activeLesson === $lIndex) ? 'bg-cyan-500/10 border border-cyan-500/20' : 'hover:bg-zinc-800/50' }}"
                            >
                                <div class="h-6 w-6 shrink-0 rounded border flex items-center justify-center text-[10px] font-mono {{ ($activeModule === $mIndex && $activeLesson === $lIndex) ? 'bg-cyan-500 border-cyan-400 text-black font-bold' : 'border-zinc-700 text-zinc-600 bg-zinc-800' }}">
                                    {{ $lIndex + 1 }}
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
        <!-- HEADER -->
        <header class="h-16 flex items-center justify-between px-6 lg:px-8 border-b border-zinc-800 bg-zinc-950/80 backdrop-blur sticky top-0 z-20">
            <div class="flex items-center gap-4">
                <button @click="mobileMenuOpen = true" class="lg:hidden p-2 -ml-2 text-zinc-400 hover:text-white transition">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                </button>
                <div class="hidden sm:block text-[10px] font-mono font-bold text-zinc-500 uppercase tracking-[0.2em] border border-zinc-800 px-2 py-1 rounded">
                    Terminal // Mod_{{ $activeModule + 1 }} // Lesson_{{ $activeLesson + 1 }}
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

        <!-- CONTENT STAGE -->
        <div class="flex-1 overflow-y-auto p-8 lg:p-12 custom-scrollbar">
            <div class="max-w-5xl mx-auto">
                
                <!-- 1. VIDEO PLAYER (Refined Parameters) -->
                <!-- 
                    rel=0: Related videos only from the same channel.
                    modestbranding=1: Hide the YouTube logo from the control bar.
                    iv_load_policy=3: Disables video annotations.
                -->
                <div class="aspect-video bg-black border border-zinc-800 rounded-xl overflow-hidden shadow-2xl relative group" wire:key="video-{{ $activeModule }}-{{ $activeLesson }}">
                    <iframe 
                        class="w-full h-full"
                        src="https://www.youtube.com/embed/{{ $currentVideoId }}?rel=0&modestbranding=1&autoplay=1&iv_load_policy=3&controls=1" 
                        title="YouTube video player" 
                        frameborder="0" 
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" 
                        allowfullscreen>
                    </iframe>
                </div>

                <!-- 2. LESSON INFO -->
                <div class="mt-8 grid lg:grid-cols-3 gap-10">
                    
                    <!-- Description -->
                    <div class="lg:col-span-2 space-y-6">
                        <div>
                            <div class="flex items-center gap-3 mb-3">
                                <span class="px-2 py-0.5 rounded bg-cyan-500/10 text-cyan-500 border border-cyan-500/20 text-[9px] font-black uppercase tracking-widest">
                                    Deployment Phase
                                </span>
                                <span class="text-[10px] font-mono text-zinc-600 uppercase">
                                    ID: {{ $curriculum[$activeModule]['lessons'][$activeLesson]['id'] }}
                                </span>
                            </div>
                            <h1 class="text-3xl lg:text-4xl font-black text-white uppercase italic tracking-tighter leading-none">
                                {{ $curriculum[$activeModule]['lessons'][$activeLesson]['title'] }}
                            </h1>
                        </div>

                        <div class="prose prose-invert prose-sm text-zinc-400 font-medium leading-relaxed">
                            <p>In this module, we execute the core logic for the automation. Ensure your API keys are set in your `.env` file before proceeding.</p>
                            <ul class="text-xs font-mono bg-zinc-900 p-4 rounded-lg border border-zinc-800 space-y-2 list-none pl-0">
                                <li>> Verify Node Version: v1.0.2</li>
                                <li>> Check Webhook Listener Status</li>
                                <li>> Confirm Paystack/Vapi Credentials</li>
                            </ul>
                        </div>
                    </div>

                    <!-- 3. THE VAULT (Downloads) -->
                    <div class="space-y-6">
                        @if($curriculum[$activeModule]['lessons'][$activeLesson]['has_blueprint'])
                            <div class="p-6 bg-zinc-900 border border-cyan-900/50 rounded-xl relative overflow-hidden group hover:border-cyan-500/30 transition-all">
                                <!-- Glow Effect -->
                                <div class="absolute top-0 right-0 w-24 h-24 bg-cyan-500/10 blur-[40px] pointer-events-none"></div>
                                
                                <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-cyan-500 mb-4 flex items-center gap-2 relative z-10">
                                    <span class="h-1.5 w-1.5 rounded-full bg-cyan-500 animate-pulse"></span>
                                    Snapshot Vault
                                </h4>
                                <p class="text-[10px] text-zinc-400 mb-6 leading-relaxed relative z-10">
                                    This lesson includes a production-ready n8n JSON file. Import it to skip the manual build process.
                                </p>
                                
                                <a href="{{ route('vault.download', $curriculum[$activeModule]['lessons'][$activeLesson]['id']) }}" 
                                   class="block w-full py-3 bg-white text-black text-center font-black uppercase text-[10px] tracking-widest rounded hover:bg-cyan-400 hover:shadow-[0_0_20px_rgba(6,182,212,0.4)] transition-all relative z-10">
                                    Download Blueprint .JSON
                                </a>
                            </div>
                        @else
                            <div class="p-6 bg-zinc-900/30 border border-zinc-800 rounded-xl flex items-center justify-center min-h-[140px]">
                                <div class="text-center opacity-50">
                                    <svg class="w-8 h-8 text-zinc-600 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                    <p class="text-[9px] font-mono text-zinc-500 uppercase">No Assets Required</p>
                                </div>
                            </div>
                        @endif

                        <!-- System Status Box -->
                        <div class="p-4 border border-zinc-800 rounded-xl bg-zinc-950/50">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-[9px] font-black uppercase text-zinc-500 tracking-widest">System Status</span>
                                <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span>
                            </div>
                            <div class="space-y-1 font-mono text-[9px] text-zinc-600">
                                <div class="flex justify-between"><span>API Gateway</span> <span>Online</span></div>
                                <div class="flex justify-between"><span>Vault Core</span> <span>Secure</span></div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </main>
</div>