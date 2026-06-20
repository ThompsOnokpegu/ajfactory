<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use App\Models\Enrollment;
use App\Support\StudentProvisioner;
use Illuminate\Support\Facades\Http;

new #[Layout('components.layouts.admin', ['title' => 'Enrollments'])] class extends Component {
    use WithPagination;

    #[Url] public string $search = '';
    #[Url] public string $plan = '';
    #[Url] public string $status = '';
    #[Url] public string $suspended = '';

    public string $message = '';

    // Manual enrolment form
    public bool $showEnrol = false;
    public string $meName = '';
    public string $meEmail = '';
    public string $meWhatsapp = '';
    public string $meAmount = '79000';
    public string $meCurrency = 'NGN';
    public string $mePlan = 'full';
    public int $meCohort = 2;

    public function mount(): void
    {
        abort_unless(auth()->user()?->is_admin, 403);
    }

    public function updating($name): void
    {
        if (in_array($name, ['search', 'plan', 'status', 'suspended'])) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'plan', 'status', 'suspended']);
        $this->resetPage();
    }

    private function find(int $id): ?Enrollment
    {
        return Enrollment::find($id);
    }

    public function toggleSuspend(int $id): void
    {
        $e = $this->find($id);
        if (! $e) return;
        $e->update(['access_suspended' => ! $e->access_suspended]);
        $this->message = $e->access_suspended ? "Access suspended for {$e->email}." : "Access restored for {$e->email}.";
    }

    public function markBalancePaid(int $id): void
    {
        $e = $this->find($id);
        if (! $e) return;
        $e->update(['balance_due' => 0, 'second_payment_status' => 'paid', 'access_suspended' => false]);
        $this->message = "Balance cleared for {$e->email}.";
    }

    public function setCohort(int $id, int $cohort): void
    {
        $e = $this->find($id);
        if (! $e) return;
        $e->update(['cohort' => $cohort]);
        $this->message = "{$e->email} moved to Cohort {$cohort}.";
    }

    public function resendInstallmentLink(int $id): void
    {
        $e = $this->find($id);
        if (! $e || $e->plan_type !== 'installment' || (float) $e->balance_due <= 0) return;

        $payUrl = \Illuminate\Support\Facades\URL::signedRoute('installment.pay', ['enrollment' => $e->id]);
        $e->update(['second_payment_status' => 'link_sent', 'installment_reminder_sent_at' => now()]);

        if ($url = (config('services.n8n.installment_webhook') ?: config('services.n8n.enrollment_webhook'))) {
            try {
                Http::post($url, [
                    'event' => 'installment_due',
                    'full_name' => $e->full_name, 'email' => $e->email, 'phone' => $e->whatsapp,
                    'amount' => $e->balance_due, 'currency' => $e->currency,
                    'pay_url' => $payUrl, 'original_reference' => $e->payment_reference,
                ]);
            } catch (\Throwable $ex) { /* logged downstream */ }
        }

        $this->message = "Payment link re-sent to {$e->email}: {$payUrl}";
    }

    public function manualEnrol(StudentProvisioner $provisioner): void
    {
        $data = $this->validate([
            'meName' => 'required|string|max:255',
            'meEmail' => 'required|email|max:255',
            'meWhatsapp' => 'nullable|string|max:40',
            'meAmount' => 'required|numeric|min:0',
            'meCurrency' => 'required|in:NGN,USD',
            'mePlan' => 'required|in:full,installment',
            'meCohort' => 'required|integer|min:1|max:9',
        ]);

        $result = $provisioner->manualEnrol([
            'name' => $data['meName'],
            'email' => $data['meEmail'],
            'whatsapp' => $data['meWhatsapp'],
            'amount' => (float) $data['meAmount'],
            'currency' => $data['meCurrency'],
            'plan_type' => $data['mePlan'],
            'cohort' => $data['meCohort'],
        ]);

        $this->reset(['meName', 'meEmail', 'meWhatsapp']);
        $this->showEnrol = false;
        $this->message = $result['created']
            ? "Enrolled {$data['meEmail']} — temp password: {$result['temp_password']}"
            : "Updated enrolment for existing user {$data['meEmail']} (kept their password).";
    }

    public function with(): array
    {
        $rows = Enrollment::query()
            ->when($this->search, fn ($q) => $q->where(fn ($w) =>
                $w->where('full_name', 'like', "%{$this->search}%")->orWhere('email', 'like', "%{$this->search}%")))
            ->when($this->plan, fn ($q) => $q->where('plan_type', $this->plan))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->when($this->suspended !== '', fn ($q) => $q->where('access_suspended', (bool) $this->suspended))
            ->orderByDesc('created_at')
            ->paginate(15);

        return ['enrollments' => $rows, 'total' => $rows->total(), 'hasFilters' => $this->search || $this->plan || $this->status || $this->suspended !== ''];
    }
}; ?>

