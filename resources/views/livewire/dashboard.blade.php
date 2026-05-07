<div class="p-4 max-w-lg mx-auto">

    {{-- En-tête --}}
    <div class="flex items-center justify-between mb-5">
        <div>
            <p class="text-xs text-slate-400 font-medium">{{ now()->locale('fr')->isoFormat('dddd D MMMM YYYY') }}</p>
            <h1 class="text-xl font-extrabold text-slate-900">{{ $patientName }} 💙</h1>
        </div>
        <div class="flex items-center gap-2">
            <livewire:profile-switcher />
            <button wire:click="export" wire:loading.attr="disabled" wire:target="export"
                    class="w-9 h-9 rounded-xl bg-slate-100 flex items-center justify-center text-slate-500 hover:bg-slate-200 transition-colors disabled:opacity-50">
                <svg wire:loading.remove wire:target="export" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                </svg>
                <svg wire:loading wire:target="export" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                </svg>
            </button>
        </div>
    </div>

    @if($exportError)
    <div class="bg-red-50 border border-red-200 rounded-2xl p-3 mb-4 text-xs text-red-700">
        {{ $exportError }}
    </div>
    @endif

    {{-- Bannière prochain RDV --}}
    @if($nextHospitalDate)
    <div class="rounded-2xl p-4 mb-4 text-white overflow-hidden relative"
         style="background: linear-gradient(135deg, #0ea5e9, #6366f1);">
        <div class="absolute top-0 right-0 w-24 h-24 rounded-full bg-white/10 -translate-y-8 translate-x-8"></div>
        <p class="text-xs opacity-80 uppercase tracking-wide font-semibold mb-1">Prochain RDV hôpital</p>
        <p class="text-lg font-extrabold capitalize">{{ $nextHospitalDate }}</p>
    </div>
    @endif

    {{-- Barre de progression --}}
    @if($progressPercent !== null)
    <div class="bg-white rounded-2xl p-4 mb-4 shadow-sm">
        <div class="flex justify-between items-center mb-2">
            <p class="text-xs font-semibold text-slate-700">Fin du traitement</p>
            <p class="text-xs font-bold text-sky-500">{{ $daysRemaining }} jours restants</p>
        </div>
        <div class="w-full bg-slate-200 rounded-full h-1.5 overflow-hidden">
            <div class="h-full rounded-full transition-all duration-500"
                 style="width: {{ $progressPercent }}%; background: linear-gradient(90deg, #0ea5e9, #6366f1);">
            </div>
        </div>
        <div class="flex justify-between mt-1.5">
            <p class="text-xs text-slate-400">{{ $treatmentStartLabel }}</p>
            <p class="text-xs text-slate-400">{{ $treatmentEndLabel }}</p>
        </div>
    </div>
    @endif

    {{-- Widgets dynamiques --}}
    @if(count($widgets) > 0)
    <div class="grid grid-cols-2 gap-3 mb-4">
        @foreach($widgets as $widget)
        <div class="bg-white rounded-2xl p-3 shadow-sm">
            <div class="w-8 h-8 rounded-xl flex items-center justify-center text-lg mb-1"
                 style="background-color: {{ $widget['color'] }}18;">
                {{ $widget['icon'] }}
            </div>
            <p class="text-2xl font-extrabold leading-none" style="color: {{ $widget['color'] }};">{{ $widget['count'] }}</p>
            <p class="text-xs text-slate-400 font-medium mt-0.5">{{ $widget['display_name'] }}</p>
        </div>
        @endforeach
    </div>
    @endif

    {{-- Traitements du jour --}}
    <div class="bg-white rounded-2xl p-4 shadow-sm">
        <p class="text-xs font-bold text-slate-600 uppercase tracking-wide mb-3">Aujourd'hui</p>
        <div class="space-y-2">
            @forelse($todayEvents as $event)
            <div class="flex items-center gap-3 px-3 py-2 rounded-xl
                        {{ $event['requires_fasting'] ? 'bg-amber-50 border border-amber-200' :
                           ($event['type'] === 'cyclic' ? 'bg-slate-50 border border-slate-100' : 'bg-slate-50') }}">
                <span class="w-2 h-2 rounded-full flex-shrink-0" style="background-color: {{ $event['color'] }};"></span>
                <span class="text-xs font-medium text-slate-700 flex-1">{{ $event['display_name'] ?? $event['name'] }}</span>
                @if($event['requires_fasting'])
                    <span class="text-xs text-amber-600 font-bold">À jeun !</span>
                @elseif(isset($event['dose']) && $event['dose'])
                    <span class="text-xs text-slate-400">{{ $event['dose'] }}</span>
                @endif
            </div>
            @empty
            <p class="text-xs text-slate-400 text-center py-2">Aucun événement particulier aujourd'hui.</p>
            @endforelse
        </div>
    </div>

</div>
