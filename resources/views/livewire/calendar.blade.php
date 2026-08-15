<div class="p-4 max-w-lg mx-auto" x-data="{ legend: false }">

    {{-- Header avec switcher --}}
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-xl font-extrabold text-slate-900">{{ __('calendar.title') }}</h1>
        <livewire:profile-switcher />
    </div>

    {{-- Navigation mensuelle --}}
    <div class="flex items-center justify-between mb-4">
        <button wire:click="previousMonth"
                class="w-8 h-8 rounded-xl bg-slate-100 flex items-center justify-center text-slate-500 hover:bg-slate-200 transition-colors text-lg">
            ‹
        </button>
        <h2 class="text-sm font-bold text-slate-800 capitalize">{{ $monthName }}</h2>
        <div class="flex items-center gap-2">
            <button @click="legend = !legend"
                    :class="legend ? 'bg-sky-100 text-sky-600' : 'bg-slate-100 text-slate-500'"
                    class="w-8 h-8 rounded-xl flex items-center justify-center transition-colors hover:bg-sky-100 hover:text-sky-600"
                    title="{{ __('calendar.legend_tooltip') }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10"/><path d="M12 16v-4m0-4h.01"/>
                </svg>
            </button>
            <button wire:click="nextMonth"
                    class="w-8 h-8 rounded-xl bg-slate-100 flex items-center justify-center text-slate-500 hover:bg-slate-200 transition-colors text-lg">
                ›
            </button>
        </div>
    </div>

    {{-- Grille du calendrier --}}
    <div class="bg-white rounded-2xl p-3 shadow-sm mb-4">

        {{-- En-têtes jours --}}
        <div class="grid grid-cols-7 mb-1">
            @foreach($weekdayHeaders as $header)
            <div class="text-center text-xs font-semibold text-slate-400 py-1">{{ $header }}</div>
            @endforeach
        </div>

        {{-- Cases du calendrier --}}
        <div class="grid grid-cols-7 gap-y-1">

            {{-- Cellules vides au début --}}
            @for($i = 0; $i < $startOffset; $i++)
            <div></div>
            @endfor

            {{-- Jours du mois --}}
            @for($day = 1; $day <= $daysInMonth; $day++)
            @php
                $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $day);
                $events = $monthEvents[$dateStr] ?? [];
                $isToday = $dateStr === $today;
                $isSelected = $dateStr === $selectedDate;
            @endphp
            <button wire:click="selectDay('{{ $dateStr }}')"
                    class="flex flex-col items-center py-1 rounded-xl transition-colors
                           {{ $isToday ? 'bg-sky-500' : ($isSelected ? 'bg-sky-100 ring-2 ring-sky-400' : 'hover:bg-slate-50') }}">
                <span class="text-xs font-medium mb-0.5
                             {{ $isToday ? 'text-white font-bold' : 'text-slate-700' }}">
                    {{ $day }}
                </span>
                {{-- Points de couleur (max 3) --}}
                <div class="flex gap-0.5 flex-wrap justify-center max-w-5">
                    @foreach(array_slice($events, 0, 3) as $event)
                    <span class="w-1 h-1 rounded-full flex-shrink-0"
                          style="background-color: {{ $isToday ? 'rgba(255,255,255,0.8)' : $event['color'] }};"></span>
                    @endforeach
                </div>
            </button>
            @endfor

        </div>
    </div>

    {{-- Légende (masquée par défaut) --}}
    <div x-show="legend"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-2"
         class="bg-white rounded-2xl shadow-sm mb-4 overflow-hidden">

        <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
            <p class="text-xs font-bold text-slate-500 uppercase tracking-wide">{{ __('calendar.legend') }}</p>
            <button @click="legend = false" class="text-slate-400 hover:text-slate-600 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="divide-y divide-slate-50">
            @foreach($legend as $item)
            <div class="flex items-center gap-3 px-4 py-2.5">
                <div class="w-1 self-stretch rounded-full flex-shrink-0"
                     style="background-color: {{ $item['color'] }};"></div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-bold text-slate-800 truncate">{{ $item['label'] }}</p>
                    @if($item['label'] !== $item['name'])
                    <p class="text-xs text-slate-400">{{ $item['name'] }}</p>
                    @endif
                </div>
                <span class="text-xs font-semibold px-2 py-0.5 rounded-full flex-shrink-0"
                      style="color: {{ $item['color'] }}; background-color: {{ $item['color'] }}18;">
                    @if($item['type'] === 'daily') {{ __('calendar.type_daily') }}
                    @elseif($item['type'] === 'weekly') {{ __('calendar.type_weekly') }}
                    @elseif($item['is_medical_act']) {{ __('calendar.type_medical_act') }}
                    @elseif($item['frequency_weeks']) {{ __('calendar.type_every_weeks', ['weeks' => $item['frequency_weeks']]) }}
                    @else {{ __('calendar.type_cyclic') }}
                    @endif
                </span>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Panneau de détail du jour sélectionné --}}
    @if($selectedDate)
    <div class="bg-white rounded-2xl p-4 shadow-sm mb-4">
        <div class="flex items-center justify-between mb-3">
            <p class="text-xs font-bold text-slate-800 uppercase tracking-wide">
                {{ \Carbon\Carbon::parse($selectedDate)->isoFormat('dddd D MMMM YYYY') }}
            </p>
            <button wire:click="openEventModal"
                    class="text-xs text-white font-semibold rounded-lg px-3 py-1.5 bg-sky-500 hover:bg-sky-600 transition-colors flex items-center gap-1 shadow-sm">
                <span class="text-sm leading-none">+</span> {{ __('events.add') }}
            </button>
        </div>

        @if(empty($selectedDayEvents))
        <p class="text-xs text-slate-400 text-center py-2">{{ __('calendar.empty_day') }}</p>
        @else
        <div class="space-y-2">
            @foreach($selectedDayEvents as $event)
            <div class="flex items-start gap-3 px-3 py-2 rounded-xl
                        {{ ($event['kind'] ?? 'treatment') === 'personal' ? 'bg-slate-50' : ($event['requires_fasting'] ? 'bg-amber-50 border border-amber-200' : 'bg-slate-50') }}">
                @if(($event['kind'] ?? 'treatment') === 'personal')
                    <x-alys-icon :value="$event['icon']" kind="twemoji" class="w-6 h-6 flex-shrink-0 mt-0.5" />
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-semibold text-slate-800">{{ $event['title'] }}</p>
                        @if(!empty($event['is_multi_day']))
                            <p class="text-xs text-slate-400">
                                {{ __('events.period', [
                                    'start' => \Carbon\Carbon::parse($event['start_date'])->isoFormat('D MMM'),
                                    'end'   => \Carbon\Carbon::parse($event['end_date'])->isoFormat('D MMM'),
                                ]) }}
                            </p>
                        @endif
                        @if(!empty($event['notes']))
                            <p class="text-xs text-slate-400">{{ $event['notes'] }}</p>
                        @endif
                    </div>
                    <div class="flex items-center gap-1 flex-shrink-0">
                        <button wire:click="editEvent({{ $event['id'] }})"
                                class="text-xs text-sky-500 font-semibold border border-sky-200 rounded-lg px-2 py-1 bg-sky-50 hover:bg-sky-100 transition-colors">
                            {{ __('events.edit') }}
                        </button>
                        <button wire:click="openDeleteEventModal({{ $event['id'] }})"
                                class="text-xs text-red-500 font-semibold border border-red-200 rounded-lg px-2 py-1 bg-red-50 hover:bg-red-100 transition-colors">
                            {{ __('events.delete') }}
                        </button>
                    </div>
                @else
                    <span class="w-2 h-2 rounded-full flex-shrink-0 mt-1" style="background-color: {{ $event['color'] }};"></span>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-semibold text-slate-800">{{ $event['display_name'] ?? $event['name'] }}</p>
                        @if($event['requires_fasting'])
                            <p class="text-xs text-amber-600 font-bold">⚠️ {{ __('calendar.fasting_warning', ['name' => $profileName]) }}</p>
                        @endif
                        @if(!empty($event['notes']))
                            <p class="text-xs text-slate-400">{{ $event['notes'] }}</p>
                        @elseif(!empty($event['dose_parts']))
                            @foreach($event['dose_parts'] as $part)
                                @if(!empty($event['id']))
                                    <button type="button"
                                            wire:click="toggleDaypartSkip({{ $event['id'] }}, '{{ $part['daypart'] }}')"
                                            title="{{ $part['skipped'] ? __('calendar.daypart_restore') : __('calendar.daypart_skip') }}"
                                            class="text-xs text-left {{ $part['skipped'] ? 'text-slate-300 line-through italic' : 'text-slate-500' }} hover:text-sky-500 transition-colors">
                                        {{ $part['text'] }}
                                    </button>
                                @else
                                    <p class="text-xs {{ $part['skipped'] ? 'text-slate-300 line-through italic' : 'text-slate-400' }}">{{ $part['text'] }}</p>
                                @endif
                            @endforeach
                        @elseif(isset($event['dose']) && $event['dose'])
                            @foreach(explode(' · ', $event['dose']) as $dosePart)
                            <p class="text-xs text-slate-400">{{ $dosePart }}</p>
                            @endforeach
                        @endif
                        @if(!empty($event['moved']) && $event['moved'])
                            <p class="text-xs text-orange-500 italic">{{ __('calendar.moved_from', ['date' => \Carbon\Carbon::parse($event['original_date'])->isoFormat('D MMM')]) }}</p>
                        @endif
                    </div>
                    @if(!empty($event['can_move']) && $event['can_move'])
                    <button wire:click="openMoveModal({{ $event['id'] }})"
                            class="text-xs text-sky-500 font-semibold border border-sky-200 rounded-lg px-2 py-1 bg-sky-50 hover:bg-sky-100 transition-colors flex-shrink-0">
                        {{ __('calendar.move') }}
                    </button>
                    @endif
                @endif
            </div>
            @endforeach
        </div>
        @endif
    </div>
    @endif

    {{-- Modal déplacement --}}
    @if($showMoveModal)
    <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl p-5 w-full max-w-sm shadow-xl">
            <h3 class="text-sm font-bold text-slate-800 mb-1">{{ __('calendar.move_event') }}</h3>
            <p class="text-xs text-slate-400 mb-4">{{ __('calendar.move_choose_date') }}</p>
            <div class="mb-4">
                <x-datepicker model="moveToDate" :value="$moveToDate" />
            </div>
            @error('moveToDate')
            <p class="text-xs text-red-500 mb-3">{{ $message }}</p>
            @enderror

            @if(!empty($moveMomentOptions))
                <p class="text-xs text-slate-400 mb-2">{{ __('calendar.move_choose_moment') }}</p>
                <div class="flex gap-2 mb-4">
                    @foreach($moveMomentOptions as $moment)
                        <button type="button"
                                wire:click="$set('moveToMoment', '{{ $moment }}')"
                                class="flex-1 py-2 rounded-xl text-xs font-semibold border transition-colors
                                       {{ $moveToMoment === $moment
                                          ? 'bg-sky-500 text-white border-sky-500'
                                          : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50' }}">
                            {{ __('calendar.moment_' . $moment) }}
                        </button>
                    @endforeach
                </div>
            @endif

            <div class="flex gap-3">
                <button wire:click="cancelMove"
                        class="flex-1 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition-colors">
                    {{ __('common.cancel') }}
                </button>
                <button wire:click="confirmMove"
                        class="flex-1 py-2.5 rounded-xl bg-sky-500 text-sm font-semibold text-white hover:bg-sky-600 transition-colors">
                    {{ __('common.confirm') }}
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- Modal événement personnel --}}
    @if($showEventModal)
    <div class="fixed inset-0 bg-black/50 z-50 overflow-y-auto flex items-center justify-center p-4 pb-24">
        <div class="bg-white rounded-2xl p-5 w-full max-w-sm shadow-xl max-h-[85dvh] overflow-y-auto my-auto">
            <h3 class="text-sm font-bold text-slate-800 mb-4">
                {{ $editingEventId ? __('events.edit_title') : __('events.new_title') }}
            </h3>

            <label class="block text-xs font-semibold text-slate-600 mb-1">{{ __('events.field_title') }}</label>
            <input type="text" wire:model="eventTitle" placeholder="{{ __('events.field_title_ph') }}"
                   class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-400 mb-1">
            @error('eventTitle') <p class="text-xs text-red-500 mb-2">{{ $message }}</p> @enderror

            <label class="block text-xs font-semibold text-slate-600 mb-1 mt-3">{{ __('events.field_category') }}</label>
            <div class="grid grid-cols-3 gap-2 mb-3">
                @foreach($eventCategories as $cat)
                <button type="button" wire:click="selectCategory('{{ $cat }}')"
                        class="px-2 py-2 rounded-xl border text-xs font-semibold transition-colors
                               {{ $eventCategory === $cat ? 'bg-sky-100 ring-2 ring-sky-400 text-sky-700 border-sky-200' : 'bg-slate-50 border-slate-200 text-slate-600 hover:bg-slate-100' }}">
                    {{ __('events.category_' . $cat) }}
                </button>
                @endforeach
            </div>

            <div class="grid grid-cols-2 gap-3 mb-1">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">{{ __('events.field_start') }}</label>
                    <x-datepicker model="eventStartDate" :value="$eventStartDate" wire:key="ev-start-{{ $editingEventId ?? 'new' }}" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">{{ __('events.field_end') }}</label>
                    <x-datepicker model="eventEndDate" :value="$eventEndDate" wire:key="ev-end-{{ $editingEventId ?? 'new' }}" />
                </div>
            </div>
            @error('eventEndDate') <p class="text-xs text-red-500 mb-2">{{ $message }}</p> @enderror

            <label class="block text-xs font-semibold text-slate-600 mb-1 mt-3">{{ __('events.field_color') }}</label>
            <div class="flex flex-wrap gap-2 mb-3">
                @foreach($eventColors as $c)
                <button type="button" wire:click="$set('eventColor', '{{ $c }}')"
                        class="w-7 h-7 rounded-full transition-transform {{ $eventColor === $c ? 'ring-2 ring-offset-2 ring-slate-400 scale-110' : '' }}"
                        style="background-color: {{ $c }};"></button>
                @endforeach
            </div>

            <label class="block text-xs font-semibold text-slate-600 mb-1">{{ __('events.field_icon') }}</label>
            <div class="flex flex-wrap gap-2 mb-3">
                @foreach($eventIcons as $icon)
                <button type="button" wire:click="$set('eventIcon', '{{ $icon }}')"
                        class="w-9 h-9 rounded-xl flex items-center justify-center text-lg transition-colors
                               {{ $eventIcon === $icon ? 'bg-sky-100 ring-2 ring-sky-400' : 'bg-slate-100 hover:bg-slate-200' }}">
                    <x-alys-icon :value="$icon" kind="twemoji" class="w-6 h-6" />
                </button>
                @endforeach
            </div>

            <label class="block text-xs font-semibold text-slate-600 mb-1">{{ __('events.field_notes') }}</label>
            <textarea wire:model="eventNotes" rows="2" placeholder="{{ __('events.field_notes_ph') }}"
                      class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-400 mb-4"></textarea>

            <div class="flex gap-3">
                <button wire:click="cancelEventModal"
                        class="flex-1 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition-colors">
                    {{ __('common.cancel') }}
                </button>
                <button wire:click="saveEvent"
                        class="flex-1 py-2.5 rounded-xl bg-sky-500 text-sm font-semibold text-white hover:bg-sky-600 transition-colors">
                    {{ __('events.save') }}
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- Modale de confirmation de suppression d'un événement --}}
    @if($showDeleteEventModal)
    <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl p-5 w-full max-w-sm shadow-xl">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl bg-red-50 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-800">{{ __('events.delete_title') }}</h3>
                    <p class="text-xs text-slate-400 mt-0.5">{{ __('events.delete_subtitle') }}</p>
                </div>
            </div>
            <p class="text-xs text-slate-500 mb-4 pl-13">{{ __('events.delete_description') }}</p>
            <div class="flex gap-2">
                <button wire:click="cancelDeleteEvent"
                        class="flex-1 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition-colors">
                    {{ __('common.cancel') }}
                </button>
                {{-- Haptic « warning » : action destructive irréversible. --}}
                <button wire:click="confirmDeleteEvent"
                        x-on:click="$haptic('warning')"
                        class="flex-1 py-2.5 rounded-xl bg-red-500 text-sm font-semibold text-white hover:bg-red-600 transition-colors">
                    {{ __('events.delete') }}
                </button>
            </div>
        </div>
    </div>
    @endif

</div>
