<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\Enrollment;
use App\Models\Checkpoint;
use App\Models\Student;
use App\Models\MasterclassRegistration;
use App\Support\Accelerator;

new #[Layout('components.layouts.admin', ['title' => 'Overview'])] class extends Component {
    public function mount(): void
    {
        abort_unless(auth()->user()?->is_admin, 403);
    }

    /** Pause / resume cohort registration (gates the checkout immediately). */
    public function toggleRegistration(): void
    {
        abort_unless(auth()->user()?->is_admin, 403);
        Accelerator::setRegistrationOpen(! Accelerator::registrationOpen());
    }

    public function with(): array
    {
        $collected = Enrollment::where('status', 'paid')
            ->selectRaw('currency, SUM(COALESCE(amount_total, amount) - COALESCE(balance_due, 0)) as total')
            ->groupBy('currency')
            ->pluck('total', 'currency');

        // Only students whose first payment actually verified (status=paid) truly owe
        // a balance. Without this, abandoned checkout rows — pre-created with a balance
        // but never paid — inflate the outstanding total (Collected + installments:process
        // already scope to status=paid; this stat was the odd one out).
        $outstanding = Enrollment::where('status', 'paid')
            ->where('plan_type', 'installment')
            ->where('balance_due', '>', 0);
        $cap = (int) config('accelerator.cohort_cap');
        $sold = Accelerator::seatsSold();

        return [
            'collectedNgn' => (float) ($collected['NGN'] ?? 0),
            'collectedUsd' => (float) ($collected['USD'] ?? 0),
            'paidCount' => Enrollment::where('status', 'paid')->count(),
            'seatsSold' => $sold,
            'seatsLeft' => Accelerator::seatsLeft(),
            'seatsPct' => $cap > 0 ? min(100, round($sold / $cap * 100)) : 0,
            'cap' => $cap,
            'earlybird' => Accelerator::earlybirdActive(),
            'soldOut' => Accelerator::isSoldOut(),
            'registrationOpen' => Accelerator::registrationOpen(),
            'outstandingTotal' => (float) (clone $outstanding)->sum('balance_due'),
            'outstandingCount' => (clone $outstanding)->count(),
            'suspended' => Enrollment::where('access_suspended', true)->count(),
            'pendingCheckpoints' => Checkpoint::where('status', 'submitted')->count(),
            'mcRegs' => MasterclassRegistration::where('session_date', config('taab.masterclass.date'))->count(),
            'totalLeads' => Student::count(),
            'recentEnrollments' => Enrollment::where('status', 'paid')->latest('paid_at')->limit(6)->get(),
            'recentLeads' => Student::latest('created_at')->limit(6)->get(),
        ];
    }
}; ?>

