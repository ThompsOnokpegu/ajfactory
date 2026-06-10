{{-- Proof checkpoint submit/resubmit form. Expects $label. Used by the dashboard terminal. --}}
<form wire:submit.prevent="submitCheckpoint" class="mt-4 space-y-3">
    <input
        type="url"
        wire:model="proofUrl"
        placeholder="https://loom.com/share/...  or your screenshot link"
        class="w-full bg-zinc-950 border border-zinc-800 text-white p-3 rounded-xl focus:border-cyan-500 focus:ring-0 transition-all font-mono text-xs placeholder:text-zinc-700"
    >
    @error('proofUrl') <p class="text-[10px] text-red-500 uppercase font-bold tracking-widest">{{ $message }}</p> @enderror

    <button
        type="submit"
        wire:loading.attr="disabled"
        class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-white text-black text-[11px] font-black uppercase tracking-widest hover:bg-cyan-500 transition-all disabled:opacity-50"
    >
        <span wire:loading.remove wire:target="submitCheckpoint">{{ $label ?? 'Submit proof' }}</span>
        <span wire:loading wire:target="submitCheckpoint">Submitting...</span>
    </button>
</form>
