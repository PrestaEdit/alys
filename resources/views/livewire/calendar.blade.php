<div class="p-4 max-w-lg mx-auto" x-data>

    {{-- Navigation mensuelle --}}
    <div class="flex items-center justify-between mb-4">
        <button wire:click="previousMonth"
                class="w-8 h-8 rounded-xl bg-slate-100 flex items-center justify-center text-slate-500 hover:bg-slate-200 transition-colors text-lg">
            ‹
        </button>
        <h2 class="text-sm font-bold text-slate-800 capitalize">{{ $monthName }}</h2>
        <button wire:click="nextMonth"
                class="w-8 h-8 rounded-xl bg-slate-100 flex items-center justify-center text-slate-500 hover:bg-slate-200 transition-colors text-lg">
            ›
        </button>
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

    {{-- Légende --}}
    <div class="flex flex-wrap gap-x-4 gap-y-1 mb-4 px-1">
        @foreach([
            ['color' => '#f97316', 'label' => 'Hôpital'],
            ['color' => '#ef4444', 'label' => 'MTX'],
            ['color' => '#8b5cf6', 'label' => 'VCR'],
            ['color' => '#0ea5e9', 'label' => 'IT MTTX'],
        ] as $item)
        <div class="flex items-center gap-1.5">
            <span class="w-2 h-2 rounded-full" style="background-color: {{ $item['color'] }};"></span>
            <span class="text-xs text-slate-500">{{ $item['label'] }}</span>
        </div>
        @endforeach
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
            <div class="flex items-center gap-3 px-3 py-2 rounded-xl
                        {{ $event['requires_fasting'] ? 'bg-amber-50 border border-amber-200' : 'bg-slate-50' }}">
                <span class="w-2 h-2 rounded-full flex-shrink-0" style="background-color: {{ $event['color'] }};"></span>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-semibold text-slate-800">{{ $event['name'] }}</p>
                    @if($event['requires_fasting'])
                        <p class="text-xs text-amber-600 font-bold">⚠️ Alexis doit être à jeun</p>
                    @endif
                    @if(isset($event['dose']) && $event['dose'])
                        <p class="text-xs text-slate-400">{{ $event['dose'] }}</p>
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
    <div class="fixed inset-0 bg-black/50 z-50 flex items-end justify-center p-4">
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
