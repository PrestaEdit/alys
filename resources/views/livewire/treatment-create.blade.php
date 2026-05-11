<div class="p-4 max-w-lg mx-auto">

    {{-- Header --}}
    <div class="flex items-center gap-3 mb-5">
        @if($step === 1)
        <a href="{{ route('treatments') }}"
           class="w-8 h-8 rounded-xl bg-slate-100 flex items-center justify-center text-slate-500 hover:bg-slate-200 transition-colors text-lg flex-shrink-0">
            ‹
        </a>
        @else
        <button wire:click="prevStep"
                class="w-8 h-8 rounded-xl bg-slate-100 flex items-center justify-center text-slate-500 hover:bg-slate-200 transition-colors text-lg flex-shrink-0">
            ‹
        </button>
        @endif
        <div>
            <h1 class="text-base font-extrabold text-slate-900">Nouveau traitement</h1>
            <p class="text-xs text-slate-400">Étape {{ $step }} sur 5</p>
        </div>
    </div>

    {{-- Dots de progression --}}
    @php
        $currentIdx = array_search($step, $applicableSteps);
    @endphp
    <div class="flex gap-1.5 justify-center mb-6">
        @for ($i = 1; $i <= 5; $i++)
        @php
            $isActive  = $i === $step;
            $stepIdx   = array_search($i, $applicableSteps);
            $isDone    = $stepIdx !== false && $stepIdx < $currentIdx;
            $isNA      = $stepIdx === false;
            $bgColor   = $isActive ? '#0ea5e9' : ($isDone ? '#10b981' : '#e2e8f0');
        @endphp
        <div style="
            width: {{ $isActive ? '24px' : '7px' }};
            height: 7px;
            border-radius: 9999px;
            background: {{ $bgColor }};
            opacity: {{ $isNA ? '0.35' : '1' }};
            transition: width 0.2s;
        "></div>
        @endfor
    </div>

    @if(session('success'))
    <div class="bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-2 mb-4">
        <p class="text-xs font-semibold text-emerald-700">{{ session('success') }}</p>
    </div>
    @endif

    {{-- ── Étape 1 : Informations de base ──────────────────────────────── --}}
    @if($step === 1)
    <div class="bg-white rounded-2xl p-5 shadow-sm mb-4">
        <p class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-4">{{ $stepLabel }}</p>

        <div class="mb-3">
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Nom usuel *</label>
            <input type="text"
                   wire:model="name"
                   placeholder="ex : Méthotrexate"
                   class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-400">
            @error('name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="mb-3">
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Nom commercial</label>
            <input type="text"
                   wire:model="commercialName"
                   placeholder="ex : Novatrex"
                   class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-400">
        </div>

        <div class="mb-4">
            <label class="block text-xs font-semibold text-slate-600 mb-2">Type *</label>
            <div class="grid grid-cols-3 gap-2">
                @foreach([
                    ['daily', 'Quotidien'],
                    ['weekly', 'Hebdomadaire'],
                    ['cyclic', 'Cyclique'],
                ] as [$val, $label])
                <label class="flex items-center justify-center px-2 py-2.5 rounded-xl border cursor-pointer transition-colors
                              {{ $type === $val ? 'border-sky-400 bg-sky-50 text-sky-700' : 'border-slate-200 text-slate-600 hover:border-slate-300' }}">
                    <input type="radio" wire:model.live="type" value="{{ $val }}" class="hidden">
                    <span class="text-xs font-semibold text-center">{{ $label }}</span>
                </label>
                @endforeach
            </div>
            @error('type') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center justify-between mb-3">
            <div>
                <span class="text-sm font-semibold text-slate-700">Acte médical</span>
                <p class="text-xs text-slate-400">Pas de posologie ni d'unité</p>
            </div>
            <button type="button" wire:click="$toggle('isMedicalAct')"
                    style="position:relative;display:inline-block;width:44px;height:24px;border-radius:9999px;background-color:{{ $isMedicalAct ? '#0ea5e9' : '#94a3b8' }};border:none;cursor:pointer;flex-shrink:0;transition:background-color .2s;">
                <span style="position:absolute;top:4px;left:{{ $isMedicalAct ? '24px' : '4px' }};width:16px;height:16px;border-radius:9999px;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.25);transition:left .15s;"></span>
            </button>
        </div>

        <div class="flex items-center justify-between mb-4">
            <div>
                <span class="text-sm font-semibold text-slate-700">À jeun</span>
                <p class="text-xs text-slate-400">Affiché en avertissement dans le calendrier</p>
            </div>
            <button type="button" wire:click="$toggle('requiresFasting')"
                    style="position:relative;display:inline-block;width:44px;height:24px;border-radius:9999px;background-color:{{ $requiresFasting ? '#f59e0b' : '#94a3b8' }};border:none;cursor:pointer;flex-shrink:0;transition:background-color .2s;">
                <span style="position:absolute;top:4px;left:{{ $requiresFasting ? '24px' : '4px' }};width:16px;height:16px;border-radius:9999px;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.25);transition:left .15s;"></span>
            </button>
        </div>

        <div class="mb-4">
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Couleur</label>
            <div class="flex gap-2 flex-wrap">
                @foreach($colors as $c)
                <button type="button"
                        wire:click="$set('color', '{{ $c }}')"
                        class="w-8 h-8 rounded-full transition-all {{ $color === $c ? 'ring-2 ring-offset-2 ring-slate-400 scale-110' : 'hover:scale-110' }}"
                        style="background-color: {{ $c }};"></button>
                @endforeach
            </div>
            @error('color') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="mb-2">
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Lié à un traitement</label>
            <select wire:model.live="parentTreatmentId"
                    class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-400 bg-white">
                <option value="">— Aucun —</option>
                @foreach($otherTreatments as $t)
                <option value="{{ $t->id }}">{{ $t->name }}{{ $t->commercial_name ? ' · ' . $t->commercial_name : '' }}</option>
                @endforeach
            </select>
            @error('parentTreatmentId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
        </div>

        @if($parentTreatmentId)
        <div class="mt-3">
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Durée du traitement lié (jours)</label>
            <div class="flex items-center gap-3">
                <button wire:click="decrementLinkedDays"
                        class="w-10 h-10 rounded-xl bg-slate-100 text-slate-600 text-xl font-light hover:bg-slate-200 transition-colors flex items-center justify-center">
                    −
                </button>
                <div class="flex-1 text-center">
                    <p class="text-lg font-extrabold text-slate-800">
                        {{ $linkedDays }} {{ $linkedDays === 1 ? 'jour' : 'jours' }}
                    </p>
                </div>
                <button wire:click="incrementLinkedDays"
                        class="w-10 h-10 rounded-xl bg-slate-100 text-slate-600 text-xl font-light hover:bg-slate-200 transition-colors flex items-center justify-center">
                    +
                </button>
            </div>
        </div>
        @endif
    </div>

    <button wire:click="nextStep"
            class="w-full py-3 rounded-xl text-sm font-bold text-white transition-colors hover:opacity-90"
            style="background: linear-gradient(135deg, #0ea5e9, #6366f1);">
        Suivant →
    </button>
    @endif

    {{-- ── Étape 2 : Widget accueil ─────────────────────────────────────── --}}
    @if($step === 2)
    <div class="bg-white rounded-2xl p-5 shadow-sm mb-4">
        <p class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-4">{{ $stepLabel }}</p>

        <div class="flex items-center justify-between mb-4">
            <span class="text-sm font-semibold text-slate-700">Afficher en page d'accueil</span>
            <button type="button" wire:click="$toggle('showWidget')"
                    style="position:relative;display:inline-block;width:44px;height:24px;border-radius:9999px;background-color:{{ $showWidget ? '#0ea5e9' : '#94a3b8' }};border:none;cursor:pointer;flex-shrink:0;transition:background-color .2s;">
                <span style="position:absolute;top:4px;left:{{ $showWidget ? '24px' : '4px' }};width:16px;height:16px;border-radius:9999px;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.25);transition:left .15s;"></span>
            </button>
        </div>

        <div style="display: {{ $showWidget ? 'block' : 'none' }};"
             wire:key="widget-icon-picker-{{ $showWidget ? '1' : '0' }}">
            <label class="block text-xs font-semibold text-slate-600 mb-2">Icône du widget</label>
            <div class="flex gap-2 flex-wrap">
                @foreach($widgetIcons as $icon)
                <button type="button"
                        wire:click="$set('widgetIcon', '{{ $icon }}')"
                        class="w-10 h-10 rounded-xl text-xl flex items-center justify-center transition-all
                               {{ $widgetIcon === $icon ? 'bg-sky-100 ring-2 ring-sky-400' : 'bg-slate-100 hover:bg-slate-200' }}">
                    {{ $icon }}
                </button>
                @endforeach
            </div>
        </div>
    </div>

    <div class="flex gap-3">
        <button wire:click="prevStep"
                class="flex-1 py-3 rounded-xl text-sm font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 transition-colors">
            Précédent
        </button>
        <button wire:click="nextStep"
                class="flex-[2] py-3 rounded-xl text-sm font-bold text-white transition-colors hover:opacity-90"
                style="background: linear-gradient(135deg, #0ea5e9, #6366f1);">
            Suivant →
        </button>
    </div>
    @endif

    {{-- ── Étape 3 : Posologie ─────────────────────────────────────────── --}}
    @if($step === 3)
    <div class="bg-white rounded-2xl p-5 shadow-sm mb-4">
        <p class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-4">{{ $stepLabel }}</p>

        <div class="mb-4">
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Unité</label>
            <input type="text"
                   wire:model="unit"
                   placeholder="ex : mg, ml, cachet"
                   class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-400">
        </div>

        <div class="grid grid-cols-2 gap-2 mb-5">
            @foreach([['single', 'Dose unique'], ['dayparts', 'Matin / Midi / Soir']] as [$val, $lbl])
            <label class="flex items-center gap-2 px-3 py-2.5 rounded-xl border cursor-pointer transition-colors
                          {{ $dosageMode === $val ? 'border-sky-400 bg-sky-50 text-sky-700' : 'border-slate-200 text-slate-600 hover:border-slate-300' }}">
                <input type="radio" wire:model.live="dosageMode" value="{{ $val }}" class="hidden">
                <span class="text-xs font-semibold">{{ $lbl }}</span>
            </label>
            @endforeach
        </div>

        @if($dosageMode === 'dayparts')
            @foreach([
                ['label' => 'Matin', 'prop' => 'doseMorning', 'inc' => 'incrementMorning', 'dec' => 'decrementMorning', 'value' => $doseMorning],
                ['label' => 'Midi',  'prop' => 'doseNoon',    'inc' => 'incrementNoon',    'dec' => 'decrementNoon',    'value' => $doseNoon],
                ['label' => 'Soir',  'prop' => 'doseEvening', 'inc' => 'incrementEvening', 'dec' => 'decrementEvening', 'value' => $doseEvening],
            ] as $part)
            <div class="mb-4">
                <p class="text-xs font-semibold text-slate-500 mb-2">{{ $part['label'] }}</p>
                <div class="flex items-center gap-4">
                    <button wire:click="{{ $part['dec'] }}"
                            class="w-10 h-10 rounded-xl bg-slate-100 text-slate-600 text-xl font-light hover:bg-slate-200 transition-colors flex items-center justify-center">
                        −
                    </button>
                    <div class="flex-1 text-center">
                        <p class="text-3xl font-extrabold leading-none" style="color: {{ $color }};">
                            {{ number_format($part['value'] ?? 0, $unit === 'ml' ? 1 : 0, ',', '') }}
                        </p>
                        @if($unit)
                        <p class="text-xs text-slate-400 font-medium mt-1">{{ $unit }}</p>
                        @endif
                    </div>
                    <button wire:click="{{ $part['inc'] }}"
                            class="w-10 h-10 rounded-xl bg-slate-100 text-slate-600 text-xl font-light hover:bg-slate-200 transition-colors flex items-center justify-center">
                        +
                    </button>
                </div>
                <input type="number"
                       wire:model.live="{{ $part['prop'] }}"
                       step="{{ $unit === 'ml' ? '0.1' : '1' }}"
                       min="0"
                       class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-700 text-center focus:outline-none focus:ring-2 focus:ring-sky-400 mt-2">
            </div>
            @endforeach
        @else
            <div class="flex items-center gap-4 mb-4">
                <button wire:click="decrement"
                        class="w-10 h-10 rounded-xl bg-slate-100 text-slate-600 text-xl font-light hover:bg-slate-200 transition-colors flex items-center justify-center">
                    −
                </button>
                <div class="flex-1 text-center">
                    <p class="text-4xl font-extrabold leading-none" style="color: {{ $color }};">
                        {{ number_format($currentDose, $unit === 'ml' ? 1 : 0, ',', '') }}
                    </p>
                    <p class="text-sm text-slate-400 font-medium mt-1">
                        {{ $unit ?: '—' }} / {{ $type === 'daily' ? 'jour' : ($type === 'weekly' ? 'semaine' : 'prise') }}
                    </p>
                </div>
                <button wire:click="increment"
                        class="w-10 h-10 rounded-xl bg-slate-100 text-slate-600 text-xl font-light hover:bg-slate-200 transition-colors flex items-center justify-center">
                    +
                </button>
            </div>
            <input type="number"
                   wire:model.live="currentDose"
                   step="{{ $unit === 'ml' ? '0.1' : '1' }}"
                   min="0"
                   class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-700 text-center focus:outline-none focus:ring-2 focus:ring-sky-400">
        @endif
    </div>

    <div class="flex gap-3">
        <button wire:click="prevStep"
                class="flex-1 py-3 rounded-xl text-sm font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 transition-colors">
            Précédent
        </button>
        <button wire:click="nextStep"
                class="flex-[2] py-3 rounded-xl text-sm font-bold text-white transition-colors hover:opacity-90"
                style="background: linear-gradient(135deg, #0ea5e9, #6366f1);">
            Suivant →
        </button>
    </div>
    @endif

    {{-- ── Étape 4 : Récurrence ────────────────────────────────────────── --}}
    @if($step === 4)
    <div class="bg-white rounded-2xl p-5 shadow-sm mb-4">
        <p class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-4">{{ $stepLabel }}</p>

        <div class="mb-3">
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Date de début</label>
            <x-datepicker model="recurrenceStart" :value="$recurrenceStart" />
            @error('recurrenceStart') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="mb-2">
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Fréquence (semaines)</label>
            <div class="flex items-center gap-3">
                <button wire:click="decrementFrequency"
                        class="w-10 h-10 rounded-xl bg-slate-100 text-slate-600 text-xl font-light hover:bg-slate-200 transition-colors flex items-center justify-center">
                    −
                </button>
                <div class="flex-1 text-center">
                    <p class="text-lg font-extrabold text-slate-800">
                        {{ $frequencyWeeks === 1 ? 'Toutes les semaines' : 'Toutes les ' . $frequencyWeeks . ' semaines' }}
                    </p>
                </div>
                <button wire:click="incrementFrequency"
                        class="w-10 h-10 rounded-xl bg-slate-100 text-slate-600 text-xl font-light hover:bg-slate-200 transition-colors flex items-center justify-center">
                    +
                </button>
            </div>
            @error('frequencyWeeks') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="flex gap-3">
        <button wire:click="prevStep"
                class="flex-1 py-3 rounded-xl text-sm font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 transition-colors">
            Précédent
        </button>
        <button wire:click="nextStep"
                class="flex-[2] py-3 rounded-xl text-sm font-bold text-white transition-colors hover:opacity-90"
                style="background: linear-gradient(135deg, #0ea5e9, #6366f1);">
            Suivant →
        </button>
    </div>
    @endif

    {{-- ── Étape 5 : Récapitulatif ─────────────────────────────────────── --}}
    @if($step === 5)
    <div class="bg-white rounded-2xl p-5 shadow-sm mb-4">
        <p class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-4">{{ $stepLabel }}</p>

        {{-- Nom --}}
        <div class="flex justify-between items-start py-3 border-b border-slate-100">
            <span class="text-xs text-slate-400 font-medium">Nom</span>
            <div class="text-right">
                <span class="text-sm font-bold text-slate-800">{{ $name }}</span>
                @if($commercialName)
                <p class="text-xs text-slate-400">{{ $commercialName }}</p>
                @endif
            </div>
        </div>

        {{-- Type --}}
        <div class="flex justify-between items-center py-3 border-b border-slate-100">
            <span class="text-xs text-slate-400 font-medium">Type</span>
            <span class="text-sm font-bold text-slate-800">
                {{ ['daily' => 'Quotidien', 'weekly' => 'Hebdomadaire', 'cyclic' => 'Cyclique'][$type] ?? $type }}
            </span>
        </div>

        {{-- Couleur --}}
        <div class="flex justify-between items-center py-3 border-b border-slate-100">
            <span class="text-xs text-slate-400 font-medium">Couleur</span>
            <div class="w-5 h-5 rounded-full" style="background: {{ $color }};"></div>
        </div>

        {{-- Badges acte médical / à jeun --}}
        @if($isMedicalAct || $requiresFasting)
        <div class="flex gap-2 py-3 border-b border-slate-100">
            @if($isMedicalAct)
            <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-sky-100 text-sky-700">Acte médical</span>
            @endif
            @if($requiresFasting)
            <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-700">À jeun</span>
            @endif
        </div>
        @endif

        {{-- Traitement lié --}}
        @if($parentTreatmentId)
        @php $linked = $otherTreatments->firstWhere('id', $parentTreatmentId); @endphp
        <div class="flex justify-between items-center py-3 border-b border-slate-100">
            <span class="text-xs text-slate-400 font-medium">Lié à</span>
            <span class="text-sm font-bold text-slate-800">
                {{ $linked?->name ?? '—' }}
                ({{ $linkedDays }} {{ $linkedDays === 1 ? 'jour' : 'jours' }})
            </span>
        </div>
        @endif

        {{-- Widget --}}
        @if($showWidget)
        <div class="flex justify-between items-center py-3 border-b border-slate-100">
            <span class="text-xs text-slate-400 font-medium">Widget</span>
            <span class="text-lg">{{ $widgetIcon }}</span>
        </div>
        @endif

        {{-- Posologie --}}
        @if(!$isMedicalAct)
        <div class="flex justify-between items-start py-3 border-b border-slate-100">
            <span class="text-xs text-slate-400 font-medium">Posologie</span>
            <div class="text-right">
                @if($dosageMode === 'dayparts')
                    <p class="text-xs text-slate-600">Matin : <strong>{{ number_format($doseMorning ?? 0, $unit === 'ml' ? 1 : 0, ',', '') }} {{ $unit }}</strong></p>
                    <p class="text-xs text-slate-600">Midi : <strong>{{ number_format($doseNoon ?? 0, $unit === 'ml' ? 1 : 0, ',', '') }} {{ $unit }}</strong></p>
                    <p class="text-xs text-slate-600">Soir : <strong>{{ number_format($doseEvening ?? 0, $unit === 'ml' ? 1 : 0, ',', '') }} {{ $unit }}</strong></p>
                @else
                    <span class="text-sm font-bold text-slate-800">
                        {{ number_format($currentDose, $unit === 'ml' ? 1 : 0, ',', '') }} {{ $unit }}
                        / {{ ['daily' => 'jour', 'weekly' => 'semaine', 'cyclic' => 'prise'][$type] ?? '' }}
                    </span>
                @endif
            </div>
        </div>
        @endif

        {{-- Récurrence --}}
        @if($type === 'cyclic')
        <div class="flex justify-between items-center py-3">
            <span class="text-xs text-slate-400 font-medium">Récurrence</span>
            <div class="text-right">
                <span class="text-sm font-bold text-slate-800">
                    {{ $frequencyWeeks === 1 ? 'Chaque semaine' : 'Toutes les ' . $frequencyWeeks . ' sem.' }}
                </span>
                @if($recurrenceStart)
                <p class="text-xs text-slate-400">Début : {{ \Carbon\Carbon::parse($recurrenceStart)->format('d/m/Y') }}</p>
                @endif
            </div>
        </div>
        @endif
    </div>

    <div class="flex gap-3">
        <button wire:click="prevStep"
                class="flex-1 py-3 rounded-xl text-sm font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 transition-colors">
            Précédent
        </button>
        <button wire:click="save"
                class="flex-[2] py-3 rounded-xl text-sm font-bold text-white transition-colors hover:opacity-90"
                style="background: linear-gradient(135deg, #0ea5e9, #6366f1);">
            Créer le traitement
        </button>
    </div>
    @endif

</div>
