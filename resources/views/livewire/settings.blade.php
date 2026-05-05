<div class="p-4 max-w-lg mx-auto">

    {{-- Header --}}
    <div class="flex items-center gap-3 mb-5">
        <a href="{{ route('home') }}"
           class="w-8 h-8 rounded-xl bg-slate-100 flex items-center justify-center text-slate-500 hover:bg-slate-200 transition-colors text-lg">
            ‹
        </a>
        <h1 class="text-xl font-extrabold text-slate-900">Paramètres</h1>
    </div>

    @if(session('success'))
    <div class="bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-2 mb-4">
        <p class="text-xs font-semibold text-emerald-700">{{ session('success') }}</p>
    </div>
    @endif

    <div class="bg-white rounded-2xl p-5 shadow-sm mb-4">
        <p class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-4">Informations patient</p>

        <div class="mb-4">
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Prénom du patient</label>
            <input type="text"
                   wire:model="patientName"
                   placeholder="Alys"
                   class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-400">
            @error('patientName')
            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="bg-white rounded-2xl p-5 shadow-sm mb-4">
        <p class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-4">Période de traitement</p>

        <div class="mb-4">
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Date de début</label>
            <input type="date"
                   wire:model="treatmentStart"
                   class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-400">
            @error('treatmentStart')
            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-1">
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Date de fin</label>
            <input type="date"
                   wire:model="treatmentEnd"
                   class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-400">
            @error('treatmentEnd')
            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <button wire:click="save"
            class="w-full py-3 rounded-xl text-sm font-bold text-white transition-colors hover:opacity-90"
            style="background: linear-gradient(135deg, #0ea5e9, #6366f1);">
        Enregistrer
    </button>

</div>
