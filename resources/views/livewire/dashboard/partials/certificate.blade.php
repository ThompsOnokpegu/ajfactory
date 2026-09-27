{{--
    Certificate of completion.

    Two states. Before it's earned: progress toward it, plus the name field so they can
    set how they'll be credited in advance rather than discovering it at the finish line.
    After: the code, the issue date, and a link to the printable certificate.

    The name field is always editable - see App\Support\Certificate for why that's a
    deliberate tradeoff (verification attests the completion, not the identity).
--}}
@if($certificate)
    @php $earned = $certificate['earned']; @endphp

    <div class="border rounded-2xl p-6 lg:p-8 {{ $earned ? 'border-cyan-500/40 bg-cyan-500/5' : 'border-zinc-900 bg-zinc-950/50' }}">
        <div class="flex items-center gap-2 mb-3">
            <span class="text-[10px] font-black uppercase tracking-[0.2em] {{ $earned ? 'text-cyan-400' : 'text-zinc-500' }}">Certificate of completion</span>
        </div>

        @if($earned)
            <h3 class="text-lg font-black text-white uppercase italic tracking-tighter">Earned &check;</h3>
            <p class="text-xs text-zinc-400 mt-2 leading-relaxed">
                All {{ $certificate['total'] }} modules shipped and approved. Issued {{ $certificate['issued_at'] }}.
            </p>

            <div class="flex flex-wrap items-center gap-3 mt-5">
                <a href="{{ route('certificate') }}" target="_blank" rel="noopener"
                   class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-cyan-500 text-black text-[11px] font-black uppercase tracking-widest hover:bg-white transition-all">
                    View certificate
                </a>
                <span class="text-[11px] font-mono text-zinc-500">
                    Verification code <span class="text-zinc-300">{{ $certificate['code'] }}</span>
                </span>
            </div>
        @else
            <h3 class="text-lg font-black text-white uppercase italic tracking-tighter">
                {{ $certificate['approved'] }} of {{ $certificate['total'] }} modules approved
            </h3>
            <p class="text-xs text-zinc-400 mt-2 leading-relaxed">
                Get every module's proof approved and your certificate is issued automatically - no request needed.
            </p>
            <div class="h-1.5 w-full bg-zinc-900 rounded-full overflow-hidden mt-4">
                <div class="h-full bg-cyan-500 transition-all duration-700"
                     style="width: {{ $certificate['total'] > 0 ? round(($certificate['approved'] / $certificate['total']) * 100) : 0 }}%"></div>
            </div>
        @endif

        {{-- Name as it should be printed --}}
        <div class="mt-6 pt-5 border-t border-zinc-800/60">
            <label for="certificateName" class="block text-[10px] font-black uppercase tracking-widest text-zinc-500">
                Your name as it should appear
            </label>
            <p class="text-[11px] text-zinc-600 mt-1 leading-relaxed">
                This is what gets printed{{ $earned ? ' and shown on your verification page' : '' }}. Changing it here does not change the name on your account or your payment record.
            </p>

            <form wire:submit.prevent="saveCertificateName" class="mt-3 flex flex-col sm:flex-row gap-2">
                <input type="text" id="certificateName" wire:model="certificateName" maxlength="60"
                       placeholder="e.g. Tolulope O. Ogunsola"
                       class="flex-1 bg-zinc-950 border border-zinc-800 text-white p-3 rounded-xl text-sm focus:border-cyan-500 focus:ring-0 transition-all placeholder:text-zinc-700">
                <button type="submit" wire:loading.attr="disabled"
                        class="px-6 py-3 rounded-xl bg-white text-black text-[11px] font-black uppercase tracking-widest hover:bg-cyan-500 transition-all disabled:opacity-50">
                    <span wire:loading.remove wire:target="saveCertificateName">Save</span>
                    <span wire:loading wire:target="saveCertificateName">Saving...</span>
                </button>
            </form>

            @error('certificateName')
                <p class="text-[11px] text-amber-400 mt-2">{{ $message }}</p>
            @enderror

            @if($certificateSaved)
                <p class="text-[11px] text-green-500 mt-2">Saved. Your certificate will print this name.</p>
            @endif
        </div>
    </div>
@endif
