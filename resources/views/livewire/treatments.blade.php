<div class="p-4 max-w-lg mx-auto">
    <div class="flex items-center justify-between mb-5">
        <h1 class="text-xl font-extrabold text-slate-900">Traitements</h1>
        <div class="flex items-center gap-2">
            <livewire:profile-switcher />
            <a href="{{ route('treatments.create') }}"
               class="w-9 h-9 rounded-xl bg-sky-500 flex items-center justify-center text-white hover:bg-sky-600 transition-colors shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
            </a>
        </div>
    </div>

    @if($treatments->isEmpty())
    <div class="flex flex-col items-center justify-center py-16 px-6 text-center">
        <div class="w-16 h-16 rounded-2xl bg-sky-50 flex items-center justify-center mb-4">
            <svg class="w-8 h-8 text-sky-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"/>
            </svg>
        </div>
        <h3 class="text-base font-bold text-slate-800 mb-1">Aucun traitement</h3>
        <p class="text-sm text-slate-400 mb-6 max-w-xs">Ce profil n'a pas encore de traitement associé. Ajoutez-en un pour commencer le suivi.</p>
        <a href="{{ route('treatments.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-sky-500 text-white text-sm font-semibold hover:bg-sky-600 transition-colors shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Ajouter un traitement
        </a>
    </div>
    @else
    <div class="space-y-3">
        @foreach($treatments as $treatment)
        <div class="bg-white rounded-2xl p-4 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full flex-shrink-0"
                          style="background-color: {{ $treatment->color }};"></span>
                    <div>
                        <p class="text-sm font-bold text-slate-800">{{ $treatment->displayName() }}</p>
                        <p class="text-xs text-slate-400 italic">{{ $treatment->name }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-1.5">
                    <a href="{{ route('treatments.edit', $treatment) }}"
                       class="text-xs text-sky-500 font-semibold border border-sky-200 rounded-xl px-3 py-1.5 bg-sky-50 hover:bg-sky-100 transition-colors">
                        Modifier
                    </a>
                    <button wire:click="archive({{ $treatment->id }})"
                            class="text-xs text-slate-400 font-semibold border border-slate-200 rounded-xl px-2.5 py-1.5 bg-slate-50 hover:bg-slate-100 transition-colors">
                        Archiver
                    </button>
                </div>
            </div>

            <div class="flex items-center justify-between">
                <div>
                    @if($treatment->hasDayPartDoses())
                        <div class="space-y-0.5">
                            @if($treatment->dose_morning !== null)
                            @php $dm = (float)$treatment->dose_morning; $dmdec = $treatment->unit === 'ml' ? 1 : ($dm != (int)$dm ? 1 : 0); @endphp
                            <p class="text-xs font-semibold" style="color: {{ $treatment->color }};">
                                Matin · {{ number_format($dm, $dmdec, ',', '') }}{{ $treatment->unit ? ' ' . $treatment->unit : '' }}
                            </p>
                            @endif
                            @if($treatment->dose_noon !== null)
                            @php $dn = (float)$treatment->dose_noon; $dndec = $treatment->unit === 'ml' ? 1 : ($dn != (int)$dn ? 1 : 0); @endphp
                            <p class="text-xs font-semibold" style="color: {{ $treatment->color }};">
                                Midi · {{ number_format($dn, $dndec, ',', '') }}{{ $treatment->unit ? ' ' . $treatment->unit : '' }}
                            </p>
                            @endif
                            @if($treatment->dose_evening !== null)
                            @php $dev = (float)$treatment->dose_evening; $devdec = $treatment->unit === 'ml' ? 1 : ($dev != (int)$dev ? 1 : 0); @endphp
                            <p class="text-xs font-semibold" style="color: {{ $treatment->color }};">
                                Soir · {{ number_format($dev, $devdec, ',', '') }}{{ $treatment->unit ? ' ' . $treatment->unit : '' }}
                            </p>
                            @endif
                        </div>
                    @elseif($treatment->hasIntervalDose())
                        @php $intervalH = $treatment->times_per_day > 0 ? round(24 / $treatment->times_per_day) : 0;
                             $icd = (float)$treatment->current_dose; $icddec = $treatment->unit === 'ml' ? 1 : ($icd != (int)$icd ? 1 : 0); @endphp
                        <p class="text-xl font-extrabold leading-none" style="color: {{ $treatment->color }};">
                            {{ number_format($icd, $icddec, ',', '') }}
                            <span class="text-sm font-normal text-slate-400">{{ $treatment->unit }}</span>
                        </p>
                        <p class="text-xs text-slate-400 mt-0.5">{{ $treatment->times_per_day }}×/jour · toutes les {{ $intervalH }}h</p>
                    @elseif($treatment->current_dose !== null)
                    @php $scd = (float)$treatment->current_dose; $scddec = $treatment->unit === 'ml' ? 1 : ($scd != (int)$scd ? 1 : 0); @endphp
                    <p class="text-xl font-extrabold leading-none" style="color: {{ $treatment->color }};">
                        {{ number_format($scd, $scddec, ',', '') }}
                        <span class="text-sm font-normal text-slate-400">{{ $treatment->unit }}</span>
                    </p>
                    @endif
                </div>
                <span class="text-xs font-semibold px-2 py-1 rounded-full"
                      style="color: {{ $treatment->color }}; background-color: {{ $treatment->color }}18;">
                    @if($treatment->type === 'daily') Quotidien
                    @elseif($treatment->type === 'weekly')
                        @if($treatment->frequency_weeks && $treatment->frequency_weeks > 1)
                            1 sem. / {{ $treatment->frequency_weeks }} · {{ $treatment->dayOfWeekName() }}
                        @else
                            Hebdo · {{ $treatment->dayOfWeekName() }}
                        @endif
                    @elseif($treatment->is_medical_act) Acte médical
                    @elseif($treatment->frequency_weeks) / {{ $treatment->frequency_weeks }} sem.
                    @else Cyclique
                    @endif
                </span>
            </div>

            {{-- Dernier changement de posologie --}}
            @if($treatment->posologyHistory->count() > 1)
            <div class="mt-2 pt-2 border-t border-slate-100 flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-slate-300"></span>
                <p class="text-xs text-slate-400">
                    Modifié le {{ $treatment->posologyHistory->first()->started_at->locale('fr')->isoFormat('D MMM YYYY') }}
                </p>
            </div>
            @endif
        </div>
        @endforeach
    </div>
    @endif

    {{-- Modal confirmation archivage --}}
    @if($showArchiveModal)
    <div class="fixed inset-0 bg-black/50 z-50 flex items-end justify-center p-4 sm:items-center">
        <div class="bg-white rounded-2xl p-5 w-full max-w-sm shadow-xl">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-.375c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v.375c0 .621.504 1.125 1.125 1.125Z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-800">Archiver le traitement</h3>
                    <p class="text-xs text-slate-400 mt-0.5">Ce traitement sera masqué de la liste active.</p>
                </div>
            </div>
            <p class="text-xs text-slate-500 mb-4 pl-13">Vous pourrez le désarchiver à tout moment depuis la section traitements archivés.</p>
            <div class="flex gap-2">
                <button wire:click="cancelArchive"
                        class="flex-1 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition-colors">
                    Annuler
                </button>
                <button wire:click="confirmArchive"
                        class="flex-1 py-2.5 rounded-xl bg-slate-700 text-sm font-semibold text-white hover:bg-slate-800 transition-colors">
                    Archiver
                </button>
            </div>
        </div>
    </div>
    @endif

    @if($archived->isNotEmpty())
    <details class="mt-6">
        <summary class="text-sm font-semibold text-slate-500 cursor-pointer">
            Traitements archivés ({{ $archived->count() }})
        </summary>
        <div class="space-y-2 mt-3">
            @foreach($archived as $treatment)
            <div class="bg-slate-50 rounded-2xl p-4 flex items-center gap-3">
                <span class="w-3 h-3 rounded-full flex-shrink-0 opacity-50"
                      style="background-color: {{ $treatment->color }};"></span>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-slate-500 truncate">{{ $treatment->displayName() }}</p>
                    @if($treatment->name !== $treatment->displayName())
                    <p class="text-xs text-slate-400 italic truncate">{{ $treatment->name }}</p>
                    @endif
                </div>
                <button wire:click="unarchive({{ $treatment->id }})"
                        class="text-xs font-semibold text-sky-500 shrink-0">
                    Désarchiver
                </button>
            </div>
            @endforeach
        </div>
    </details>
    @endif
</div>
