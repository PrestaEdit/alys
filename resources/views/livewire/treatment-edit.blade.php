<div>

    {{-- Fond fixe qui couvre la safe-area au-dessus du sticky header --}}
    <div class="fixed top-0 left-0 right-0 bg-slate-50" style="height: var(--safe-top); z-index: 49;"></div>

    {{-- Header sticky --}}
    <div class="sticky bg-slate-50 border-b border-slate-100 px-4 py-3" style="top: var(--safe-top); z-index: 50;">
        <div class="max-w-lg mx-auto flex items-center gap-3">
            <a href="{{ route('treatments') }}"
               class="w-8 h-8 rounded-xl bg-slate-100 flex items-center justify-center text-slate-500 hover:bg-slate-200 transition-colors text-lg flex-shrink-0">
                ‹
            </a>
            <div class="min-w-0">
                <h1 class="text-base font-extrabold text-slate-900 truncate">{{ $treatment->name }}{{ $treatment->commercial_name ? ' · ' . $treatment->commercial_name : '' }}</h1>
                <p class="text-xs text-slate-400">
                    Traitement {{ $treatment->type === 'daily' ? 'quotidien' : ($treatment->type === 'weekly' ? 'hebdomadaire' : 'cyclique') }}{{ $treatment->is_medical_act ? ' · acte médical' : '' }}
                </p>
            </div>
        </div>
    </div>

    <div class="p-4 max-w-lg mx-auto">

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

    {{-- Panel : Notifications --}}
    <div class="bg-white rounded-2xl p-5 shadow-sm mb-4">
        <p class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-4">Notifications</p>

        <div class="flex items-center justify-between mb-5">
            <div>
                <span class="text-sm font-semibold text-slate-700">Activer les rappels</span>
                <p class="text-xs text-slate-400">Notification locale au moment de la prise</p>
            </div>
            <button type="button" wire:click="$toggle('notificationEnabled')"
                    style="position:relative;display:inline-block;width:44px;height:24px;border-radius:9999px;background-color:{{ $notificationEnabled ? '#0ea5e9' : '#94a3b8' }};border:none;cursor:pointer;flex-shrink:0;transition:background-color .2s;">
                <span style="position:absolute;top:4px;left:{{ $notificationEnabled ? '24px' : '4px' }};width:16px;height:16px;border-radius:9999px;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.25);transition:left .15s;"></span>
            </button>
        </div>

        @if($notificationEnabled)
        <div class="space-y-3 mb-5">
            @if($treatment->is_medical_act || $dosageMode === 'single' || $dosageMode === 'interval')
            <x-time-picker
                :label="$dosageMode === 'interval' ? 'Heure de la 1ère prise' : 'Heure du rappel'"
                property="notificationTimeMorning"
                :value="$notificationTimeMorning" />
            @error('notificationTimeMorning') <p class="text-xs text-red-500 mt-2">{{ $message }}</p> @enderror
            @if($dosageMode === 'interval' && $newTimesPerDay > 0)
            <p class="text-xs text-slate-400 text-center mt-2">
                Les {{ $newTimesPerDay }} rappels suivants se déclencheront automatiquement
                toutes les {{ round(24 / $newTimesPerDay) }}h.
            </p>
            @endif

            @elseif($dosageMode === 'dayparts')
            @if(($newDoseMorning ?? 0) > 0)
            <x-time-picker label="Matin" property="notificationTimeMorning" :value="$notificationTimeMorning" />
            @endif
            @if(($newDoseNoon ?? 0) > 0)
            <x-time-picker label="Midi" property="notificationTimeNoon" :value="$notificationTimeNoon" />
            @endif
            @if(($newDoseEvening ?? 0) > 0)
            <x-time-picker label="Soir" property="notificationTimeEvening" :value="$notificationTimeEvening" />
            @endif
            @error('notificationTimeMorning') <p class="text-xs text-red-500 mt-2">{{ $message }}</p> @enderror
            @endif

            @if($treatment->type === 'weekly' || $treatment->type === 'cyclic')
            <div class="px-3 py-2 bg-amber-50 rounded-xl">
                <p class="text-xs text-amber-700">
                    La notification se déclenchera uniquement les jours planifiés dans votre calendrier.
                </p>
            </div>
            @endif
        </div>
        @endif

        <button wire:click="saveNotification"
                class="w-full py-2.5 rounded-xl text-sm font-semibold text-white transition-colors"
                style="background: linear-gradient(135deg, #0ea5e9, #6366f1);">
            Enregistrer
        </button>
    </div>

    {{-- Panel 3 : Planification (weekly) ou Récurrence (cyclic) --}}
    @if($editType === 'weekly' || $editType === 'cyclic')
    <div class="bg-white rounded-2xl p-5 shadow-sm mb-4">
        <p class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-4">
            {{ $editType === 'weekly' ? 'Planification' : 'Récurrence' }}
        </p>

        @if($editType === 'weekly')
            {{-- Jour de la semaine --}}
            <div class="mb-4">
                <label class="block text-xs font-semibold text-slate-600 mb-2">Jour de la semaine</label>
                @php $dayNames = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim']; @endphp
                <div class="grid grid-cols-7 gap-1">
                    @foreach($dayNames as $i => $dayName)
                    <button wire:click="$set('editDayOfWeek', {{ $i }})"
                            class="py-2 rounded-xl text-xs font-bold transition-colors
                                   {{ $editDayOfWeek === $i ? 'text-white' : 'bg-slate-100 text-slate-500 hover:bg-slate-200' }}"
                            style="{{ $editDayOfWeek === $i ? 'background: linear-gradient(135deg, #0ea5e9, #6366f1);' : '' }}">
                        {{ $dayName }}
                    </button>
                    @endforeach
                </div>
            </div>

            {{-- Fréquence --}}
            <div class="mb-4">
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Fréquence</label>
                <div class="flex items-center gap-3">
                    <button wire:click="decrementFrequency"
                            class="w-10 h-10 rounded-xl bg-slate-100 text-slate-600 text-xl font-light hover:bg-slate-200 transition-colors flex items-center justify-center">
                        −
                    </button>
                    <div class="flex-1 text-center">
                        <p class="text-lg font-extrabold text-slate-800">
                            {{ $frequencyWeeks === 1 ? 'Toutes les semaines' : 'Une semaine sur ' . $frequencyWeeks }}
                        </p>
                    </div>
                    <button wire:click="incrementFrequency"
                            class="w-10 h-10 rounded-xl bg-slate-100 text-slate-600 text-xl font-light hover:bg-slate-200 transition-colors flex items-center justify-center">
                        +
                    </button>
                </div>
                @error('frequencyWeeks') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Date de référence --}}
            <div class="mb-5">
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Date de la première prise (optionnel)</label>
                <x-datepicker model="editRecurrenceStart" :value="$editRecurrenceStart" />
            </div>
        @else
            {{-- Cyclique : date + fréquence --}}
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
        @endif

        <button wire:click="saveRecurrence"
                class="w-full py-3 rounded-xl text-sm font-bold text-white transition-colors hover:opacity-90"
                style="background: linear-gradient(135deg, #0ea5e9, #6366f1);">
            Enregistrer la planification
        </button>
    </div>
    @endif

    {{-- Panel 4 : Posologie actuelle --}}
    @if(!$editIsMedicalAct)
    <div class="bg-white rounded-2xl p-5 shadow-sm mb-4">
        <p class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-4">Posologie actuelle</p>

        {{-- Mode selector --}}
        <div class="flex flex-col gap-2 mb-5">
            @foreach([['single', 'Dose unique'], ['dayparts', 'Matin / Midi / Soir'], ['interval', 'Intervalle régulier']] as [$val, $lbl])
            <label class="flex items-center px-4 py-3 rounded-xl border cursor-pointer transition-colors
                          {{ $dosageMode === $val ? 'border-sky-400 bg-sky-50 text-sky-700' : 'border-slate-200 text-slate-600 hover:border-slate-300' }}">
                <input type="radio" wire:model.live="dosageMode" value="{{ $val }}" class="hidden">
                <span class="text-sm font-semibold">{{ $lbl }}</span>
                @if($dosageMode === $val)
                <svg class="ml-auto w-4 h-4 text-sky-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                @endif
            </label>
            @endforeach
        </div>

        @if($dosageMode === 'dayparts')
            {{-- Day-part posology: Matin / Midi / Soir --}}
            @foreach([
                ['label' => 'Matin',  'inc' => 'incrementMorning', 'dec' => 'decrementMorning', 'value' => $newDoseMorning],
                ['label' => 'Midi',   'inc' => 'incrementNoon',    'dec' => 'decrementNoon',    'value' => $newDoseNoon],
                ['label' => 'Soir',   'inc' => 'incrementEvening', 'dec' => 'decrementEvening', 'value' => $newDoseEvening],
            ] as $part)
            <div class="mb-4">
                <p class="text-xs font-semibold text-slate-500 mb-2">{{ $part['label'] }}</p>
                <div class="flex flex-row items-center gap-4">
                    <button wire:click="{{ $part['dec'] }}"
                            class="w-10 h-10 rounded-xl bg-slate-100 text-slate-600 text-xl font-light hover:bg-slate-200 transition-colors flex items-center justify-center">
                        −
                    </button>
                    <div class="flex-1 text-center">
                        @php $epv = (float)($part['value'] ?? 0); $epdec = $treatment->unit === 'ml' ? 1 : ($epv != (int)$epv ? 1 : 0); @endphp
                        <p class="text-3xl font-extrabold leading-none" style="color: {{ $treatment->color }};">
                            {{ number_format($epv, $epdec, ',', '') }}
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
            </div>
            @endforeach
        @elseif($dosageMode === 'interval')
            {{-- Dose par prise --}}
            <p class="text-xs font-semibold text-slate-500 mb-2">Dose par prise</p>
            <div class="flex flex-row items-center gap-4 mb-5">
                <button wire:click="decrement"
                        class="w-10 h-10 rounded-xl bg-slate-100 text-slate-600 text-xl font-light hover:bg-slate-200 transition-colors flex items-center justify-center">
                    −
                </button>
                <div class="flex-1 text-center">
                    @php $eid = (float)($newDose ?? 0); $eidec = $treatment->unit === 'ml' ? 1 : ($eid != (int)$eid ? 1 : 0); @endphp
                    <p class="text-4xl font-extrabold leading-none" style="color: {{ $treatment->color }};">
                        {{ number_format($eid, $eidec, ',', '') }}
                    </p>
                    @if($treatment->unit)
                    <p class="text-sm text-slate-400 font-medium mt-1">{{ $treatment->unit }} / prise</p>
                    @endif
                </div>
                <button wire:click="increment"
                        class="w-10 h-10 rounded-xl bg-slate-100 text-slate-600 text-xl font-light hover:bg-slate-200 transition-colors flex items-center justify-center">
                    +
                </button>
            </div>
            {{-- Nombre de prises --}}
            <p class="text-xs font-semibold text-slate-500 mb-2">Nombre de prises par jour</p>
            <div class="flex flex-row items-center gap-4 mb-2">
                <button wire:click="decrementTimesPerDay"
                        class="w-10 h-10 rounded-xl bg-slate-100 text-slate-600 text-xl font-light hover:bg-slate-200 transition-colors flex items-center justify-center">
                    −
                </button>
                <div class="flex-1 text-center">
                    @php $intervalH = $newTimesPerDay > 0 ? round(24 / $newTimesPerDay) : 0; @endphp
                    <p class="text-4xl font-extrabold leading-none" style="color: {{ $treatment->color }};">{{ $newTimesPerDay }}</p>
                    <p class="text-sm text-slate-400 font-medium mt-1">× / jour · toutes les {{ $intervalH }}h</p>
                </div>
                <button wire:click="incrementTimesPerDay"
                        class="w-10 h-10 rounded-xl bg-slate-100 text-slate-600 text-xl font-light hover:bg-slate-200 transition-colors flex items-center justify-center">
                    +
                </button>
            </div>
        @else
            {{-- Single-dose posology --}}
            <div class="flex flex-row items-center gap-4 mb-4">
                <button wire:click="decrement"
                        class="w-10 h-10 rounded-xl bg-slate-100 text-slate-600 text-xl font-light hover:bg-slate-200 transition-colors flex items-center justify-center">
                    −
                </button>
                <div class="flex-1 text-center">
                    @php $esd = (float)($newDose ?? 0); $esdec = $treatment->unit === 'ml' ? 1 : ($esd != (int)$esd ? 1 : 0); @endphp
                    <p class="text-4xl font-extrabold leading-none" style="color: {{ $treatment->color }};">
                        {{ number_format($esd, $esdec, ',', '') }}
                    </p>
                    <p class="text-sm text-slate-400 font-medium mt-1">{{ $treatment->unit }} / {{ $treatment->type === 'daily' ? 'jour' : ($treatment->type === 'weekly' ? $treatment->dayOfWeekName() : 'prise') }}</p>
                </div>
                <button wire:click="increment"
                        class="w-10 h-10 rounded-xl bg-slate-100 text-slate-600 text-xl font-light hover:bg-slate-200 transition-colors flex items-center justify-center">
                    +
                </button>
            </div>
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
                            <div class="{{ $index === 0 ? 'text-slate-800' : 'text-slate-500' }}">
                                @if($entry->dose_morning !== null)
                                @php $hm = (float)$entry->dose_morning; $hmdec = $treatment->unit === 'ml' ? 1 : ($hm != (int)$hm ? 1 : 0); @endphp
                                <p class="text-xs font-semibold">Matin · {{ number_format($hm, $hmdec, ',', '') }}{{ $treatment->unit ? ' ' . $treatment->unit : '' }}</p>
                                @endif
                                @if($entry->dose_noon !== null)
                                @php $hn = (float)$entry->dose_noon; $hndec = $treatment->unit === 'ml' ? 1 : ($hn != (int)$hn ? 1 : 0); @endphp
                                <p class="text-xs font-semibold">Midi · {{ number_format($hn, $hndec, ',', '') }}{{ $treatment->unit ? ' ' . $treatment->unit : '' }}</p>
                                @endif
                                @if($entry->dose_evening !== null)
                                @php $hev = (float)$entry->dose_evening; $hevdec = $treatment->unit === 'ml' ? 1 : ($hev != (int)$hev ? 1 : 0); @endphp
                                <p class="text-xs font-semibold">Soir · {{ number_format($hev, $hevdec, ',', '') }}{{ $treatment->unit ? ' ' . $treatment->unit : '' }}</p>
                                @endif
                            </div>
                        @elseif($entry->times_per_day)
                            @php $intervalH = $entry->times_per_day > 0 ? round(24 / $entry->times_per_day) : 0;
                                 $hid = (float)($entry->dose ?? 0); $hiddec = $treatment->unit === 'ml' ? 1 : ($hid != (int)$hid ? 1 : 0); @endphp
                            <p class="text-sm font-bold {{ $index === 0 ? 'text-slate-800' : 'text-slate-500' }}">
                                {{ number_format($hid, $hiddec, ',', '') }} {{ $treatment->unit }} / prise
                            </p>
                            <p class="text-xs {{ $index === 0 ? 'text-slate-500' : 'text-slate-400' }}">{{ $entry->times_per_day }}×/jour · toutes les {{ $intervalH }}h</p>
                        @else
                        @php $hsd = (float)($entry->dose ?? 0); $hsddec = $treatment->unit === 'ml' ? 1 : ($hsd != (int)$hsd ? 1 : 0); @endphp
                        <p class="text-sm font-bold {{ $index === 0 ? 'text-slate-800' : 'text-slate-500' }}">
                            {{ number_format($hsd, $hsddec, ',', '') }} {{ $treatment->unit }} / {{ $treatment->type === 'daily' ? 'jour' : 'prise' }}
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

    </div>{{-- /p-4 max-w-lg --}}
</div>
