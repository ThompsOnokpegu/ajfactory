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

        return ['enrollments' => $rows];
    }
}; ?>

<div class="max-w-6xl mx-auto space-y-6" wire:key="enrollments-page">

    @if($message)
        <div class="p-4 rounded-xl bg-cyan-500/10 border border-cyan-500/20 text-cyan-300 text-xs font-mono break-all">{{ $message }}</div>
    @endif

    <!-- Manual enrol -->
    <div>
        <button wire:click="$toggle('showEnrol')" class="text-[10px] font-black uppercase tracking-widest px-4 py-2 rounded-lg border border-zinc-800 text-zinc-400 hover:text-white hover:border-cyan-500/50 transition">
            {{ $showEnrol ? '× Close' : '+ Manual enrolment' }}
        </button>
        @if($showEnrol)
            <form wire:submit="manualEnrol" class="mt-4 p-6 rounded-2xl bg-zinc-900/50 border border-zinc-800 grid sm:grid-cols-2 gap-4">
                <div><label class="text-[10px] font-black uppercase text-zinc-600 tracking-widest">Full name</label>
                    <input wire:model="meName" class="mt-1 w-full bg-zinc-950 border border-zinc-800 text-white p-3 rounded-lg text-sm focus:border-cyan-500 focus:ring-0">
                    @error('meName') <span class="text-[10px] text-red-500">{{ $message }}</span> @enderror</div>
                <div><label class="text-[10px] font-black uppercase text-zinc-600 tracking-widest">Email</label>
                    <input wire:model="meEmail" type="email" class="mt-1 w-full bg-zinc-950 border border-zinc-800 text-white p-3 rounded-lg text-sm focus:border-cyan-500 focus:ring-0">
                    @error('meEmail') <span class="text-[10px] text-red-500">{{ $message }}</span> @enderror</div>
                <div><label class="text-[10px] font-black uppercase text-zinc-600 tracking-widest">WhatsApp</label>
                    <input wire:model="meWhatsapp" class="mt-1 w-full bg-zinc-950 border border-zinc-800 text-white p-3 rounded-lg text-sm focus:border-cyan-500 focus:ring-0"></div>
                <div class="grid grid-cols-2 gap-2">
                    <div><label class="text-[10px] font-black uppercase text-zinc-600 tracking-widest">Amount</label>
                        <input wire:model="meAmount" type="number" class="mt-1 w-full bg-zinc-950 border border-zinc-800 text-white p-3 rounded-lg text-sm focus:border-cyan-500 focus:ring-0"></div>
                    <div><label class="text-[10px] font-black uppercase text-zinc-600 tracking-widest">Currency</label>
                        <select wire:model="meCurrency" class="mt-1 w-full bg-zinc-950 border border-zinc-800 text-white p-3 rounded-lg text-sm focus:border-cyan-500 focus:ring-0"><option>NGN</option><option>USD</option></select></div>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div><label class="text-[10px] font-black uppercase text-zinc-600 tracking-widest">Plan</label>
                        <select wire:model="mePlan" class="mt-1 w-full bg-zinc-950 border border-zinc-800 text-white p-3 rounded-lg text-sm focus:border-cyan-500 focus:ring-0"><option value="full">full</option><option value="installment">installment</option></select></div>
                    <div><label class="text-[10px] font-black uppercase text-zinc-600 tracking-widest">Cohort</label>
                        <input wire:model="meCohort" type="number" class="mt-1 w-full bg-zinc-950 border border-zinc-800 text-white p-3 rounded-lg text-sm focus:border-cyan-500 focus:ring-0"></div>
                </div>
                <div class="sm:col-span-2">
                    <button type="submit" wire:loading.attr="disabled" class="px-6 py-3 rounded-lg bg-cyan-500 text-black font-black uppercase tracking-widest text-xs hover:bg-white transition">Enrol &amp; grant access</button>
                    <span class="ml-3 text-[10px] text-zinc-600">Marks as paid in full + fires the welcome email.</span>
                </div>
            </form>
        @endif
    </div>

    <!-- Filters -->
    <div class="flex flex-wrap gap-3">
        <input wire:model.live.debounce.300ms="search" placeholder="Search name or email…" class="flex-1 min-w-[200px] bg-zinc-900 border border-zinc-800 text-white p-3 rounded-lg text-sm focus:border-cyan-500 focus:ring-0 placeholder:text-zinc-600">
        <select wire:model.live="plan" class="bg-zinc-900 border border-zinc-800 text-zinc-300 p-3 rounded-lg text-sm focus:border-cyan-500 focus:ring-0"><option value="">All plans</option><option value="full">Full</option><option value="installment">Installment</option></select>
        <select wire:model.live="status" class="bg-zinc-900 border border-zinc-800 text-zinc-300 p-3 rounded-lg text-sm focus:border-cyan-500 focus:ring-0"><option value="">All statuses</option><option value="paid">Paid</option><option value="pending">Pending</option></select>
        <select wire:model.live="suspended" class="bg-zinc-900 border border-zinc-800 text-zinc-300 p-3 rounded-lg text-sm focus:border-cyan-500 focus:ring-0"><option value="">Any access</option><option value="1">Suspended</option><option value="0">Active</option></select>
    </div>

    <!-- Table -->
    <div class="space-y-2">
        @forelse($enrollments as $e)
            @php $sym = ($e->currency ?: 'NGN') === 'NGN' ? '₦' : '$'; @endphp
            <div wire:key="enr-{{ $e->id }}" x-data="{ open: false }" class="rounded-2xl border border-zinc-800 bg-zinc-900/40">
                <button @click="open = !open" class="w-full flex items-center gap-4 p-4 text-left">
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-bold text-white truncate">{{ $e->full_name }}</div>
                        <div class="text-[11px] text-zinc-500 truncate">{{ $e->email }}</div>
                    </div>
                    <div class="hidden sm:flex items-center gap-2">
                        <span class="text-[9px] font-black uppercase tracking-widest px-2 py-0.5 rounded bg-zinc-800 text-zinc-400">{{ $e->plan_type }}</span>
                        <span class="text-[9px] font-black uppercase tracking-widest px-2 py-0.5 rounded bg-zinc-800 text-zinc-400">C{{ $e->cohort }}</span>
                    </div>
                    <span class="text-[9px] font-black uppercase tracking-widest px-2 py-0.5 rounded {{ $e->status === 'paid' ? 'bg-green-500/10 text-green-500' : 'bg-amber-500/10 text-amber-400' }}">{{ $e->status }}</span>
                    @if($e->access_suspended)
                        <span class="text-[9px] font-black uppercase tracking-widest px-2 py-0.5 rounded bg-red-500/10 text-red-400">Suspended</span>
                    @endif
                    @if($e->plan_type === 'installment' && (float)$e->balance_due > 0)
                        <span class="text-xs font-mono text-amber-400">{{ $sym }}{{ number_format($e->balance_due) }}</span>
                    @endif
                    <svg class="w-4 h-4 text-zinc-600 transition-transform" :class="open ? 'rotate-90' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>

                <div x-show="open" x-cloak class="px-4 pb-4 border-t border-zinc-800/60 pt-4 space-y-3">
                    <div class="grid sm:grid-cols-2 gap-x-6 gap-y-1 text-[11px] font-mono text-zinc-500">
                        <div>Ref: <span class="text-zinc-300">{{ $e->payment_reference }}</span></div>
                        <div>Paid: <span class="text-zinc-300">{{ optional($e->paid_at)->toDayDateTimeString() ?? '—' }}</span></div>
                        <div>Total: <span class="text-zinc-300">{{ $sym }}{{ number_format((float)($e->amount_total ?: $e->amount)) }}</span></div>
                        <div>Balance: <span class="text-zinc-300">{{ $sym }}{{ number_format((float)$e->balance_due) }}</span> ({{ $e->second_payment_status }})</div>
                        @if($e->second_payment_due_at)<div>2nd due: <span class="text-zinc-300">{{ $e->second_payment_due_at->toFormattedDateString() }}</span></div>@endif
                    </div>

                    <div class="flex flex-wrap gap-2 pt-1">
                        <button wire:click="toggleSuspend({{ $e->id }})" wire:confirm="{{ $e->access_suspended ? 'Restore access?' : 'Suspend access for this student?' }}"
                            class="text-[10px] font-black uppercase tracking-widest px-3 py-2 rounded-lg border {{ $e->access_suspended ? 'border-green-500/40 text-green-400' : 'border-red-500/40 text-red-400' }} hover:bg-zinc-800 transition">
                            {{ $e->access_suspended ? 'Reinstate access' : 'Suspend access' }}
                        </button>

                        @if($e->plan_type === 'installment' && (float)$e->balance_due > 0)
                            <button wire:click="resendInstallmentLink({{ $e->id }})" class="text-[10px] font-black uppercase tracking-widest px-3 py-2 rounded-lg border border-cyan-500/40 text-cyan-400 hover:bg-zinc-800 transition">Re-send pay link</button>
                            <button wire:click="markBalancePaid({{ $e->id }})" wire:confirm="Mark this balance as paid in full?" class="text-[10px] font-black uppercase tracking-widest px-3 py-2 rounded-lg border border-amber-500/40 text-amber-400 hover:bg-zinc-800 transition">Mark balance paid</button>
                        @endif

                        <div class="flex items-center gap-1">
                            <span class="text-[10px] font-black uppercase tracking-widest text-zinc-600">Cohort:</span>
                            @foreach([1,2] as $c)
                                <button wire:click="setCohort({{ $e->id }}, {{ $c }})" class="text-[10px] font-black px-2.5 py-2 rounded-lg border {{ $e->cohort === $c ? 'border-cyan-500 text-cyan-400' : 'border-zinc-800 text-zinc-500' }} hover:bg-zinc-800 transition">{{ $c }}</button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="p-10 text-center rounded-2xl border border-dashed border-zinc-800 text-zinc-500 text-sm">No enrollments match.</div>
        @endforelse
    </div>

    <div>{{ $enrollments->links() }}</div>
</div>
