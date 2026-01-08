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
    // 1. Enrollment Check (Uncomment for Production)
    /*
    $enrollment = Enrollment::where('email', auth()->user()->email)->first();
    if (!$enrollment || $enrollment->status !== 'paid') {
        return redirect()->route('checkout');
    }
    */

    // 2. Define Curriculum Data
    $this->curriculum = [
        [
            'title' => 'Module 01: The Foundation',
            'lessons' => [
                ['id' => '01-01', 'title' => 'Local n8n Setup vs Cloud', 'video_id' => 'w0H1-b044KY', 'duration' => '12:45', 'has_blueprint' => false],
                ['id' => '01-02', 'title' => 'API Handshakes Explained', 'video_id' => 'S8r0-fC_6iE', 'duration' => '15:20', 'has_blueprint' => true],
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

    // Initialize first video
    $this->currentVideoId = $this->curriculum[0]['lessons'][0]['video_id'];
});

// Action to switch lessons
$selectLesson = function ($modIndex, $lessIndex) {
    $this->activeModule = $modIndex;
    $this->activeLesson = $lessIndex;
    $this->currentVideoId = $this->curriculum[$modIndex]['lessons'][$lessIndex]['video_id'];
};

?>

<div class="flex h-full w-full bg-zinc-950">
    
    <!-- =======================
         LEFT SIDEBAR (Navigation)
         ======================= -->
    <aside class="w-80 h-full bg-zinc-900/50 border-r border-zinc-800 flex flex-col shrink-0">
        <!-- Logo -->
        <div class="h-16 flex items-center px-6 border-b border-zinc-800">
            <div class="text-sm font-black tracking-tighter italic text-white uppercase">
                AUTO<span class="text-cyan-500">MATION</span>.ACC
            </div>
        </div>

        <!-- Curriculum List -->
        <div class="flex-1 overflow-y-auto custom-scrollbar p-4 space-y-8">
            @foreach($curriculum as $mIndex => $module)
                <div class="space-y-3">
                    <h3 class="px-2 text-[10px] font-black uppercase tracking-[0.2em] text-zinc-500">
                        {{ $module['title'] }}
                    </h3>
                    <div class="space-y-1">
                        @foreach($module['lessons'] as $lIndex => $lesson)
                            <button 
                                wire:click="selectLesson({{ $mIndex }}, {{ $lIndex }})"
                                class="w-full flex items-center gap-3 px-3 py-3 rounded-lg transition-all text-left group {{ ($activeModule === $mIndex && $activeLesson === $lIndex) ? 'bg-cyan-500/10 border border-cyan-500/20 shadow-[0_0_15px_rgba(6,182,212,0.1)]' : 'border border-transparent hover:bg-zinc-800/50' }}"
                            >
                                <!-- Status Indicator -->
                                <div class="h-6 w-6 flex-shrink-0 rounded border flex items-center justify-center text-[10px] font-mono {{ ($activeModule === $mIndex && $activeLesson === $lIndex) ? 'bg-cyan-500 border-cyan-400 text-black font-bold' : 'border-zinc-700 text-zinc-600 bg-zinc-800' }}">
                                    {{ $lIndex + 1 }}
                                </div>
                                
                                <!-- Text -->
                                <div class="flex-1 min-w-0">
                                    <p class="text-[11px] font-bold uppercase tracking-tight truncate {{ ($activeModule === $mIndex && $activeLesson === $lIndex) ? 'text-white' : 'text-zinc-400 group-hover:text-zinc-200' }}">
                                        {{ $lesson['title'] }}
                                    </p>
                                    <p class="text-[9px] font-mono text-zinc-600 tracking-tighter">{{ $lesson['duration'] }}</p>
                                </div>

                                <!-- Play Icon (Active) -->
                                @if($activeModule === $mIndex && $activeLesson === $lIndex)
                                    <div class="w-2 h-2 rounded-full bg-cyan-500 animate-pulse shadow-[0_0_8px_#06b6d4]"></div>
                                @endif
                            </button>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        <!-- User Profile Footer -->
        <div class="p-4 border-t border-zinc-800 bg-zinc-900">
            <div class="flex items-center gap-3">
                <div class="h-8 w-8 rounded bg-cyan-600 flex items-center justify-center text-xs font-black text-white uppercase">
                    {{ substr(auth()->user()->name ?? 'A', 0, 1) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-[10px] font-bold text-white truncate uppercase tracking-wider">{{ auth()->user()->name ?? 'Builder' }}</p>
                    <div class="flex items-center gap-1.5 mt-0.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                        <p class="text-[8px] text-zinc-500 font-mono uppercase tracking-widest">Online</p>
                    </div>
                </div>
            </div>
        </div>
    </aside>

    <!-- =======================
         MAIN CONTENT (Player)
         ======================= -->
    <main class="flex-1 flex flex-col h-full overflow-hidden relative">
        
        <!-- Header -->
        <header class="h-16 flex items-center justify-between px-8 border-b border-zinc-800 bg-zinc-950/80 backdrop-blur sticky top-0 z-20">
            <div class="flex items-center gap-4">
                <div class="text-[10px] font-mono font-bold text-zinc-500 uppercase tracking-[0.2em] border border-zinc-800 px-2 py-1 rounded">
                    Terminal // Mod_{{ str_pad($activeModule + 1, 2, '0', STR_PAD_LEFT) }}
                </div>
            </div>
            <div class="flex items-center gap-6">
                <a href="https://discord.gg" target="_blank" class="text-[10px] font-black text-zinc-500 hover:text-cyan-500 transition uppercase tracking-widest flex items-center gap-2">
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M20.317 4.37a19.791 19.791 0 0 0-4.885-1.515.074.074 0 0 0-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 0 0-5.487 0 12.64 12.64 0 0 0-.617-1.25.077.077 0 0 0-.079-.037A19.736 19.736 0 0 0 3.677 4.37a.07.07 0 0 0-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 0 0 .031.057 19.9 19.9 0 0 0 5.993 3.03.078.078 0 0 0 .084-.028 14.09 14.09 0 0 0 1.226-1.994.076.076 0 0 0-.041-.106 13.107 13.107 0 0 1-1.872-.892.077.077 0 0 1-.008-.128 10.2 10.2 0 0 0 .372-.292.074.074 0 0 1 .077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 0 1 .078.01c.12.098.246.198.373.292a.077.077 0 0 1-.006.127 12.299 12.299 0 0 1-1.873.892.077.077 0 0 0-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 0 0 .084.028 19.839 19.839 0 0 0 6.002-3.03.077.077 0 0 0 .032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 0 0-.031-.03z"/></svg>
                    Discord Uplink
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-[10px] font-black text-zinc-700 hover:text-red-500 transition uppercase tracking-widest">
                        Exit Terminal
                    </button>
                </form>
            </div>
        </header>

        <!-- Video & Content Stage -->
        <div class="flex-1 overflow-y-auto p-8 lg:p-12 custom-scrollbar">
            <div class="max-w-5xl mx-auto">
                
                <!-- 1. VIDEO PLAYER -->
                <!-- wire:key ensures iframe refreshes when ID changes -->
                <div class="aspect-video bg-black border border-zinc-800 rounded-xl overflow-hidden shadow-2xl relative group" wire:key="video-{{ $activeModule }}-{{ $activeLesson }}">
                    <iframe 
                        class="w-full h-full"
                        src="https://www.youtube.com/embed/{{ $currentVideoId }}?rel=0&modestbranding=1&autoplay=1&iv_load_policy=3" 
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