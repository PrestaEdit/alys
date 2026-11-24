<div>
    @if($profile)
    {{-- Résumé --}}
    <button type="button" wire:click="openSheet"
            class="w-full bg-white rounded-2xl p-4 mb-4 shadow-sm flex items-center gap-3 text-left hover:bg-slate-50 transition-colors">
        <span class="w-10 h-10 rounded-xl bg-rose-50 flex items-center justify-center text-lg shrink-0">🩺</span>
        <span class="flex-1 min-w-0">
            <span class="block text-xs font-bold text-slate-600 uppercase tracking-wide">{{ __('profiles.medical_section') }}</span>
            @if($profile->weight_kg === null && $profile->height_cm === null && $profile->blood_group === null)
                <span class="block text-sm text-sky-500 font-semibold mt-0.5">{{ __('profiles.medical_fill') }}</span>
            @else
                <span class="block text-sm font-bold text-slate-800 mt-0.5">
                    {{ collect([
                        $profile->weight_kg !== null ? \App\Livewire\MedicalCard::formatKg($profile->weight_kg) . ' kg' : null,
                        $profile->height_cm !== null ? $profile->height_cm . ' cm' : null,
                        $profile->blood_group,
                    ])->filter()->implode(' · ') }}
                </span>
                @if($delta && $delta['kg'] != 0)
                <span class="block text-xs mt-0.5 {{ $delta['kg'] < 0 ? 'text-amber-600' : 'text-emerald-600' }}">
                    {{ __('profiles.weight_delta', [
                        'delta' => ($delta['kg'] > 0 ? '+' : '−') . \App\Livewire\MedicalCard::formatKg(abs($delta['kg'])),
                        'date'  => $delta['since'],
                    ]) }}
                </span>
                @endif
            @endif
        </span>
        <svg class="w-4 h-4 text-slate-300 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
        </svg>
    </button>

    {{-- Feuille d'édition rapide. z-[60] : au-dessus de la barre de
         navigation du layout (z-50), qui sinon masque les boutons. --}}
    @if($open)
    <div class="fixed inset-0 bg-black/50 z-[60] flex items-end justify-center">
        <button type="button" wire:click="close" class="absolute inset-0 cursor-default"
                aria-label="{{ __('profiles.close') }}"></button>

        <div class="relative bg-white rounded-t-3xl w-full max-w-lg p-5 shadow-xl max-h-[90vh] overflow-y-auto"
             style="padding-bottom: calc(1.25rem + var(--safe-bottom));">
            <div class="w-10 h-1 rounded-full bg-slate-200 mx-auto mb-4"></div>
            <h3 class="text-sm font-bold text-slate-800 mb-4">{{ __('profiles.medical_section') }} · {{ $profile->name }}</h3>

            {{-- Poids --}}
            <label class="block text-xs font-semibold text-slate-600 mb-1">{{ __('profiles.weight') }}</label>
            <div class="flex items-center gap-2 mb-1">
                <button type="button" wire:click="stepWeight(-0.1)"
                        class="w-11 h-11 rounded-xl bg-slate-100 text-slate-700 text-xl font-bold shrink-0">−</button>
                <input type="text" inputmode="decimal"
                       wire:model="weight"
                       placeholder="{{ __('profiles.weight_placeholder') }}"
                       class="flex-1 min-w-0 text-center rounded-xl border border-slate-200 px-3 py-2.5 text-lg font-bold focus:outline-none focus:ring-2 focus:ring-sky-400">
                <button type="button" wire:click="stepWeight(0.1)"
                        class="w-11 h-11 rounded-xl bg-slate-100 text-slate-700 text-xl font-bold shrink-0">+</button>
            </div>
            @error('weight')<p class="text-xs text-red-500 mb-2">{{ $message }}</p>@enderror

            @if($entries->isNotEmpty())
            <div class="mt-2 mb-4 rounded-xl bg-slate-50 px-3 py-2">
                <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide mb-1">{{ __('profiles.weight_history') }}</p>
                @foreach($entries as $entry)
                <div class="flex justify-between text-xs py-0.5">
                    <span class="text-slate-500">{{ $entry->measured_on->isoFormat('D MMM YYYY') }}</span>
                    <span class="font-semibold text-slate-700">{{ \App\Livewire\MedicalCard::formatKg($entry->weight_kg) }} kg</span>
                </div>
                @endforeach
            </div>
            @else
            <div class="mb-4"></div>
            @endif

            {{-- Taille --}}
            <label class="block text-xs font-semibold text-slate-600 mb-1">{{ __('profiles.height') }}</label>
            <input type="number" step="1" min="30" max="250" inputmode="numeric"
                   wire:model="height"
                   placeholder="{{ __('profiles.height_placeholder') }}"
                   class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm mb-1 focus:outline-none focus:ring-2 focus:ring-sky-400">
            @error('height')<p class="text-xs text-red-500 mb-2">{{ $message }}</p>@enderror

            {{-- Groupe sanguin --}}
            <label class="block text-xs font-semibold text-slate-600 mb-1 mt-3">{{ __('profiles.blood_group') }}</label>
            <div class="grid grid-cols-4 gap-2 mb-5">
                @foreach($bloodGroups as $group)
                <button type="button" wire:click="toggleBloodGroup('{{ $group }}')"
                        class="py-2 rounded-xl text-xs font-bold border transition-colors
                               {{ $bloodGroup === $group
                                  ? 'bg-sky-500 text-white border-sky-500'
                                  : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50' }}">
                    {{ $group }}
                </button>
                @endforeach
            </div>
            @error('bloodGroup')<p class="text-xs text-red-500 -mt-3 mb-3">{{ $message }}</p>@enderror

            <div class="flex gap-3">
                <button type="button" wire:click="close"
                        class="flex-1 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition-colors">
                    {{ __('common.cancel') }}
                </button>
                <button type="button" wire:click="save"
                        class="flex-1 py-2.5 rounded-xl text-sm font-bold text-white"
                        style="background: linear-gradient(135deg, #0ea5e9, #6366f1);">
                    {{ __('common.save') }}
                </button>
            </div>
        </div>
    </div>
    @endif
    @endif
</div>
