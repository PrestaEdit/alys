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
                        @php
                            $decimals = $treatment->unit === 'ml' ? 1 : 0;
                            $parts = [];
                            if ($treatment->dose_morning !== null) $parts[] = ['val' => number_format((float)$treatment->dose_morning, $decimals, ',', ''), 'lbl' => 'mat.'];
                            if ($treatment->dose_noon    !== null) $parts[] = ['val' => number_format((float)$treatment->dose_noon,    $decimals, ',', ''), 'lbl' => 'midi'];
                            if ($treatment->dose_evening !== null) $parts[] = ['val' => number_format((float)$treatment->dose_evening, $decimals, ',', ''), 'lbl' => 'soir'];
                        @endphp
                        <div class="flex items-end gap-2">
                            @foreach($parts as $p)
                            <div class="text-center">
                                <p class="text-lg font-extrabold leading-none" style="color: {{ $treatment->color }};">{{ $p['val'] }}</p>
                                <p class="text-[10px] text-slate-400 font-medium mt-0.5">{{ $p['lbl'] }}</p>
                            </div>
                            @if(!$loop->last)
                            <span class="text-slate-200 font-light text-lg leading-none mb-3">·</span>
                            @endif
                            @endforeach
                            @if($treatment->unit)
                            <p class="text-xs text-slate-400 mb-0.5">{{ $treatment->unit }}</p>
                            @endif
                        </div>
                    @elseif($treatment->current_dose !== null)
                    <p class="text-xl font-extrabold leading-none" style="color: {{ $treatment->color }};">
                        {{ number_format((float)$treatment->current_dose, $treatment->unit === 'ml' ? 1 : 0, ',', '') }}
                        <span class="text-sm font-normal text-slate-400">{{ $treatment->unit }}</span>
                    </p>
                    @endif
                </div>
                <span class="text-xs font-semibold px-2 py-1 rounded-full"
                      style="color: {{ $treatment->color }}; background-color: {{ $treatment->color }}18;">
                    @if($treatment->type === 'daily') Quotidien
                    @elseif($treatment->type === 'weekly') Hebdo · mardi
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
