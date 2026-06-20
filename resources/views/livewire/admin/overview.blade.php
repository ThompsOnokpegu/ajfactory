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

    public function with(): array
    {
        $collected = Enrollment::where('status', 'paid')
            ->selectRaw('currency, SUM(COALESCE(amount_total, amount) - COALESCE(balance_due, 0)) as total')
            ->groupBy('currency')
            ->pluck('total', 'currency');

        $outstanding = Enrollment::where('plan_type', 'installment')->where('balance_due', '>', 0);

        return [
            'collectedNgn' => (float) ($collected['NGN'] ?? 0),
            'collectedUsd' => (float) ($collected['USD'] ?? 0),
            'paidCount' => Enrollment::where('status', 'paid')->count(),
            'seatsLeft' => Accelerator::seatsLeft(),
            'cap' => (int) config('accelerator.cohort_cap'),
            'earlybird' => Accelerator::earlybirdActive(),
            'soldOut' => Accelerator::isSoldOut(),
            'outstandingTotal' => (float) (clone $outstanding)->sum('balance_due'),
            'outstandingCount' => (clone $outstanding)->count(),
            'suspended' => Enrollment::where('access_suspended', true)->count(),
            'pendingCheckpoints' => Checkpoint::where('status', 'submitted')->count(),
            'mcRegs' => MasterclassRegistration::where('session_date', config('taab.masterclass.date'))->count(),
            'totalLeads' => Student::count(),
        ];
    }
}; ?>

@php
    $card = fn ($label, $value, $sub = null, $accent = 'text-white') =>
        '<div class="p-5 rounded-2xl bg-zinc-900/50 border border-zinc-800">'
        .'<div class="text-[10px] font-black uppercase tracking-widest text-zinc-500 mb-2">'.$label.'</div>'
        .'<div class="text-3xl font-black tracking-tighter '.$accent.'">'.$value.'</div>'
        .($sub ? '<div class="text-[11px] text-zinc-500 mt-1">'.$sub.'</div>' : '')
        .'</div>';
@endphp

<div class="max-w-6xl mx-auto space-y-8">

    <!-- Money -->
    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {!! $card('Collected (NGN)', '₦'.number_format($collectedNgn), $collectedUsd > 0 ? '+ $'.number_format($collectedUsd).' USD' : 'Paid in full + installments received', 'text-cyan-400') !!}
        {!! $card('Paid enrollments', number_format($paidCount)) !!}
        {!! $card('Seats left', $soldOut ? 'Sold out' : $seatsLeft.' / '.$cap, $earlybird ? 'Early-bird active' : 'Regular pricing', $earlybird ? 'text-cyan-400' : 'text-white') !!}
        {!! $card('Outstanding installments', '₦'.number_format($outstandingTotal), $outstandingCount.' '.\Illuminate\Support\Str::plural('student', $outstandingCount).' with a balance', $outstandingTotal > 0 ? 'text-amber-400' : 'text-white') !!}
    </div>

    <!-- Needs attention -->
    <div>
        <div class="text-[10px] font-black uppercase tracking-[0.2em] text-zinc-600 mb-3">Needs attention</div>
        <div class="grid sm:grid-cols-3 gap-4">
            <a href="{{ route('admin.checkpoints') }}" class="p-5 rounded-2xl bg-zinc-900/50 border {{ $pendingCheckpoints > 0 ? 'border-cyan-500/30' : 'border-zinc-800' }} hover:border-cyan-500/50 transition block">
                <div class="text-[10px] font-black uppercase tracking-widest text-zinc-500 mb-2">Checkpoints to review</div>
                <div class="text-3xl font-black tracking-tighter {{ $pendingCheckpoints > 0 ? 'text-cyan-400' : 'text-white' }}">{{ $pendingCheckpoints }}</div>
                <div class="text-[11px] text-cyan-500 mt-2 font-bold uppercase tracking-widest">Review →</div>
            </a>
            <a href="{{ route('admin.enrollments', ['suspended' => 1]) }}" class="p-5 rounded-2xl bg-zinc-900/50 border {{ $suspended > 0 ? 'border-red-500/30' : 'border-zinc-800' }} hover:border-red-500/50 transition block">
                <div class="text-[10px] font-black uppercase tracking-widest text-zinc-500 mb-2">Suspended (overdue)</div>
                <div class="text-3xl font-black tracking-tighter {{ $suspended > 0 ? 'text-red-400' : 'text-white' }}">{{ $suspended }}</div>
                <div class="text-[11px] text-cyan-500 mt-2 font-bold uppercase tracking-widest">Manage →</div>
            </a>
            <a href="{{ route('admin.masterclass') }}" class="p-5 rounded-2xl bg-zinc-900/50 border border-zinc-800 hover:border-cyan-500/50 transition block">
                <div class="text-[10px] font-black uppercase tracking-widest text-zinc-500 mb-2">Next masterclass regs</div>
                <div class="text-3xl font-black tracking-tighter text-white">{{ $mcRegs }}</div>
                <div class="text-[11px] text-zinc-500 mt-2">{{ config('taab.masterclass.date') ?: 'date TBA' }}</div>
            </a>
        </div>
    </div>

    <!-- Funnel -->
    <div class="grid sm:grid-cols-2 gap-4">
        {!! $card('Total leads & waitlist', number_format($totalLeads), 'TAAB tools + masterclass + waitlist') !!}
        <a href="{{ route('admin.leads') }}" class="p-5 rounded-2xl bg-zinc-900/50 border border-zinc-800 hover:border-cyan-500/50 transition flex items-center justify-center">
            <span class="text-xs font-black uppercase tracking-widest text-cyan-500">View leads & waitlist →</span>
        </a>
    </div>
</div>
