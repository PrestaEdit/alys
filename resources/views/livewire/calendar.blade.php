<div class="p-4 max-w-lg mx-auto" x-data="{ legend: false }">

    {{-- Header avec switcher --}}
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-xl font-extrabold text-slate-900">Calendrier</h1>
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
                    title="Légende">
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
            @foreach(['L','M','M','J','V','S','D'] as $header)
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
            <p class="text-xs font-bold text-slate-500 uppercase tracking-wide">Légende</p>
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
                    @if($item['type'] === 'daily') Quotidien
                    @elseif($item['type'] === 'weekly') Hebdo
                    @elseif($item['is_medical_act']) Acte médical
                    @elseif($item['frequency_weeks']) / {{ $item['frequency_weeks'] }} sem.
                    @else Cyclique
                    @endif
                </span>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Panneau de détail du jour sélectionné --}}
    @if($selectedDate)
    <div class="bg-white rounded-2xl p-4 shadow-sm mb-4">
        <p class="text-xs font-bold text-slate-800 uppercase tracking-wide mb-3">
            {{ \Carbon\Carbon::parse($selectedDate)->locale('fr')->isoFormat('dddd D MMMM YYYY') }}
        </p>

        @if(empty($selectedDayEvents))
        <p class="text-xs text-slate-400 text-center py-2">Aucun événement ce jour.</p>
        @else
        <div class="space-y-2">
            @foreach($selectedDayEvents as $event)
            <div class="flex items-start gap-3 px-3 py-2 rounded-xl
                        {{ $event['requires_fasting'] ? 'bg-amber-50 border border-amber-200' : 'bg-slate-50' }}">
                <span class="w-2 h-2 rounded-full flex-shrink-0 mt-1" style="background-color: {{ $event['color'] }};"></span>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-semibold text-slate-800">{{ $event['display_name'] ?? $event['name'] }}</p>
                    @if($event['requires_fasting'])
                        <p class="text-xs text-amber-600 font-bold">⚠️ {{ $profileName }} doit être à jeun</p>
                    @endif
                    @if(!empty($event['notes']))
                        <p class="text-xs text-slate-400">{{ $event['notes'] }}</p>
                    @elseif(isset($event['dose']) && $event['dose'])
                        @foreach(explode(' · ', $event['dose']) as $dosePart)
                        <p class="text-xs text-slate-400">{{ $dosePart }}</p>
                        @endforeach
                    @endif
                    @if(!empty($event['moved']) && $event['moved'])
                        <p class="text-xs text-orange-500 italic">Déplacé (était le {{ \Carbon\Carbon::parse($event['original_date'])->locale('fr')->isoFormat('D MMM') }})</p>
                    @endif
                </div>
                @if(!empty($event['can_move']) && $event['can_move'])
                <button wire:click="openMoveModal({{ $event['id'] }})"
                        class="text-xs text-sky-500 font-semibold border border-sky-200 rounded-lg px-2 py-1 bg-sky-50 hover:bg-sky-100 transition-colors flex-shrink-0">
                    Déplacer
                </button>
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
            <h3 class="text-sm font-bold text-slate-800 mb-1">Déplacer l'événement</h3>
            <p class="text-xs text-slate-400 mb-4">Choisir la nouvelle date :</p>
            <input type="date"
                   wire:model="moveToDate"
                   class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-400 mb-4">
            @error('moveToDate')
            <p class="text-xs text-red-500 mb-3">{{ $message }}</p>
            @enderror
            <div class="flex gap-3">
                <button wire:click="cancelMove"
                        class="flex-1 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition-colors">
                    Annuler
                </button>
                <button wire:click="confirmMove"
                        class="flex-1 py-2.5 rounded-xl bg-sky-500 text-sm font-semibold text-white hover:bg-sky-600 transition-colors">
                    Confirmer
                </button>
            </div>
        </div>
    </div>
    @endif

</div>
