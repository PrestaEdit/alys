<div class="p-4 max-w-lg mx-auto">

    {{-- Header --}}
    <div class="flex items-center gap-3 mb-5">
        <a href="{{ route('treatments') }}"
           class="w-8 h-8 rounded-xl bg-slate-100 flex items-center justify-center text-slate-500 hover:bg-slate-200 transition-colors text-lg">
            ‹
        </a>
        <div>
            <h1 class="text-base font-extrabold text-slate-900">{{ $treatment->name }} · {{ $treatment->commercial_name }}</h1>
            <p class="text-xs text-slate-400">
                Traitement {{ $treatment->type === 'daily' ? 'quotidien' : ($treatment->type === 'weekly' ? 'hebdomadaire' : 'cyclique') }}{{ $treatment->is_medical_act ? ' · acte médical' : '' }}
            </p>
        </div>
    </div>

    @if(session('success'))
    <div class="bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-2 mb-4">
        <p class="text-xs font-semibold text-emerald-700">{{ session('success') }}</p>
    </div>
    @endif

    {{-- Panel 1 : Informations --}}
    <div class="bg-white rounded-2xl p-5 shadow-sm mb-4">
        <p class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-4">Informations</p>

        {{-- Nom usuel --}}
        <div class="mb-3">
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Nom usuel *</label>
            <input type="text"
                   wire:model="editName"
                   class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-400">
            @error('editName') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Nom commercial --}}
        <div class="mb-3">
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Nom commercial</label>
            <input type="text"
                   wire:model="editCommercialName"
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
                              {{ $editType === $val ? 'border-sky-400 bg-sky-50 text-sky-700' : 'border-slate-200 text-slate-600 hover:border-slate-300' }}">
                    <input type="radio" wire:model.live="editType" value="{{ $val }}" class="hidden">
                    <span class="text-xs font-semibold">{{ $label }}</span>
                </label>
                @endforeach
            </div>
            @error('editType') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Acte médical --}}
        <div class="flex items-center justify-between mb-3">
            <div>
                <span class="text-sm font-semibold text-slate-700">Acte médical</span>
                <p class="text-xs text-slate-400">Pas de posologie ni d'unité</p>
            </div>
            <button type="button" wire:click="$toggle('editIsMedicalAct')"
                    style="position:relative;display:inline-block;width:44px;height:24px;border-radius:9999px;background-color:{{ $editIsMedicalAct ? '#0ea5e9' : '#94a3b8' }};border:none;cursor:pointer;flex-shrink:0;transition:background-color .2s;">
                <span style="position:absolute;top:4px;left:{{ $editIsMedicalAct ? '24px' : '4px' }};width:16px;height:16px;border-radius:9999px;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.25);transition:left .15s;"></span>
            </button>
        </div>

        {{-- À jeun --}}
        <div class="flex items-center justify-between mb-4">
            <div>
                <span class="text-sm font-semibold text-slate-700">À jeun</span>
                <p class="text-xs text-slate-400">Affiché en avertissement dans le calendrier</p>
            </div>
            <button type="button" wire:click="$toggle('editRequiresFasting')"
                    style="position:relative;display:inline-block;width:44px;height:24px;border-radius:9999px;background-color:{{ $editRequiresFasting ? '#f59e0b' : '#94a3b8' }};border:none;cursor:pointer;flex-shrink:0;transition:background-color .2s;">
                <span style="position:absolute;top:4px;left:{{ $editRequiresFasting ? '24px' : '4px' }};width:16px;height:16px;border-radius:9999px;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.25);transition:left .15s;"></span>
            </button>
        </div>

        {{-- Lié à un traitement --}}
        <div class="mb-4">
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Lié à un traitement</label>
            <select wire:model.live="editParentTreatmentId"
                    class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-400 bg-white">
                <option value="">— Aucun —</option>
                @foreach($otherTreatments as $t)
                <option value="{{ $t->id }}">{{ $t->name }}{{ $t->commercial_name ? ' · ' . $t->commercial_name : '' }}</option>
                @endforeach
            </select>
            @error('editParentTreatmentId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
        </div>

        @if($editParentTreatmentId)
        <div class="mb-4">
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Durée du traitement lié (jours)</label>
            <div class="flex items-center gap-3">
                <button wire:click="decrementLinkedDays"
                        class="w-10 h-10 rounded-xl bg-slate-100 text-slate-600 text-xl font-light hover:bg-slate-200 transition-colors flex items-center justify-center">
                    −
                </button>
                <div class="flex-1 text-center">
                    <p class="text-lg font-extrabold text-slate-800">
                        {{ $editLinkedDays }} {{ $editLinkedDays === 1 ? 'jour' : 'jours' }}
                    </p>
                </div>
                <button wire:click="incrementLinkedDays"
                        class="w-10 h-10 rounded-xl bg-slate-100 text-slate-600 text-xl font-light hover:bg-slate-200 transition-colors flex items-center justify-center">
                    +
                </button>
            </div>
        </div>
        @endif

        {{-- Unité --}}
        @if(!$editIsMedicalAct)
        <div class="mb-3">
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Unité</label>
            <input type="text"
                   wire:model="editUnit"
                   placeholder="ex : mg, ml, cp"
                   class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-400">
            @error('editUnit') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
        </div>
        @endif

        {{-- Couleur --}}
        <div class="mb-5">
            <label class="block text-xs font-semibold text-slate-600 mb-2">Couleur</label>
            <div class="flex gap-2 flex-wrap">
                @foreach($colors as $c)
                <button type="button"
                        wire:click="$set('editColor', '{{ $c }}')"
                        class="w-8 h-8 rounded-full transition-all {{ $editColor === $c ? 'ring-2 ring-offset-2 ring-slate-400 scale-110' : 'hover:scale-110' }}"
                        style="background-color: {{ $c }};"></button>
                @endforeach
            </div>
        </div>

        <button wire:click="saveInfo"
                class="w-full py-3 rounded-xl text-sm font-bold text-white transition-colors hover:opacity-90"
                style="background: linear-gradient(135deg, #0ea5e9, #6366f1);">
            Enregistrer les informations
        </button>
    </div>

    {{-- Panel 2 : Widget --}}
    <div class="bg-white rounded-2xl p-5 shadow-sm mb-4">
        <p class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-4">Widget accueil</p>

        <div class="flex items-center justify-between mb-4">
            <span class="text-sm font-semibold text-slate-700">Afficher en page d'accueil</span>
            <button type="button"
                    wire:click="$toggle('showWidget')"
                    style="position:relative;display:inline-block;width:44px;height:24px;border-radius:9999px;background-color:{{ $showWidget ? '#0ea5e9' : '#94a3b8' }};border:none;cursor:pointer;flex-shrink:0;transition:background-color .2s;">
                <span style="position:absolute;top:4px;left:{{ $showWidget ? '24px' : '4px' }};width:16px;height:16px;border-radius:9999px;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.25);transition:left .15s;"></span>
            </button>
        </div>

        <div class="mb-5" style="display: {{ $showWidget ? 'block' : 'none' }};"
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

        <button wire:click="saveWidget"
                class="w-full py-3 rounded-xl text-sm font-bold text-white transition-colors hover:opacity-90"
                style="background: linear-gradient(135deg, #0ea5e9, #6366f1);">
            Enregistrer le widget
        </button>
    </div>

    {{-- Panel 3 : Récurrence (cyclic uniquement) --}}
    @if($editType === 'cyclic')
    <div class="bg-white rounded-2xl p-5 shadow-sm mb-4">
        <p class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-4">Récurrence</p>

        <div class="mb-3">
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Date de début</label>
            <x-datepicker model="editRecurrenceStart" :value="$editRecurrenceStart" />
        </div>

        <div class="mb-5">
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

        <button wire:click="saveRecurrence"
                class="w-full py-3 rounded-xl text-sm font-bold text-white transition-colors hover:opacity-90"
                style="background: linear-gradient(135deg, #0ea5e9, #6366f1);">
            Enregistrer la récurrence
        </button>
    </div>
    @endif

    {{-- Panel 4 : Posologie actuelle --}}
    @if(!$editIsMedicalAct)
    <div class="bg-white rounded-2xl p-5 shadow-sm mb-4">
        <p class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-4">Posologie actuelle</p>

        {{-- Mode selector --}}
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
            {{-- Day-part posology: Matin / Midi / Soir --}}
            @foreach([
                ['label' => 'Matin',  'prop' => 'newDoseMorning', 'inc' => 'incrementMorning', 'dec' => 'decrementMorning', 'value' => $newDoseMorning],
                ['label' => 'Midi',   'prop' => 'newDoseNoon',    'inc' => 'incrementNoon',    'dec' => 'decrementNoon',    'value' => $newDoseNoon],
                ['label' => 'Soir',   'prop' => 'newDoseEvening', 'inc' => 'incrementEvening', 'dec' => 'decrementEvening', 'value' => $newDoseEvening],
            ] as $part)
            <div class="mb-4">
                <p class="text-xs font-semibold text-slate-500 mb-2">{{ $part['label'] }}</p>
                <div class="flex items-center gap-4">
                    <button wire:click="{{ $part['dec'] }}"
                            class="w-10 h-10 rounded-xl bg-slate-100 text-slate-600 text-xl font-light hover:bg-slate-200 transition-colors flex items-center justify-center">
                        −
                    </button>
                    <div class="flex-1 text-center">
                        <p class="text-3xl font-extrabold leading-none" style="color: {{ $treatment->color }};">
                            {{ number_format($part['value'] ?? 0, $treatment->unit === 'ml' ? 1 : 0, ',', '') }}
                        </p>
                        @if($treatment->unit)
                        <p class="text-xs text-slate-400 font-medium mt-1">{{ $treatment->unit }}</p>
                        @endif
                    </div>
                    <button wire:click="{{ $part['inc'] }}"
                            class="w-10 h-10 rounded-xl bg-slate-100 text-slate-600 text-xl font-light hover:bg-slate-200 transition-colors flex items-center justify-center">
                        +
                    </button>
                </div>
                <input type="number"
                       wire:model.live="{{ $part['prop'] }}"
                       step="{{ $treatment->unit === 'ml' ? '0.1' : '1' }}"
                       min="0"
                       class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-700 text-center focus:outline-none focus:ring-2 focus:ring-sky-400 mt-2">
            </div>
            @endforeach
        @else
            {{-- Single-dose posology --}}
            <div class="flex items-center gap-4 mb-4">
                <button wire:click="decrement"
                        class="w-10 h-10 rounded-xl bg-slate-100 text-slate-600 text-xl font-light hover:bg-slate-200 transition-colors flex items-center justify-center">
                    −
                </button>
                <div class="flex-1 text-center">
                    <p class="text-4xl font-extrabold leading-none" style="color: {{ $treatment->color }};">
                        {{ number_format($newDose ?? 0, $treatment->unit === 'ml' ? 1 : 0, ',', '') }}
                    </p>
                    <p class="text-sm text-slate-400 font-medium mt-1">{{ $treatment->unit }} / {{ $treatment->type === 'daily' ? 'jour' : ($treatment->type === 'weekly' ? 'mardi' : 'prise') }}</p>
                </div>
                <button wire:click="increment"
                        class="w-10 h-10 rounded-xl bg-slate-100 text-slate-600 text-xl font-light hover:bg-slate-200 transition-colors flex items-center justify-center">
                    +
                </button>
            </div>

            {{-- Direct input --}}
            <input type="number"
                   wire:model.live="newDose"
                   step="{{ $treatment->unit === 'ml' ? '0.1' : '1' }}"
                   min="0"
                   class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-700 text-center focus:outline-none focus:ring-2 focus:ring-sky-400 mb-3">
        @endif

        {{-- Note --}}
        <div class="flex items-center gap-2 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 mb-5 mt-2">
            <svg class="w-3 h-3 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
            <input type="text"
                   wire:model="note"
                   placeholder="Note optionnelle..."
                   class="flex-1 bg-transparent text-xs text-slate-600 focus:outline-none">
        </div>

        <button wire:click="save"
                class="w-full py-3 rounded-xl text-sm font-bold text-white transition-colors hover:opacity-90"
                style="background: linear-gradient(135deg, #0ea5e9, #6366f1);">
            Enregistrer la posologie
        </button>
    </div>
    @endif

    {{-- Historique --}}
    @if(!$editIsMedicalAct && $history->isNotEmpty())
    <div class="bg-white rounded-2xl p-5 shadow-sm mb-4">
        <p class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-4">Historique</p>

        <div class="relative pl-5">
            <div class="absolute left-[3px] top-2 bottom-2 w-0.5 bg-slate-200 rounded-full"></div>

            @foreach($history as $index => $entry)
            <div class="relative mb-4 last:mb-0">
                <div class="absolute -left-5 top-0.5 w-2.5 h-2.5 rounded-full border-2 border-white shadow
                            {{ $index === 0 ? 'bg-sky-500' : 'bg-slate-300' }}"></div>
                <div class="flex items-start justify-between">
                    <div>
                        @if($entry->dose_morning !== null || $entry->dose_noon !== null || $entry->dose_evening !== null)
                            @php
                                $parts = [];
                                $decimals = $treatment->unit === 'ml' ? 1 : 0;
                                if ($entry->dose_morning !== null) $parts[] = number_format($entry->dose_morning, $decimals, ',', '') . ($treatment->unit ? ' ' . $treatment->unit : '') . ' matin';
                                if ($entry->dose_noon    !== null) $parts[] = number_format($entry->dose_noon,    $decimals, ',', '') . ($treatment->unit ? ' ' . $treatment->unit : '') . ' midi';
                                if ($entry->dose_evening !== null) $parts[] = number_format($entry->dose_evening, $decimals, ',', '') . ($treatment->unit ? ' ' . $treatment->unit : '') . ' soir';
                            @endphp
                            <p class="text-sm font-bold {{ $index === 0 ? 'text-slate-800' : 'text-slate-500' }}">
                                {{ implode(' · ', $parts) }}
                            </p>
                        @else
                        <p class="text-sm font-bold {{ $index === 0 ? 'text-slate-800' : 'text-slate-500' }}">
                            {{ number_format($entry->dose ?? 0, $treatment->unit === 'ml' ? 1 : 0, ',', '') }} {{ $treatment->unit }} / {{ $treatment->type === 'daily' ? 'jour' : 'prise' }}
                        </p>
                        @endif
                        <p class="text-xs text-slate-400">
                            Depuis le {{ $entry->started_at->locale('fr')->isoFormat('D MMM YYYY') }}
                        </p>
                        @if($entry->note)
                        <p class="text-xs text-slate-500 italic mt-0.5">{{ $entry->note }}</p>
                        @endif
                    </div>
                    @if($index === 0)
                    <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-600 flex-shrink-0">Actuel</span>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Modale de confirmation recalcul --}}
    @if($showRecalculateModal)
    <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl p-6 max-w-sm w-full shadow-xl">
            <h2 class="text-base font-extrabold text-slate-900 mb-2">Recalculer les événements ?</h2>
            <p class="text-xs text-slate-500 mb-5">
                Les événements futurs seront supprimés et recalculés avec la nouvelle fréquence et date de début. Cette action est irréversible.
            </p>
            <div class="flex gap-3">
                <button wire:click="cancelRecalculate"
                        class="flex-1 py-2.5 rounded-xl text-sm font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 transition-colors">
                    Annuler
                </button>
                <button wire:click="confirmRecalculate"
                        class="flex-1 py-2.5 rounded-xl text-sm font-bold text-white transition-colors hover:opacity-90"
                        style="background: linear-gradient(135deg, #0ea5e9, #6366f1);">
                    Confirmer
                </button>
            </div>
        </div>
    </div>
    @endif

</div>
