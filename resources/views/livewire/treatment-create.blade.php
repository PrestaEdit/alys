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
            <h1 class="text-base font-extrabold text-slate-900">{{ __('treatments.title_create') }}</h1>
            <p class="text-xs text-slate-400">{{ __('treatments.step_of', ['current' => array_search($step, $applicableSteps) + 1, 'total' => count($applicableSteps)]) }}</p>
        </div>
    </div>

    {{-- Dots de progression --}}
    @php
        $currentIdx = array_search($step, $applicableSteps);
    @endphp
    <div class="flex gap-1.5 justify-center mb-6">
        @foreach($applicableSteps as $dotStep)
        @php
            $isActive = $dotStep === $step;
            $dotIdx   = array_search($dotStep, $applicableSteps);
            $isDone   = $dotIdx !== false && $dotIdx < $currentIdx;
            $bgColor  = $isActive ? '#0ea5e9' : ($isDone ? '#10b981' : '#e2e8f0');
        @endphp
        <div style="
            width: {{ $isActive ? '24px' : '7px' }};
            height: 7px;
            border-radius: 9999px;
            background: {{ $bgColor }};
            transition: width 0.2s;
        "></div>
        @endforeach
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
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">{{ __('treatments.common_name') }} *</label>
            <input type="text"
                   wire:model="name"
                   placeholder="{{ __('treatments.common_name_placeholder') }}"
                   class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-400">
            @error('name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="mb-3">
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">{{ __('treatments.commercial_name') }}</label>
            <input type="text"
                   wire:model="commercialName"
                   placeholder="{{ __('treatments.commercial_name_placeholder') }}"
                   class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-400">
        </div>

        <div class="mb-4">
            <label class="block text-xs font-semibold text-slate-600 mb-2">{{ __('treatments.type') }} *</label>
            <div class="grid grid-cols-3 gap-2">
                @foreach([
                    ['daily', __('treatments.type_daily')],
                    ['weekly', __('treatments.type_weekly')],
                    ['cyclic', __('treatments.type_cyclic')],
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
                <span class="text-sm font-semibold text-slate-700">{{ __('treatments.medical_act_label') }}</span>
                <p class="text-xs text-slate-400">{{ __('treatments.medical_act_help') }}</p>
            </div>
            <button type="button" wire:click="$toggle('isMedicalAct')"
                    style="position:relative;display:inline-block;width:44px;height:24px;border-radius:9999px;background-color:{{ $isMedicalAct ? '#0ea5e9' : '#94a3b8' }};border:none;cursor:pointer;flex-shrink:0;transition:background-color .2s;">
                <span style="position:absolute;top:4px;left:{{ $isMedicalAct ? '24px' : '4px' }};width:16px;height:16px;border-radius:9999px;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.25);transition:left .15s;"></span>
            </button>
        </div>

        <div class="flex items-center justify-between mb-4">
            <div>
                <span class="text-sm font-semibold text-slate-700">{{ __('treatments.fasting') }}</span>
                <p class="text-xs text-slate-400">{{ __('treatments.fasting_help') }}</p>
            </div>
            <button type="button" wire:click="$toggle('requiresFasting')"
                    style="position:relative;display:inline-block;width:44px;height:24px;border-radius:9999px;background-color:{{ $requiresFasting ? '#f59e0b' : '#94a3b8' }};border:none;cursor:pointer;flex-shrink:0;transition:background-color .2s;">
                <span style="position:absolute;top:4px;left:{{ $requiresFasting ? '24px' : '4px' }};width:16px;height:16px;border-radius:9999px;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.25);transition:left .15s;"></span>
            </button>
        </div>

        <div class="mb-4">
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">{{ __('treatments.color') }}</label>
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
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">{{ __('treatments.linked_to') }}</label>
            <select wire:model.live="parentTreatmentId"
                    class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-400 bg-white">
                <option value="">{{ __('treatments.none') }}</option>
                @foreach($otherTreatments as $t)
                <option value="{{ $t->id }}">{{ $t->name }}{{ $t->commercial_name ? ' · ' . $t->commercial_name : '' }}</option>
                @endforeach
            </select>
            @error('parentTreatmentId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
        </div>

        @if($parentTreatmentId)
        <div class="mt-3">
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">{{ __('treatments.linked_duration') }}</label>
            <div class="flex items-center gap-3">
                <button wire:click="decrementLinkedDays"
                        class="w-10 h-10 rounded-xl bg-slate-100 text-slate-600 text-xl font-light hover:bg-slate-200 transition-colors flex items-center justify-center">
                    −
                </button>
                <div class="flex-1 text-center">
                    <p class="text-lg font-extrabold text-slate-800">
                        {{ $linkedDays }} {{ $linkedDays === 1 ? __('treatments.day') : __('treatments.days') }}
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
        {{ __('treatments.next') }} →
    </button>
    @endif

    {{-- ── Étape 2 : Widget accueil ─────────────────────────────────────── --}}
    @if($step === 2)
    <div class="bg-white rounded-2xl p-5 shadow-sm mb-4">
        <p class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-4">{{ $stepLabel }}</p>

        <div class="flex items-center justify-between mb-4">
            <span class="text-sm font-semibold text-slate-700">{{ __('treatments.show_on_home') }}</span>
            <button type="button" wire:click="$toggle('showWidget')"
                    style="position:relative;display:inline-block;width:44px;height:24px;border-radius:9999px;background-color:{{ $showWidget ? '#0ea5e9' : '#94a3b8' }};border:none;cursor:pointer;flex-shrink:0;transition:background-color .2s;">
                <span style="position:absolute;top:4px;left:{{ $showWidget ? '24px' : '4px' }};width:16px;height:16px;border-radius:9999px;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.25);transition:left .15s;"></span>
            </button>
        </div>

        <div style="display: {{ $showWidget ? 'block' : 'none' }};"
             wire:key="widget-icon-picker-{{ $showWidget ? '1' : '0' }}">
            <label class="block text-xs font-semibold text-slate-600 mb-2">{{ __('treatments.widget_icon') }}</label>
            <div class="flex gap-2 flex-wrap">
                @foreach($widgetIcons as $icon)
                <button type="button"
                        wire:click="$set('widgetIcon', '{{ $icon }}')"
                        class="w-10 h-10 rounded-xl text-xl flex items-center justify-center transition-all
                               {{ $widgetIcon === $icon ? 'bg-sky-100 ring-2 ring-sky-400' : 'bg-slate-100 hover:bg-slate-200' }}">
                    <x-alys-icon :value="$icon" kind="medical" class="w-6 h-6" />
                </button>
                @endforeach
            </div>
        </div>
    </div>

    <div class="flex gap-3">
        <button wire:click="prevStep"
                class="flex-1 py-3 rounded-xl text-sm font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 transition-colors">
            {{ __('treatments.previous') }}
        </button>
        <button wire:click="nextStep"
                class="flex-[2] py-3 rounded-xl text-sm font-bold text-white transition-colors hover:opacity-90"
                style="background: linear-gradient(135deg, #0ea5e9, #6366f1);">
            {{ __('treatments.next') }} →
        </button>
    </div>
    @endif

    {{-- ── Étape 3 : Posologie ─────────────────────────────────────────── --}}
    @if($step === 3)
    <div class="bg-white rounded-2xl p-5 shadow-sm mb-4">
        <p class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-4">{{ $stepLabel }}</p>

        <div class="mb-4">
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">{{ __('treatments.unit') }}</label>
            <input type="text"
                   wire:model="unit"
                   placeholder="{{ __('treatments.unit_placeholder') }}"
                   class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-400">
        </div>

        <div class="flex flex-col gap-2 mb-5">
            @foreach([['single', __('treatments.dose_single')], ['dayparts', __('treatments.dose_dayparts')], ['interval', __('treatments.dose_interval')]] as [$val, $lbl])
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
            @foreach([
                ['label' => __('treatments.morning'), 'inc' => 'incrementMorning', 'dec' => 'decrementMorning', 'value' => $doseMorning],
                ['label' => __('treatments.noon'),    'inc' => 'incrementNoon',    'dec' => 'decrementNoon',    'value' => $doseNoon],
                ['label' => __('treatments.evening'), 'inc' => 'incrementEvening', 'dec' => 'decrementEvening', 'value' => $doseEvening],
            ] as $part)
            <div class="mb-4">
                <p class="text-xs font-semibold text-slate-500 mb-2">{{ $part['label'] }}</p>
                <div class="flex flex-row items-center gap-4">
                    <button wire:click="{{ $part['dec'] }}"
                            class="w-10 h-10 rounded-xl bg-slate-100 text-slate-600 text-xl font-light hover:bg-slate-200 transition-colors flex items-center justify-center">
                        −
                    </button>
                    <div class="flex-1 text-center">
                        @php $pv = (float)($part['value'] ?? 0); $pdec = $unit === 'ml' ? 1 : ($pv != (int)$pv ? 1 : 0); @endphp
                        <p class="text-3xl font-extrabold leading-none" style="color: {{ $color }};">
                            {{ number_format($pv, $pdec, ',', '') }}
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
            </div>
            @endforeach
        @elseif($dosageMode === 'interval')
            {{-- Dose par prise --}}
            <p class="text-xs font-semibold text-slate-500 mb-2">{{ __('treatments.dose_per_intake') }}</p>
            <div class="flex flex-row items-center gap-4 mb-5">
                <button wire:click="decrement"
                        class="w-10 h-10 rounded-xl bg-slate-100 text-slate-600 text-xl font-light hover:bg-slate-200 transition-colors flex items-center justify-center">
                    −
                </button>
                <div class="flex-1 text-center">
                    @php $idec = $unit === 'ml' ? 1 : ($currentDose != (int)$currentDose ? 1 : 0); @endphp
                    <p class="text-4xl font-extrabold leading-none" style="color: {{ $color }};">
                        {{ number_format($currentDose, $idec, ',', '') }}
                    </p>
                    @if($unit)
                    <p class="text-sm text-slate-400 font-medium mt-1">{{ __('treatments.per_intake_suffix', ['unit' => $unit]) }}</p>
                    @endif
                </div>
                <button wire:click="increment"
                        class="w-10 h-10 rounded-xl bg-slate-100 text-slate-600 text-xl font-light hover:bg-slate-200 transition-colors flex items-center justify-center">
                    +
                </button>
            </div>
            {{-- Nombre de prises --}}
            <p class="text-xs font-semibold text-slate-500 mb-2">{{ __('treatments.intakes_per_day') }}</p>
            <div class="flex flex-row items-center gap-4 mb-2">
                <button wire:click="decrementTimesPerDay"
                        class="w-10 h-10 rounded-xl bg-slate-100 text-slate-600 text-xl font-light hover:bg-slate-200 transition-colors flex items-center justify-center">
                    −
                </button>
                <div class="flex-1 text-center">
                    @php $intervalH = $timesPerDay > 0 ? round(24 / $timesPerDay) : 0; @endphp
                    <p class="text-4xl font-extrabold leading-none" style="color: {{ $color }};">{{ $timesPerDay }}</p>
                    <p class="text-sm text-slate-400 font-medium mt-1">{{ __('treatments.times_per_day_suffix', ['hours' => $intervalH]) }}</p>
                </div>
                <button wire:click="incrementTimesPerDay"
                        class="w-10 h-10 rounded-xl bg-slate-100 text-slate-600 text-xl font-light hover:bg-slate-200 transition-colors flex items-center justify-center">
                    +
                </button>
            </div>
        @else
            <div class="flex flex-row items-center gap-4 mb-4">
                <button wire:click="decrement"
                        class="w-10 h-10 rounded-xl bg-slate-100 text-slate-600 text-xl font-light hover:bg-slate-200 transition-colors flex items-center justify-center">
                    −
                </button>
                <div class="flex-1 text-center">
                    @php $sdec = $unit === 'ml' ? 1 : ($currentDose != (int)$currentDose ? 1 : 0); @endphp
                    <p class="text-4xl font-extrabold leading-none" style="color: {{ $color }};">
                        {{ number_format($currentDose, $sdec, ',', '') }}
                    </p>
                    <p class="text-sm text-slate-400 font-medium mt-1">
                        {{ __('treatments.per_unit', ['unit' => $unit ?: '—', 'period' => $type === 'daily' ? __('treatments.period_day') : ($type === 'weekly' ? __('treatments.period_week') : __('treatments.period_intake'))]) }}
                    </p>
                </div>
                <button wire:click="increment"
                        class="w-10 h-10 rounded-xl bg-slate-100 text-slate-600 text-xl font-light hover:bg-slate-200 transition-colors flex items-center justify-center">
                    +
                </button>
            </div>
        @endif
    </div>

    <div class="flex gap-3">
        <button wire:click="prevStep"
                class="flex-1 py-3 rounded-xl text-sm font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 transition-colors">
            {{ __('treatments.previous') }}
        </button>
        <button wire:click="nextStep"
                class="flex-[2] py-3 rounded-xl text-sm font-bold text-white transition-colors hover:opacity-90"
                style="background: linear-gradient(135deg, #0ea5e9, #6366f1);">
            {{ __('treatments.next') }} →
        </button>
    </div>
    @endif

    {{-- ── Étape 4 : Planification / Récurrence ───────────────────────── --}}
    @if($step === 4)
    <div class="bg-white rounded-2xl p-5 shadow-sm mb-4">
        <p class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-4">{{ $stepLabel }}</p>

        @if($type === 'weekly')
            {{-- Jour de la semaine --}}
            <div class="mb-4">
                <label class="block text-xs font-semibold text-slate-600 mb-2">{{ __('treatments.day_of_week') }}</label>
                @php $dayNames = [__('treatments.day_mon'), __('treatments.day_tue'), __('treatments.day_wed'), __('treatments.day_thu'), __('treatments.day_fri'), __('treatments.day_sat'), __('treatments.day_sun')]; @endphp
                <div class="grid grid-cols-7 gap-1">
                    @foreach($dayNames as $i => $dayName)
                    <button wire:click="$set('dayOfWeek', {{ $i }})"
                            class="py-2 rounded-xl text-xs font-bold transition-colors
                                   {{ $dayOfWeek === $i ? 'text-white' : 'bg-slate-100 text-slate-500 hover:bg-slate-200' }}"
                            style="{{ $dayOfWeek === $i ? 'background: linear-gradient(135deg, #0ea5e9, #6366f1);' : '' }}">
                        {{ $dayName }}
                    </button>
                    @endforeach
                </div>
                @error('dayOfWeek') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Fréquence --}}
            <div class="mb-4">
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">{{ __('treatments.frequency') }}</label>
                <div class="flex items-center gap-3">
                    <button wire:click="decrementFrequency"
                            class="w-10 h-10 rounded-xl bg-slate-100 text-slate-600 text-xl font-light hover:bg-slate-200 transition-colors flex items-center justify-center">
                        −
                    </button>
                    <div class="flex-1 text-center">
                        <p class="text-lg font-extrabold text-slate-800">
                            {{ $frequencyWeeks === 1 ? __('treatments.every_week') : __('treatments.one_week_per', ['weeks' => $frequencyWeeks]) }}
                        </p>
                    </div>
                    <button wire:click="incrementFrequency"
                            class="w-10 h-10 rounded-xl bg-slate-100 text-slate-600 text-xl font-light hover:bg-slate-200 transition-colors flex items-center justify-center">
                        +
                    </button>
                </div>
                @error('frequencyWeeks') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Date de début (optionnel) --}}
            <div class="mb-2">
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">{{ __('treatments.first_intake_date') }}</label>
                <x-datepicker model="recurrenceStart" :value="$recurrenceStart" />
            </div>
        @else
            {{-- Cyclique : date + fréquence --}}
            <div class="mb-3">
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">{{ __('treatments.start_date') }}</label>
                <x-datepicker model="recurrenceStart" :value="$recurrenceStart" />
                @error('recurrenceStart') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="mb-2">
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">{{ __('treatments.frequency_weeks') }}</label>
                <div class="flex items-center gap-3">
                    <button wire:click="decrementFrequency"
                            class="w-10 h-10 rounded-xl bg-slate-100 text-slate-600 text-xl font-light hover:bg-slate-200 transition-colors flex items-center justify-center">
                        −
                    </button>
                    <div class="flex-1 text-center">
                        <p class="text-lg font-extrabold text-slate-800">
                            {{ $frequencyWeeks === 1 ? __('treatments.every_week') : __('treatments.every_n_weeks', ['weeks' => $frequencyWeeks]) }}
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
    </div>

    <div class="flex gap-3">
        <button wire:click="prevStep"
                class="flex-1 py-3 rounded-xl text-sm font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 transition-colors">
            {{ __('treatments.previous') }}
        </button>
        <button wire:click="nextStep"
                class="flex-[2] py-3 rounded-xl text-sm font-bold text-white transition-colors hover:opacity-90"
                style="background: linear-gradient(135deg, #0ea5e9, #6366f1);">
            {{ __('treatments.next') }} →
        </button>
    </div>
    @endif

    {{-- ── Étape 6 : Notifications ─────────────────────────────────────── --}}
    @if($step === 6)
    <div class="bg-white rounded-2xl p-5 shadow-sm mb-4">
        <p class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-4">{{ $stepLabel }}</p>

        {{-- Toggle --}}
        <div class="flex items-center justify-between mb-5">
            <div>
                <span class="text-sm font-semibold text-slate-700">{{ __('treatments.enable_reminders') }}</span>
                <p class="text-xs text-slate-400">{{ __('treatments.reminder_help') }}</p>
            </div>
            <button type="button" wire:click="$toggle('notificationEnabled')"
                    style="position:relative;display:inline-block;width:44px;height:24px;border-radius:9999px;background-color:{{ $notificationEnabled ? '#0ea5e9' : '#94a3b8' }};border:none;cursor:pointer;flex-shrink:0;transition:background-color .2s;">
                <span style="position:absolute;top:4px;left:{{ $notificationEnabled ? '24px' : '4px' }};width:16px;height:16px;border-radius:9999px;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.25);transition:left .15s;"></span>
            </button>
        </div>

        @if($notificationEnabled)
        <div class="space-y-3">
            @if($isMedicalAct || $dosageMode === 'single' || $dosageMode === 'interval')
            <x-time-picker
                :label="$dosageMode === 'interval' ? __('treatments.first_intake_time') : __('treatments.reminder_time')"
                property="notificationTimeMorning"
                :value="$notificationTimeMorning" />
            @error('notificationTimeMorning') <p class="text-xs text-red-500 mt-2">{{ $message }}</p> @enderror
            @if($dosageMode === 'interval' && $timesPerDay > 0)
            <p class="text-xs text-slate-400 text-center mt-2">
                {{ __('treatments.interval_reminders_note', ['count' => $timesPerDay, 'hours' => round(24 / $timesPerDay)]) }}
            </p>
            @endif

            @elseif($dosageMode === 'dayparts')
            @if(($doseMorning ?? 0) > 0)
            <x-time-picker :label="__('treatments.morning')" property="notificationTimeMorning" :value="$notificationTimeMorning" />
            @endif
            @if(($doseNoon ?? 0) > 0)
            <x-time-picker :label="__('treatments.noon')" property="notificationTimeNoon" :value="$notificationTimeNoon" />
            @endif
            @if(($doseEvening ?? 0) > 0)
            <x-time-picker :label="__('treatments.evening')" property="notificationTimeEvening" :value="$notificationTimeEvening" />
            @endif
            @error('notificationTimeMorning') <p class="text-xs text-red-500 mt-2">{{ $message }}</p> @enderror
            @endif

            @if($type === 'weekly' || $type === 'cyclic')
            <div class="px-3 py-2 bg-amber-50 rounded-xl">
                <p class="text-xs text-amber-700">
                    {{ __('treatments.scheduled_days_note') }}
                </p>
            </div>
            @endif
        </div>
        @endif
    </div>

    <div class="flex gap-3">
        <button wire:click="prevStep"
                class="flex-1 py-3 rounded-xl text-sm font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 transition-colors">
            ← {{ __('common.back') }}
        </button>
        <button wire:click="nextStep"
                class="flex-1 py-3 rounded-xl text-sm font-semibold text-white transition-colors"
                style="background: #0ea5e9;">
            {{ __('treatments.next') }} →
        </button>
    </div>
    @endif

    {{-- ── Étape 5 : Récapitulatif ─────────────────────────────────────── --}}
    @if($step === 5)
    <div class="bg-white rounded-2xl p-5 shadow-sm mb-4">
        <p class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-4">{{ $stepLabel }}</p>

        {{-- Nom --}}
        <div class="flex justify-between items-start py-3 border-b border-slate-100">
            <span class="text-xs text-slate-400 font-medium">{{ __('treatments.summary_name') }}</span>
            <div class="text-right">
                <span class="text-sm font-bold text-slate-800">{{ $name }}</span>
                @if($commercialName)
                <p class="text-xs text-slate-400">{{ $commercialName }}</p>
                @endif
            </div>
        </div>

        {{-- Type --}}
        <div class="flex justify-between items-center py-3 border-b border-slate-100">
            <span class="text-xs text-slate-400 font-medium">{{ __('treatments.type') }}</span>
            <span class="text-sm font-bold text-slate-800">
                {{ ['daily' => __('treatments.type_daily'), 'weekly' => __('treatments.type_weekly'), 'cyclic' => __('treatments.type_cyclic')][$type] ?? $type }}
            </span>
        </div>

        {{-- Couleur --}}
        <div class="flex justify-between items-center py-3 border-b border-slate-100">
            <span class="text-xs text-slate-400 font-medium">{{ __('treatments.summary_color') }}</span>
            <div class="w-5 h-5 rounded-full" style="background: {{ $color }};"></div>
        </div>

        {{-- Badges acte médical / à jeun --}}
        @if($isMedicalAct || $requiresFasting)
        <div class="flex gap-2 py-3 border-b border-slate-100">
            @if($isMedicalAct)
            <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-sky-100 text-sky-700">{{ __('treatments.medical_act') }}</span>
            @endif
            @if($requiresFasting)
            <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-700">{{ __('treatments.fasting') }}</span>
            @endif
        </div>
        @endif

        {{-- Traitement lié --}}
        @if($parentTreatmentId)
        @php $linked = $otherTreatments->firstWhere('id', $parentTreatmentId); @endphp
        <div class="flex justify-between items-center py-3 border-b border-slate-100">
            <span class="text-xs text-slate-400 font-medium">{{ __('treatments.summary_linked_to') }}</span>
            <span class="text-sm font-bold text-slate-800">
                {{ $linked?->name ?? '—' }}
                ({{ $linkedDays }} {{ $linkedDays === 1 ? __('treatments.day') : __('treatments.days') }})
            </span>
        </div>
        @endif

        {{-- Widget --}}
        @if($showWidget)
        <div class="flex justify-between items-center py-3 border-b border-slate-100">
            <span class="text-xs text-slate-400 font-medium">{{ __('treatments.summary_widget') }}</span>
            <x-alys-icon :value="$widgetIcon" kind="medical" class="w-5 h-5" />
        </div>
        @endif

        {{-- Notifications --}}
        @if($notificationEnabled)
        <div class="flex justify-between items-start py-3 border-b border-slate-100">
            <span class="text-xs text-slate-400 font-medium">{{ __('treatments.reminders') }}</span>
            <div class="text-right">
                @if(!$isMedicalAct && $dosageMode === 'dayparts')
                    @if($notificationTimeMorning && ($doseMorning ?? 0) > 0)<p class="text-xs text-slate-600">{{ __('treatments.morning') }} : <strong>{{ $notificationTimeMorning }}</strong></p>@endif
                    @if($notificationTimeNoon && ($doseNoon ?? 0) > 0)<p class="text-xs text-slate-600">{{ __('treatments.noon') }} : <strong>{{ $notificationTimeNoon }}</strong></p>@endif
                    @if($notificationTimeEvening && ($doseEvening ?? 0) > 0)<p class="text-xs text-slate-600">{{ __('treatments.evening') }} : <strong>{{ $notificationTimeEvening }}</strong></p>@endif
                @else
                    <span class="text-sm font-bold text-slate-800">{{ $notificationTimeMorning }}</span>
                @endif
            </div>
        </div>
        @endif

        {{-- Posologie --}}
        @if(!$isMedicalAct)
        <div class="flex justify-between items-start py-3 border-b border-slate-100">
            <span class="text-xs text-slate-400 font-medium">{{ __('treatments.summary_posology') }}</span>
            <div class="text-right">
                @if($dosageMode === 'dayparts')
                    <p class="text-xs text-slate-600">{{ __('treatments.morning') }} : <strong>{{ number_format($doseMorning ?? 0, $unit === 'ml' ? 1 : 0, ',', '') }} {{ $unit }}</strong></p>
                    <p class="text-xs text-slate-600">{{ __('treatments.noon') }} : <strong>{{ number_format($doseNoon ?? 0, $unit === 'ml' ? 1 : 0, ',', '') }} {{ $unit }}</strong></p>
                    <p class="text-xs text-slate-600">{{ __('treatments.evening') }} : <strong>{{ number_format($doseEvening ?? 0, $unit === 'ml' ? 1 : 0, ',', '') }} {{ $unit }}</strong></p>
                @elseif($dosageMode === 'interval')
                    @php $intervalH = $timesPerDay > 0 ? round(24 / $timesPerDay) : 0; @endphp
                    <span class="text-sm font-bold text-slate-800">
                        {{ __('treatments.per_intake_suffix', ['unit' => number_format($currentDose, $unit === 'ml' ? 1 : 0, ',', '') . ' ' . $unit]) }}
                    </span>
                    <p class="text-xs text-slate-400">{{ __('treatments.times_per_day_interval', ['count' => $timesPerDay, 'hours' => $intervalH]) }}</p>
                @else
                    <span class="text-sm font-bold text-slate-800">
                        {{ __('treatments.per_unit', ['unit' => number_format($currentDose, $unit === 'ml' ? 1 : 0, ',', '') . ' ' . $unit, 'period' => ['daily' => __('treatments.period_day'), 'weekly' => __('treatments.period_week'), 'cyclic' => __('treatments.period_intake')][$type] ?? '']) }}
                    </span>
                @endif
            </div>
        </div>
        @endif

        {{-- Récurrence --}}
        @if($type === 'cyclic')
        <div class="flex justify-between items-center py-3">
            <span class="text-xs text-slate-400 font-medium">{{ __('treatments.summary_recurrence') }}</span>
            <div class="text-right">
                <span class="text-sm font-bold text-slate-800">
                    {{ $frequencyWeeks === 1 ? __('treatments.every_week_short') : __('treatments.every_n_weeks_summary', ['weeks' => $frequencyWeeks]) }}
                </span>
                @if($recurrenceStart)
                <p class="text-xs text-slate-400">{{ __('treatments.summary_start') }} : {{ \Carbon\Carbon::parse($recurrenceStart)->format('d/m/Y') }}</p>
                @endif
            </div>
        </div>
        @endif
    </div>

    <div class="flex gap-3">
        <button wire:click="prevStep"
                class="flex-1 py-3 rounded-xl text-sm font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 transition-colors">
            {{ __('treatments.previous') }}
        </button>
        <button wire:click="save"
                class="flex-[2] py-3 rounded-xl text-sm font-bold text-white transition-colors hover:opacity-90"
                style="background: linear-gradient(135deg, #0ea5e9, #6366f1);">
            {{ __('treatments.create_treatment') }}
        </button>
    </div>
    @endif

</div>
