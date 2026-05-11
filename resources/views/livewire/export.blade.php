<div class="p-4 max-w-lg mx-auto">

    {{-- Header --}}
    <div class="flex items-center gap-3 mb-5">
        <a href="{{ route('home') }}"
           class="w-8 h-8 rounded-xl bg-slate-100 flex items-center justify-center text-slate-500 hover:bg-slate-200 transition-colors text-lg">
            ‹
        </a>
        <h1 class="text-xl font-extrabold text-slate-900">Exporter</h1>
    </div>

    @if($success)
        <div class="bg-green-50 border border-green-200 rounded-2xl p-5 shadow-sm text-center">
            <p class="text-2xl mb-2">✓</p>
            <p class="text-sm font-semibold text-green-800">Export réussi !</p>
            <p class="text-xs text-green-600 mt-1">Partagez ou enregistrez le fichier depuis la fenêtre ouverte.</p>
            <a href="{{ route('home') }}" wire:navigate
               class="mt-4 inline-block bg-green-600 text-white font-semibold py-2 px-6 rounded-2xl text-sm">
                Retour à l'accueil
            </a>
        </div>
    @else

    @if($exportError)
        <div class="bg-red-50 border border-red-200 rounded-2xl p-4 mb-4">
            <p class="text-sm text-red-700">{{ $exportError }}</p>
        </div>
    @endif

    @php
        $totalSelected = count($selectedTreatments);
        $profileCount  = count($selectedProfiles);
    @endphp

    {{-- Summary --}}
    <div class="bg-violet-50 border border-violet-100 rounded-2xl p-3 mb-4 flex items-center gap-2 text-violet-700 text-sm">
        <span>📦</span>
        <span>
            <strong>{{ $profileCount }}</strong> profil{{ $profileCount !== 1 ? 's' : '' }} ·
            <strong>{{ $totalSelected }}</strong> traitement{{ $totalSelected !== 1 ? 's' : '' }} ·
            {{ now()->locale('fr')->isoFormat('D MMM YYYY') }}
        </span>
    </div>

    {{-- Profiles list --}}
    <div class="space-y-4 mb-5">
        @foreach($profiles as $profile)
            <div class="bg-white rounded-2xl shadow-sm overflow-hidden">

                {{-- Profile row --}}
                <label class="flex items-center gap-3 p-4 cursor-pointer select-none">
                    <input type="checkbox"
                           wire:click="toggleProfile({{ $profile->id }})"
                           @checked(in_array($profile->id, $selectedProfiles, true))
                           class="w-4 h-4 cursor-pointer flex-shrink-0"
                           style="accent-color: {{ $profile->color }}">
                    <span class="w-3 h-3 rounded-full flex-shrink-0" style="background-color: {{ $profile->color }};"></span>
                    <span class="font-semibold text-slate-900 flex-1 text-sm">{{ $profile->name }}</span>
                </label>

                {{-- Treatment rows --}}
                @if($profile->treatments->isNotEmpty())
                    <div class="border-t border-slate-100 divide-y divide-slate-50">
                        @foreach($profile->treatments as $treatment)
                            @php
                                $tKey = $profile->id . ':' . $treatment->id;
                            @endphp
                            <label class="flex items-center gap-3 px-4 py-3 cursor-pointer select-none">
                                <input type="checkbox"
                                       wire:click="toggleTreatment('{{ $tKey }}')"
                                       @checked(in_array($tKey, $selectedTreatments, true))
                                       class="w-4 h-4 cursor-pointer flex-shrink-0"
                                       style="accent-color: {{ $profile->color }}">
                                <span class="text-sm text-slate-800 flex-1">{{ $treatment->displayName() }}</span>
                                @if($treatment->current_dose && $treatment->unit)
                                    <span class="text-xs text-slate-400">{{ $treatment->current_dose }} {{ $treatment->unit }}</span>
                                @endif
                            </label>
                        @endforeach
                    </div>
                @endif

            </div>
        @endforeach
    </div>

    {{-- Export button --}}
    <button wire:click="export"
            wire:loading.attr="disabled"
            wire:target="export"
            @disabled(count($selectedTreatments) === 0)
            class="w-full bg-indigo-600 text-white font-semibold py-3 rounded-2xl text-sm disabled:opacity-50">
        <span wire:loading.remove wire:target="export">
            Exporter ({{ $totalSelected }} traitement{{ $totalSelected !== 1 ? 's' : '' }})
        </span>
        <span wire:loading wire:target="export">Export en cours…</span>
    </button>

    @endif

</div>
