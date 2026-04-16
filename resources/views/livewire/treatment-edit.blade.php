<div class="p-4 max-w-lg mx-auto">

    {{-- Header --}}
    <div class="flex items-center gap-3 mb-5">
        <a href="{{ route('treatments') }}"
           class="w-8 h-8 rounded-xl bg-slate-100 flex items-center justify-center text-slate-500 hover:bg-slate-200 transition-colors text-lg">
            ‹
        </a>
        <div>
            <h1 class="text-base font-extrabold text-slate-900">{{ $treatment->name }} · {{ $treatment->commercial_name }}</h1>
            <p class="text-xs text-slate-400">Traitement {{ $treatment->type === 'daily' ? 'quotidien' : ($treatment->type === 'weekly' ? 'hebdomadaire' : 'cyclique') }}</p>
        </div>
    </div>

    @if(session('success'))
    <div class="bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-2 mb-4">
        <p class="text-xs font-semibold text-emerald-700">{{ session('success') }}</p>
    </div>
    @endif

    {{-- Sélecteur de dose --}}
    <div class="bg-white rounded-2xl p-5 shadow-sm mb-4">
        <p class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-4">Posologie actuelle</p>

        <div class="flex items-center gap-4 mb-4">
            <button wire:click="decrement"
                    class="w-10 h-10 rounded-xl bg-slate-100 text-slate-600 text-xl font-light hover:bg-slate-200 transition-colors flex items-center justify-center">
                −
            </button>
            <div class="flex-1 text-center">
                <p class="text-4xl font-extrabold leading-none" style="color: {{ $treatment->color }};">
                    {{ number_format($newDose, $treatment->unit === 'ml' ? 1 : 0, ',', '') }}
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

        {{-- Note --}}
        <div class="flex items-center gap-2 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 mb-4">
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
            Enregistrer la modification
        </button>
    </div>

    {{-- Historique --}}
    <div class="bg-white rounded-2xl p-5 shadow-sm">
        <p class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-4">Historique</p>

        <div class="relative pl-5">
            <div class="absolute left-[3px] top-2 bottom-2 w-0.5 bg-slate-200 rounded-full"></div>

            @foreach($history as $index => $entry)
            <div class="relative mb-4 last:mb-0">
                <div class="absolute -left-5 top-0.5 w-2.5 h-2.5 rounded-full border-2 border-white shadow
                            {{ $index === 0 ? 'bg-sky-500' : 'bg-slate-300' }}"></div>
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm font-bold {{ $index === 0 ? 'text-slate-800' : 'text-slate-500' }}">
                            {{ number_format($entry->dose, $treatment->unit === 'ml' ? 1 : 0, ',', '') }} {{ $treatment->unit }} / {{ $treatment->type === 'daily' ? 'jour' : 'prise' }}
                        </p>
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

</div>
