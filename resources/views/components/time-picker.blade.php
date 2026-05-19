@props(['label' => '', 'property', 'value' => '08:00'])

@php
    $parts = explode(':', ($value ?: '08:00') . ':00');
    $initH = max(0, min(23, (int)($parts[0] ?? 8)));
    $initM = in_array((int)($parts[1] ?? 0), [0, 15, 30, 45]) ? (int)$parts[1] : 0;
@endphp

<div x-data="{
    h: {{ $initH }},
    m: {{ $initM }},
    sync() {
        $wire.set('{{ $property }}', String(this.h).padStart(2,'0') + ':' + String(this.m).padStart(2,'0'));
    },
    incH()  { this.h = (this.h + 1) % 24;  this.sync(); },
    decH()  { this.h = (this.h + 23) % 24; this.sync(); },
    incM()  { this.m = this.m >= 45 ? 0 : this.m + 15; this.sync(); },
    decM()  { this.m = this.m <= 0  ? 45 : this.m - 15; this.sync(); },
}">
    @if($label)
    <label class="block text-xs font-semibold text-slate-600 mb-3">{{ $label }}</label>
    @endif

    <div class="flex items-center justify-center gap-3">
        {{-- Heures --}}
        <div class="flex flex-col items-center gap-1.5">
            <button type="button" @click="incH"
                    class="w-11 h-11 rounded-xl bg-slate-100 flex items-center justify-center text-slate-500 hover:bg-slate-200 active:bg-slate-300 transition-colors select-none">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4"><path fill-rule="evenodd" d="M10 17a.75.75 0 0 1-.75-.75V5.612L5.29 9.77a.75.75 0 0 1-1.08-1.04l5.25-5.5a.75.75 0 0 1 1.08 0l5.25 5.5a.75.75 0 1 1-1.08 1.04l-3.96-4.158V16.25A.75.75 0 0 1 10 17Z" clip-rule="evenodd" /></svg>
            </button>
            <div class="w-16 h-14 rounded-2xl border-2 border-sky-200 bg-sky-50 flex items-center justify-center">
                <span x-text="String(h).padStart(2,'0')" class="text-2xl font-extrabold text-sky-700 tabular-nums"></span>
            </div>
            <button type="button" @click="decH"
                    class="w-11 h-11 rounded-xl bg-slate-100 flex items-center justify-center text-slate-500 hover:bg-slate-200 active:bg-slate-300 transition-colors select-none">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4"><path fill-rule="evenodd" d="M10 3a.75.75 0 0 1 .75.75v10.638l3.96-4.158a.75.75 0 1 1 1.08 1.04l-5.25 5.5a.75.75 0 0 1-1.08 0l-5.25-5.5a.75.75 0 1 1 1.08-1.04l3.96 4.158V3.75A.75.75 0 0 1 10 3Z" clip-rule="evenodd" /></svg>
            </button>
            <span class="text-xs text-slate-400">heure</span>
        </div>

        <span class="text-3xl font-bold text-slate-300 mb-6">:</span>

        {{-- Minutes --}}
        <div class="flex flex-col items-center gap-1.5">
            <button type="button" @click="incM"
                    class="w-11 h-11 rounded-xl bg-slate-100 flex items-center justify-center text-slate-500 hover:bg-slate-200 active:bg-slate-300 transition-colors select-none">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4"><path fill-rule="evenodd" d="M10 17a.75.75 0 0 1-.75-.75V5.612L5.29 9.77a.75.75 0 0 1-1.08-1.04l5.25-5.5a.75.75 0 0 1 1.08 0l5.25 5.5a.75.75 0 1 1-1.08 1.04l-3.96-4.158V16.25A.75.75 0 0 1 10 17Z" clip-rule="evenodd" /></svg>
            </button>
            <div class="w-16 h-14 rounded-2xl border-2 border-sky-200 bg-sky-50 flex items-center justify-center">
                <span x-text="String(m).padStart(2,'0')" class="text-2xl font-extrabold text-sky-700 tabular-nums"></span>
            </div>
            <button type="button" @click="decM"
                    class="w-11 h-11 rounded-xl bg-slate-100 flex items-center justify-center text-slate-500 hover:bg-slate-200 active:bg-slate-300 transition-colors select-none">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4"><path fill-rule="evenodd" d="M10 3a.75.75 0 0 1 .75.75v10.638l3.96-4.158a.75.75 0 1 1 1.08 1.04l-5.25 5.5a.75.75 0 0 1-1.08 0l-5.25-5.5a.75.75 0 1 1 1.08-1.04l3.96 4.158V3.75A.75.75 0 0 1 10 3Z" clip-rule="evenodd" /></svg>
            </button>
            <span class="text-xs text-slate-400">min (×15)</span>
        </div>
    </div>
</div>