<div class="max-w-6xl mx-auto space-y-10">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-black tracking-tighter text-white">Welcome back, {{ \Illuminate\Support\Str::of(auth()->user()->name)->before(' ') }}.</h2>
            <p class="text-sm text-zinc-500 mt-1">Here's how the cohort and funnel are doing right now.</p>
        </div>

        <!-- Registration ON/OFF switch -->
        <div class="flex items-center gap-3 rounded-2xl border border-zinc-800 bg-zinc-900/40 px-4 py-3 shrink-0">
            <div class="text-right">
                <div class="text-[10px] font-black uppercase tracking-[0.15em] text-zinc-500">Cohort registration</div>
                <div class="text-sm font-black {{ $registrationOpen ? 'text-green-400' : 'text-amber-400' }}">{{ $registrationOpen ? 'Open' : 'Paused' }}</div>
            </div>
            <button type="button" wire:click="toggleRegistration"
                    wire:confirm="{{ $registrationOpen ? 'Pause cohort registration? The checkout will stop accepting new sign-ups.' : 'Resume cohort registration? The checkout will accept sign-ups again.' }}"
                    role="switch" aria-checked="{{ $registrationOpen ? 'true' : 'false' }}"
                    class="relative inline-flex h-7 w-12 items-center rounded-full transition-colors {{ $registrationOpen ? 'bg-green-500/80' : 'bg-zinc-700' }}">
                <span class="inline-block h-5 w-5 transform rounded-full bg-white transition-transform {{ $registrationOpen ? 'translate-x-6' : 'translate-x-1' }}"></span>
            </button>
        </div>
    </div>

    <!-- KPIs -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <x-admin.stat label="Collected" value="₦{{ number_format($collectedNgn) }}" accent="cyan"
            sub="{{ $collectedUsd > 0 ? '+ $'.number_format($collectedUsd).' USD · ' : '' }}paid in full + installments">
            <x-slot:icon><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 9v1m0-9c-1.11 0-2.08.402-2.599 1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></x-slot:icon>
        </x-admin.stat>

        <x-admin.stat label="Paid enrollments" value="{{ number_format($paidCount) }}" accent="white" sub="Active students in the cohort">
            <x-slot:icon><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4zm6 0a3 3 0 10-2.5-4.5"/></svg></x-slot:icon>
        </x-admin.stat>

        <x-admin.stat label="Seats" value="{{ $soldOut ? 'Sold out' : $seatsLeft.' left' }}" accent="{{ $earlybird ? 'cyan' : 'white' }}">
            <x-slot:icon><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg></x-slot:icon>
            <x-slot:footer>
                <div class="flex items-center justify-between text-[10px] font-mono text-zinc-500 mb-1.5">
                    <span>{{ $seatsSold }} / {{ $cap }} filled</span>
                    <span>{{ $earlybird ? 'Early-bird' : 'Regular' }}</span>
                </div>
                <div class="h-1.5 w-full bg-zinc-800 rounded-full overflow-hidden">
                    <div class="h-full bg-cyan-500 rounded-full" style="width: {{ $seatsPct }}%"></div>
                </div>
            </x-slot:footer>
        </x-admin.stat>

        <x-admin.stat label="Outstanding" value="₦{{ number_format($outstandingTotal) }}" accent="{{ $outstandingTotal > 0 ? 'amber' : 'white' }}"
            sub="{{ $outstandingCount }} {{ \Illuminate\Support\Str::plural('student', $outstandingCount) }} with an installment balance">
            <x-slot:icon><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></x-slot:icon>
        </x-admin.stat>
    </div>

    <!-- Needs attention -->
    <div>
        <div class="text-[10px] font-black uppercase tracking-[0.2em] text-zinc-600 mb-3">Needs attention</div>
        <div class="grid sm:grid-cols-3 gap-4">
            <x-admin.stat label="Checkpoints to review" value="{{ $pendingCheckpoints }}" accent="{{ $pendingCheckpoints > 0 ? 'cyan' : 'white' }}" :href="route('admin.checkpoints')">
                <x-slot:icon><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg></x-slot:icon>
            </x-admin.stat>
            <x-admin.stat label="Suspended (overdue)" value="{{ $suspended }}" accent="{{ $suspended > 0 ? 'red' : 'white' }}" :href="route('admin.enrollments', ['suspended' => 1])">
                <x-slot:icon><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg></x-slot:icon>
            </x-admin.stat>
            <x-admin.stat label="Next masterclass" value="{{ $mcRegs }}" accent="white" sub="{{ config('taab.masterclass.date') ?: 'date TBA' }}" :href="route('admin.masterclass')">
                <x-slot:icon><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg></x-slot:icon>
            </x-admin.stat>
        </div>
    </div>

    <!-- Recent activity -->
    <div class="grid lg:grid-cols-2 gap-4">
        <div class="rounded-2xl border border-zinc-800 bg-zinc-900/40">
            <div class="flex items-center justify-between px-5 py-4 border-b border-zinc-800">
                <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-zinc-400">Recent enrollments</h3>
                <a href="{{ route('admin.enrollments') }}" class="text-[10px] font-black uppercase tracking-widest text-cyan-500 hover:text-cyan-400">All →</a>
            </div>
            <div class="divide-y divide-zinc-900">
                @forelse($recentEnrollments as $e)
                    @php $sym = ($e->currency ?: 'NGN') === 'NGN' ? '₦' : '$'; @endphp
                    <div class="flex items-center gap-3 px-5 py-3">
                        <x-admin.avatar :name="$e->full_name" />
                        <div class="min-w-0 flex-1">
                            <div class="text-sm font-bold text-white truncate">{{ $e->full_name }}</div>
                            <div class="text-[11px] text-zinc-500 truncate">{{ $e->email }}</div>
                        </div>
                        <div class="text-right">
                            <div class="text-xs font-mono text-zinc-300">{{ $sym }}{{ number_format((float) $e->amount) }}</div>
                            <div class="text-[10px] text-zinc-600">{{ optional($e->paid_at)->diffForHumans(short: true) }}</div>
                        </div>
                    </div>
                @empty
                    <div class="px-5 py-8 text-center text-sm text-zinc-600">No enrollments yet.</div>
                @endforelse
            </div>
        </div>

        <div class="rounded-2xl border border-zinc-800 bg-zinc-900/40">
            <div class="flex items-center justify-between px-5 py-4 border-b border-zinc-800">
                <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-zinc-400">Recent leads</h3>
                <a href="{{ route('admin.leads') }}" class="text-[10px] font-black uppercase tracking-widest text-cyan-500 hover:text-cyan-400">All →</a>
            </div>
            <div class="divide-y divide-zinc-900">
                @forelse($recentLeads as $l)
                    <div class="flex items-center gap-3 px-5 py-3">
                        <x-admin.avatar :name="$l->name" />
                        <div class="min-w-0 flex-1">
                            <div class="text-sm font-bold text-white truncate">{{ $l->name }}</div>
                            <div class="text-[11px] text-zinc-500 truncate">{{ $l->email }}</div>
                        </div>
                        <div class="text-right">
                            <span class="text-[9px] font-black uppercase tracking-widest px-2 py-0.5 rounded bg-zinc-800 text-zinc-400">{{ $l->source ?: $l->interest }}</span>
                            <div class="text-[10px] text-zinc-600 mt-1">{{ optional($l->created_at)->diffForHumans(short: true) }}</div>
                        </div>
                    </div>
                @empty
                    <div class="px-5 py-8 text-center text-sm text-zinc-600">No leads yet.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
