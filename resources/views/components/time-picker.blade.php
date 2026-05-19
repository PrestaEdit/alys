@props(['label' => '', 'property', 'value' => '08:00'])

@php
    $parts = explode(':', ($value ?: '08:00') . ':00');
    $initH = max(0, min(23, (int)($parts[0] ?? 8)));
    $initM = min(55, (int)(round((int)($parts[1] ?? 0) / 5) * 5));
@endphp

<div x-data="{
    h: {{ $initH }},
    m: {{ $initM }},
    sync() {
        $wire['{{ $property }}'] = String(this.h).padStart(2,'0') + ':' + String(this.m).padStart(2,'0');
    },
    incH()  { this.h = (this.h + 1) % 24;  this.sync(); },
    decH()  { this.h = (this.h + 23) % 24; this.sync(); },
    incM()  { this.m = this.m >= 55 ? 0 : this.m + 5; this.sync(); },
    decM()  { this.m = this.m <= 0  ? 55 : this.m - 5; this.sync(); },
}">
    @if($label)
    <label class="block text-xs font-semibold text-slate-600 mb-3">{{ $label }}</label>
    @endif

    <div class="flex items-center justify-center gap-4">
        {{-- Heures --}}
        <div class="flex flex-col items-center gap-1">
            <div class="flex items-center gap-2">
                <button type="button" @click="decH"
                        class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center text-slate-600 text-xl font-light hover:bg-slate-200 active:bg-slate-300 transition-colors select-none">−</button>
                <div class="w-14 h-12 rounded-2xl border-2 border-sky-200 bg-sky-50 flex items-center justify-center">
                    <span x-text="String(h).padStart(2,'0')" class="text-2xl font-extrabold text-sky-700 tabular-nums"></span>
                </div>
                <button type="button" @click="incH"
                        class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center text-slate-600 text-xl font-light hover:bg-slate-200 active:bg-slate-300 transition-colors select-none">+</button>
            </div>
            <span class="text-xs text-slate-400">heure</span>
        </div>

        <span class="text-3xl font-bold text-slate-300 mb-4">:</span>

        {{-- Minutes --}}
        <div class="flex flex-col items-center gap-1">
            <div class="flex items-center gap-2">
                <button type="button" @click="decM"
                        class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center text-slate-600 text-xl font-light hover:bg-slate-200 active:bg-slate-300 transition-colors select-none">−</button>
                <div class="w-14 h-12 rounded-2xl border-2 border-sky-200 bg-sky-50 flex items-center justify-center">
                    <span x-text="String(m).padStart(2,'0')" class="text-2xl font-extrabold text-sky-700 tabular-nums"></span>
                </div>
                <button type="button" @click="incM"
                        class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center text-slate-600 text-xl font-light hover:bg-slate-200 active:bg-slate-300 transition-colors select-none">+</button>
            </div>
            <span class="text-xs text-slate-400">min (×5)</span>
        </div>
    </div>
</div>