<div class="max-w-6xl mx-auto space-y-5" wire:key="enrollments-page">

    <!-- Header -->
    <div class="flex items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-black tracking-tighter text-white">Enrollments</h2>
            <p class="text-[11px] text-zinc-500 mt-0.5">{{ number_format($total) }} {{ \Illuminate\Support\Str::plural('record', $total) }}</p>
        </div>
        <button wire:click="$toggle('showEnrol')"
            class="inline-flex items-center gap-2 text-[10px] font-black uppercase tracking-widest px-4 py-2.5 rounded-lg bg-cyan-500 text-black hover:bg-white transition">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Manual enrolment
        </button>
    </div>

    @if($message)
        <div class="p-4 rounded-xl bg-cyan-500/10 border border-cyan-500/20 text-cyan-300 text-xs font-mono break-all flex items-start gap-2">
            <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>{{ $message }}</span>
        </div>
    @endif

    <!-- Manual enrol -->
    @if($showEnrol)
        <form wire:submit="manualEnrol" class="p-6 rounded-2xl bg-zinc-900/60 border border-cyan-500/20">
            <div class="text-[10px] font-black uppercase tracking-[0.2em] text-cyan-500 mb-4">New manual enrolment</div>
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div><label class="text-[10px] font-black uppercase text-zinc-600 tracking-widest">Full name</label>
                    <input wire:model="meName" class="mt-1 w-full bg-zinc-950 border border-zinc-800 text-white p-3 rounded-lg text-sm focus:border-cyan-500 focus:ring-0">
                    @error('meName') <span class="text-[10px] text-red-500">{{ $message }}</span> @enderror</div>
                <div><label class="text-[10px] font-black uppercase text-zinc-600 tracking-widest">Email</label>
                    <input wire:model="meEmail" type="email" class="mt-1 w-full bg-zinc-950 border border-zinc-800 text-white p-3 rounded-lg text-sm focus:border-cyan-500 focus:ring-0">
                    @error('meEmail') <span class="text-[10px] text-red-500">{{ $message }}</span> @enderror</div>
                <div><label class="text-[10px] font-black uppercase text-zinc-600 tracking-widest">WhatsApp</label>
                    <input wire:model="meWhatsapp" class="mt-1 w-full bg-zinc-950 border border-zinc-800 text-white p-3 rounded-lg text-sm focus:border-cyan-500 focus:ring-0"></div>
                <div><label class="text-[10px] font-black uppercase text-zinc-600 tracking-widest">Amount</label>
                    <input wire:model="meAmount" type="number" class="mt-1 w-full bg-zinc-950 border border-zinc-800 text-white p-3 rounded-lg text-sm focus:border-cyan-500 focus:ring-0"></div>
                <div><label class="text-[10px] font-black uppercase text-zinc-600 tracking-widest">Currency</label>
                    <select wire:model="meCurrency" class="mt-1 w-full bg-zinc-950 border border-zinc-800 text-white p-3 rounded-lg text-sm focus:border-cyan-500 focus:ring-0"><option>NGN</option><option>USD</option></select></div>
                <div class="grid grid-cols-2 gap-2">
                    <div><label class="text-[10px] font-black uppercase text-zinc-600 tracking-widest">Plan</label>
                        <select wire:model="mePlan" class="mt-1 w-full bg-zinc-950 border border-zinc-800 text-white p-3 rounded-lg text-sm focus:border-cyan-500 focus:ring-0"><option value="full">full</option><option value="installment">installment</option></select></div>
                    <div><label class="text-[10px] font-black uppercase text-zinc-600 tracking-widest">Cohort</label>
                        <input wire:model="meCohort" type="number" class="mt-1 w-full bg-zinc-950 border border-zinc-800 text-white p-3 rounded-lg text-sm focus:border-cyan-500 focus:ring-0"></div>
                </div>
            </div>
            <div class="flex items-center gap-3 mt-5">
                <button type="submit" wire:loading.attr="disabled" class="px-6 py-3 rounded-lg bg-cyan-500 text-black font-black uppercase tracking-widest text-xs hover:bg-white transition">Enrol &amp; grant access</button>
                <button type="button" wire:click="$set('showEnrol', false)" class="text-[10px] font-black uppercase tracking-widest text-zinc-500 hover:text-white">Cancel</button>
                <span class="text-[10px] text-zinc-600">Marks as paid in full + fires the welcome email.</span>
            </div>
        </form>
    @endif

    <!-- Filters -->
    <div class="flex flex-wrap items-center gap-2">
        <div class="relative flex-1 min-w-[220px]">
            <svg class="w-4 h-4 text-zinc-600 absolute left-3 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input wire:model.live.debounce.300ms="search" placeholder="Search name or email…" class="w-full bg-zinc-900 border border-zinc-800 text-white pl-9 pr-3 py-2.5 rounded-lg text-sm focus:border-cyan-500 focus:ring-0 placeholder:text-zinc-600">
        </div>
        <select wire:model.live="plan" class="bg-zinc-900 border border-zinc-800 text-zinc-300 px-3 py-2.5 rounded-lg text-sm focus:border-cyan-500 focus:ring-0"><option value="">All plans</option><option value="full">Full</option><option value="installment">Installment</option></select>
        <select wire:model.live="status" class="bg-zinc-900 border border-zinc-800 text-zinc-300 px-3 py-2.5 rounded-lg text-sm focus:border-cyan-500 focus:ring-0"><option value="">All statuses</option><option value="paid">Paid</option><option value="pending">Pending</option></select>
        <select wire:model.live="suspended" class="bg-zinc-900 border border-zinc-800 text-zinc-300 px-3 py-2.5 rounded-lg text-sm focus:border-cyan-500 focus:ring-0"><option value="">Any access</option><option value="1">Suspended</option><option value="0">Active</option></select>
        @if($hasFilters)
            <button wire:click="clearFilters" class="text-[10px] font-black uppercase tracking-widest text-zinc-500 hover:text-white px-2">Clear</button>
        @endif
    </div>

    <!-- List -->
    <div class="rounded-2xl border border-zinc-800 divide-y divide-zinc-900 overflow-hidden">
        @forelse($enrollments as $e)
            @php $sym = ($e->currency ?: 'NGN') === 'NGN' ? '₦' : '$'; $hasBalance = $e->plan_type === 'installment' && (float)$e->balance_due > 0; @endphp
            <div wire:key="enr-{{ $e->id }}" x-data="{ menu: false, detail: false }" class="bg-zinc-900/30">
                <div class="flex items-start gap-3 px-4 sm:px-5 py-3.5">
                    <x-admin.avatar :name="$e->full_name" />
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <span class="text-sm font-bold text-white">{{ $e->full_name }}</span>
                            <span class="text-[9px] font-black uppercase tracking-widest px-2 py-0.5 rounded bg-zinc-800 text-zinc-400">{{ $e->plan_type }}</span>
                            <span class="text-[9px] font-black uppercase tracking-widest px-2 py-0.5 rounded bg-zinc-800 text-zinc-500">C{{ $e->cohort }}</span>
                            <span class="text-[9px] font-black uppercase tracking-widest px-2 py-0.5 rounded {{ $e->status === 'paid' ? 'bg-green-500/10 text-green-500' : 'bg-amber-500/10 text-amber-400' }}">{{ $e->status }}</span>
                            @if($e->access_suspended)<span class="text-[9px] font-black uppercase tracking-widest px-2 py-0.5 rounded bg-red-500/10 text-red-400">Suspended</span>@endif
                        </div>
                        <div class="text-[11px] text-zinc-500 truncate mt-0.5">{{ $e->email }}</div>
                        @if($hasBalance)<div class="text-[11px] font-mono text-amber-400 mt-1">Balance due {{ $sym }}{{ number_format($e->balance_due) }}</div>@endif
                    </div>
                    <div class="shrink-0 flex items-center gap-0.5 relative">
                        <button @click="detail = !detail" class="p-2 rounded-md text-zinc-600 hover:text-white hover:bg-zinc-800 transition" title="Details">
                            <svg class="w-4 h-4 transition-transform" :class="detail ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <button @click="menu = !menu" class="p-2 rounded-md text-zinc-600 hover:text-white hover:bg-zinc-800 transition" title="Actions">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M10 6a2 2 0 110-4 2 2 0 010 4zm0 2a2 2 0 110 4 2 2 0 010-4zm0 6a2 2 0 110 4 2 2 0 010-4z"/></svg>
                        </button>
                        <div x-show="menu" x-cloak @click.outside="menu = false" x-transition
                             class="absolute right-0 top-10 z-20 w-52 rounded-xl border border-zinc-700 bg-zinc-900 shadow-2xl shadow-black/50 py-1.5">
                            <button wire:click="toggleSuspend({{ $e->id }})" wire:confirm="{{ $e->access_suspended ? 'Restore access?' : 'Suspend access for this student?' }}" @click="menu=false"
                                class="w-full text-left px-4 py-2 text-xs font-bold {{ $e->access_suspended ? 'text-green-400' : 'text-red-400' }} hover:bg-zinc-800 transition">
                                {{ $e->access_suspended ? 'Reinstate access' : 'Suspend access' }}
                            </button>
                            @if($hasBalance)
                                <button wire:click="resendInstallmentLink({{ $e->id }})" @click="menu=false" class="w-full text-left px-4 py-2 text-xs font-bold text-cyan-400 hover:bg-zinc-800 transition">Re-send pay link</button>
                                <button wire:click="markBalancePaid({{ $e->id }})" wire:confirm="Mark this balance as paid in full?" @click="menu=false" class="w-full text-left px-4 py-2 text-xs font-bold text-amber-400 hover:bg-zinc-800 transition">Mark balance paid</button>
                            @endif
                            <div class="border-t border-zinc-800 my-1.5"></div>
                            <div class="px-4 py-1.5 flex items-center gap-2">
                                <span class="text-[9px] font-black uppercase tracking-widest text-zinc-600">Cohort</span>
                                @foreach([1,2] as $c)
                                    <button wire:click="setCohort({{ $e->id }}, {{ $c }})" @click="menu=false" class="text-[10px] font-black px-2 py-1 rounded border {{ $e->cohort === $c ? 'border-cyan-500 text-cyan-400' : 'border-zinc-700 text-zinc-500' }} hover:bg-zinc-800 transition">{{ $c }}</button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <div x-show="detail" x-cloak class="px-4 sm:px-5 pb-4">
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-x-6 gap-y-2 text-[11px] font-mono text-zinc-500 bg-zinc-950/40 rounded-xl p-4 border border-zinc-800/60">
                        <div>Ref<br><span class="text-zinc-300 break-all">{{ $e->payment_reference }}</span></div>
                        <div>Paid<br><span class="text-zinc-300">{{ optional($e->paid_at)->toFormattedDateString() ?? '—' }}</span></div>
                        <div>Total<br><span class="text-zinc-300">{{ $sym }}{{ number_format((float)($e->amount_total ?: $e->amount)) }}</span></div>
                        <div>2nd payment<br><span class="text-zinc-300">{{ $e->second_payment_status }}{{ $e->second_payment_due_at ? ' · due '.$e->second_payment_due_at->toFormattedDateString() : '' }}</span></div>
                    </div>
                </div>
            </div>
        @empty
            <div class="px-5 py-14 text-center bg-zinc-900/30">
                <p class="text-sm text-zinc-500">No enrollments match your filters.</p>
                @if($hasFilters)<button wire:click="clearFilters" class="mt-3 text-[10px] font-black uppercase tracking-widest text-cyan-500">Clear filters</button>@endif
            </div>
        @endforelse
    </div>

    <div>{{ $enrollments->links() }}</div>
</div>
