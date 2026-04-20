<div class="p-4 max-w-lg mx-auto">

    {{-- Header --}}
    <div class="flex items-center gap-3 mb-5">
        <a href="{{ route('treatments') }}"
           class="w-8 h-8 rounded-xl bg-slate-100 flex items-center justify-center text-slate-500 hover:bg-slate-200 transition-colors text-lg">
            ‹
        </a>
        <h1 class="text-xl font-extrabold text-slate-900">Nouveau traitement</h1>
    </div>

    @if(session('success'))
    <div class="bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-2 mb-4">
        <p class="text-xs font-semibold text-emerald-700">{{ session('success') }}</p>
    </div>
    @endif

    <div class="bg-white rounded-2xl p-5 shadow-sm mb-4">
        <p class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-4">Informations</p>

        {{-- Nom usuel --}}
        <div class="mb-3">
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Nom usuel *</label>
            <input type="text"
                   wire:model="name"
                   placeholder="ex : Méthotrexate"
                   class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-400">
            @error('name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Nom commercial --}}
        <div class="mb-3">
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Nom commercial</label>
            <input type="text"
                   wire:model="commercialName"
                   placeholder="ex : Novatrex"
                   class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-400">
        </div>

        {{-- Type --}}
        <div class="mb-4">
            <label class="block text-xs font-semibold text-slate-600 mb-2">Type *</label>
            <div class="grid grid-cols-2 gap-2">
                @foreach([
                    ['daily', 'Quotidien'],
                    ['weekly', 'Hebdomadaire'],
                    ['cyclic', 'Cyclique'],
                ] as [$val, $label])
                <label class="flex items-center gap-2 px-3 py-2.5 rounded-xl border cursor-pointer transition-colors
                              {{ $type === $val ? 'border-sky-400 bg-sky-50 text-sky-700' : 'border-slate-200 text-slate-600 hover:border-slate-300' }}">
                    <input type="radio" wire:model.live="type" value="{{ $val }}" class="hidden">
                    <span class="text-xs font-semibold">{{ $label }}</span>
                </label>
                @endforeach
            </div>
            @error('type') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Acte médical --}}
        <div class="flex items-center justify-between mb-3">
            <div>
                <span class="text-sm font-semibold text-slate-700">Acte médical</span>
                <p class="text-xs text-slate-400">Pas de posologie ni d'unité</p>
            </div>
            <button type="button"
                    wire:click="$toggle('isMedicalAct')"
                    class="relative inline-flex w-11 h-6 items-center rounded-full transition-colors focus:outline-none"
                    style="background-color: {{ $isMedicalAct ? '#0ea5e9' : '#94a3b8' }};">
                <span class="inline-block w-4 h-4 rounded-full bg-white shadow transition-transform"
                      style="transform: translateX({{ $isMedicalAct ? '1.5rem' : '0.25rem' }});"></span>
            </button>
        </div>

        {{-- À jeun --}}
        <div class="flex items-center justify-between mb-3">
            <div>
                <span class="text-sm font-semibold text-slate-700">À jeun</span>
                <p class="text-xs text-slate-400">Affiché en avertissement dans le calendrier</p>
            </div>
            <button type="button"
                    wire:click="$toggle('requiresFasting')"
                    class="relative inline-flex w-11 h-6 items-center rounded-full transition-colors focus:outline-none"
                    style="background-color: {{ $requiresFasting ? '#f59e0b' : '#94a3b8' }};">
                <span class="inline-block w-4 h-4 rounded-full bg-white shadow transition-transform"
                      style="transform: translateX({{ $requiresFasting ? '1.5rem' : '0.25rem' }});"></span>
            </button>
        </div>

        {{-- Unité (masquée si acte médical) --}}
        @if(!$isMedicalAct)
        <div class="mb-3">
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Unité *</label>
            <input type="text"
                   wire:model="unit"
                   placeholder="ex : mg, ml, cp"
                   class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-400">
            @error('unit') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Dose initiale --}}
        <div class="mb-4">
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Dose initiale</label>
            <input type="number"
                   wire:model="currentDose"
                   step="0.1"
                   min="0"
                   class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-400">
        </div>
        @endif

        {{-- Couleur --}}
        <div class="mb-4">
            <label class="block text-xs font-semibold text-slate-600 mb-2">Couleur</label>
            <div class="flex gap-2 flex-wrap">
                @foreach($colors as $c)
                <button type="button"
                        wire:click="$set('color', '{{ $c }}')"
                        class="w-8 h-8 rounded-full transition-all {{ $color === $c ? 'ring-2 ring-offset-2 ring-slate-400 scale-110' : 'hover:scale-110' }}"
                        style="background-color: {{ $c }};"></button>
                @endforeach
            </div>
        </div>

        {{-- Fréquence et date de début (seulement pour cyclic) --}}
        @if($type === 'cyclic')
        <div class="border-t border-slate-100 pt-4 mb-4">
            <p class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-3">Récurrence</p>

            <div class="mb-3">
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Date de début</label>
                <input type="date"
                       wire:model="recurrenceStart"
                       class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-400">
                @error('recurrenceStart') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Fréquence (semaines)</label>
                <div class="flex items-center gap-3">
                    <button type="button"
                            wire:click="$set('frequencyWeeks', max(1, $frequencyWeeks - 1))"
                            class="w-10 h-10 rounded-xl bg-slate-100 text-slate-600 text-xl font-light hover:bg-slate-200 transition-colors flex items-center justify-center">
                        −
                    </button>
                    <div class="flex-1 text-center">
                        <p class="text-lg font-extrabold text-slate-800">
                            {{ $frequencyWeeks === 1 ? 'Toutes les semaines' : 'Toutes les ' . $frequencyWeeks . ' semaines' }}
                        </p>
                    </div>
                    <button type="button"
                            wire:click="$set('frequencyWeeks', $frequencyWeeks + 1)"
                            class="w-10 h-10 rounded-xl bg-slate-100 text-slate-600 text-xl font-light hover:bg-slate-200 transition-colors flex items-center justify-center">
                        +
                    </button>
                </div>
                @error('frequencyWeeks') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
        </div>
        @endif

        <button wire:click="save"
                class="w-full py-3 rounded-xl text-sm font-bold text-white transition-colors hover:opacity-90"
                style="background: linear-gradient(135deg, #0ea5e9, #6366f1);">
            Créer le traitement
        </button>
    </div>

</div>
