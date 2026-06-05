<div class="p-4 max-w-lg mx-auto">

    <div class="flex items-center gap-3 mb-5">
        <a href="{{ route('settings') }}"
           class="w-8 h-8 rounded-xl bg-slate-100 flex items-center justify-center text-slate-500 hover:bg-slate-200 transition-colors text-lg">
            ‹
        </a>
        <h1 class="text-xl font-extrabold text-slate-900">{{ __('data.import_title') }}</h1>
    </div>

    @if($error)
        <div class="bg-red-50 border border-red-200 rounded-2xl p-4 mb-4">
            <p class="text-sm text-red-700">{{ $errorMessage }}</p>
        </div>
    @endif

    @if($success)
        <div class="bg-green-50 border border-green-200 rounded-2xl p-5 shadow-sm text-center">
            <p class="text-2xl mb-2">✓</p>
            <p class="text-sm font-semibold text-green-800">{{ __('data.import_success') }}</p>
            <p class="text-xs text-green-600 mt-1">{{ __('data.import_success_detail') }}</p>
            <a href="{{ route('home') }}"
               class="mt-4 inline-block bg-green-600 text-white font-semibold py-2 px-6 rounded-2xl text-sm">
                {{ __('data.import_back_home') }}
            </a>
        </div>
    @elseif($importing)
        <div class="bg-white rounded-2xl p-5 shadow-sm text-center">
            <p class="text-sm text-slate-600">{{ __('data.import_in_progress') }}</p>
        </div>
    @elseif($previewing)
        @php
            $labels = [
                'commercial_name'  => __('data.field_commercial_name'),
                'type'             => __('data.field_type'),
                'unit'             => __('data.field_unit'),
                'current_dose'     => __('data.field_current_dose'),
                'dose_morning'     => __('data.field_dose_morning'),
                'dose_noon'        => __('data.field_dose_noon'),
                'dose_evening'     => __('data.field_dose_evening'),
                'color'            => __('data.field_color'),
                'frequency_weeks'  => __('data.field_frequency_weeks'),
                'day_of_week'      => __('data.field_day_of_week'),
                'is_medical_act'   => __('data.field_is_medical_act'),
                'requires_fasting' => __('data.field_requires_fasting'),
                'archived_at'      => __('data.field_archived_at'),
            ];
            $totalTreatments = collect($previewData)->sum(fn($p) => count($p['treatments']));
            $exportDate = $exportedAt ? \Carbon\Carbon::parse($exportedAt)->isoFormat('D MMM YYYY [·] HH:mm') : '—';
        @endphp

        {{-- Summary --}}
        <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4 mb-4 text-sm text-slate-600">
            <p><span class="font-semibold text-slate-800">{{ __('data.import_exported_at') }}</span> {{ $exportDate }}</p>
            <p class="mt-1">
                <span class="font-semibold text-slate-800">{{ count($previewData) }}</span> {{ trans_choice('data.import_summary_profiles', count($previewData)) }},
                <span class="font-semibold text-slate-800">{{ $totalTreatments }}</span> {{ trans_choice('data.import_summary_treatments', $totalTreatments) }}
            </p>
        </div>

        {{-- Profiles list --}}
        <div class="space-y-4 mb-5">
            @foreach($previewData as $profile)
                <div class="bg-white rounded-2xl shadow-sm overflow-hidden">

                    {{-- Profile header --}}
                    <label class="flex items-center gap-3 p-4 cursor-pointer select-none">
                        <input type="checkbox"
                               wire:click="toggleProfile({{ $profile['old_id'] }})"
                               @checked(in_array($profile['old_id'], $selectedProfiles, true))
                               class="w-4 h-4 accent-blue-600 cursor-pointer flex-shrink-0">
                        <span class="w-3 h-3 rounded-full flex-shrink-0" style="background-color: {{ $profile['color'] }};"></span>
                        <span class="font-semibold text-slate-900 flex-1 text-sm">{{ $profile['name'] }}</span>
                        @if($profile['status'] === 'new')
                            <span class="bg-blue-100 text-blue-700 text-xs font-semibold px-2 py-0.5 rounded-full">{{ __('data.import_badge_new_profile') }}</span>
                        @endif
                    </label>

                    {{-- Treatments --}}
                    @if(count($profile['treatments']) > 0)
                        <div class="border-t border-slate-100 divide-y divide-slate-50">
                            @foreach($profile['treatments'] as $treatment)
                                @php
                                    $tKey = $profile['old_id'] . ':' . $treatment['name'];
                                    $badgeClass = match($treatment['status']) {
                                        'new'       => 'bg-green-100 text-green-700',
                                        'modified'  => 'bg-amber-100 text-amber-700',
                                        default     => 'bg-slate-100 text-slate-500',
                                    };
                                    $badgeLabel = match($treatment['status']) {
                                        'new'       => __('data.import_badge_new'),
                                        'modified'  => __('data.import_badge_modified'),
                                        default     => __('data.import_badge_identical'),
                                    };
                                @endphp
                                <div class="px-4 py-3">
                                    <label class="flex items-center gap-3 cursor-pointer select-none">
                                        <input type="checkbox"
                                               wire:click="toggleTreatment('{{ $tKey }}')"
                                               @checked(in_array($tKey, $selectedTreatments, true))
                                               class="w-4 h-4 accent-blue-600 cursor-pointer flex-shrink-0">
                                        <span class="text-sm text-slate-800 flex-1">{{ $treatment['name'] }}</span>
                                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full {{ $badgeClass }}">{{ $badgeLabel }}</span>
                                    </label>

                                    {{-- Before/after grid for modified treatments --}}
                                    @if($treatment['status'] === 'modified' && count($treatment['diff_fields']) > 0)
                                        <div class="mt-2 rounded-xl overflow-hidden border border-slate-100 text-xs">
                                            <div class="grid grid-cols-2 bg-slate-100 text-slate-500 font-semibold">
                                                <div class="px-3 py-1.5">{{ __('data.import_before') }}</div>
                                                <div class="px-3 py-1.5 border-l border-slate-200">{{ __('data.import_after') }}</div>
                                            </div>
                                            @foreach($treatment['diff_fields'] as $field)
                                                @php
                                                    $fieldLabel = $labels[$field] ?? $field;
                                                    $before = $treatment['current'][$field] ?? null;
                                                    $after  = $treatment['incoming'][$field] ?? null;
                                                @endphp
                                                <div class="grid grid-cols-2 border-t border-slate-100 bg-amber-50">
                                                    <div class="px-3 py-1.5">
                                                        <span class="text-slate-400 block text-[10px]">{{ $fieldLabel }}</span>
                                                        <span class="text-slate-700">{{ $before ?? '—' }}</span>
                                                    </div>
                                                    <div class="px-3 py-1.5 border-l border-amber-100">
                                                        <span class="text-slate-400 block text-[10px]">{{ $fieldLabel }}</span>
                                                        <span class="text-amber-800 font-semibold">{{ $after ?? '—' }}</span>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- Local-only treatments (collapsed) --}}
                    @if(count($profile['local_only']) > 0)
                        <details class="border-t border-slate-100">
                            <summary class="px-4 py-3 text-xs text-slate-400 cursor-pointer list-none flex items-center gap-1">
                                <span>▸</span>
                                <span>{{ trans_choice('data.import_local_only', count($profile['local_only']), ['count' => count($profile['local_only'])]) }}</span>
                            </summary>
                            <div class="divide-y divide-slate-50">
                                @foreach($profile['local_only'] as $localTreatment)
                                    <div class="px-4 py-2 flex items-center gap-2">
                                        <span class="text-sm text-slate-400">{{ $localTreatment['name'] }}</span>
                                        <span class="text-[10px] bg-slate-100 text-slate-400 px-1.5 py-0.5 rounded-full">{{ __('data.import_badge_local') }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </details>
                    @endif

                </div>
            @endforeach
        </div>

        {{-- Action buttons --}}
        <div class="space-y-3">
            <button wire:click="confirmImport"
                    wire:loading.attr="disabled"
                    wire:target="confirmImport"
                    @disabled(count($selectedTreatments) === 0)
                    class="w-full bg-blue-600 text-white font-semibold py-3 rounded-2xl text-sm disabled:opacity-50">
                {{ trans_choice('data.import_button', count($selectedTreatments), ['count' => count($selectedTreatments)]) }}
            </button>
            <button wire:click="cancelPreview"
                    class="w-full border border-slate-200 text-slate-600 font-medium py-3 rounded-2xl text-sm hover:bg-slate-50 transition-colors">
                {{ __('common.cancel') }}
            </button>
        </div>
    @else
        <div class="bg-white rounded-2xl p-5 shadow-sm">
            <p class="text-sm text-slate-600 mb-5">
                {!! __('data.import_intro') !!}
            </p>

            <button wire:click="pickFile"
                    wire:loading.attr="disabled"
                    class="w-full bg-blue-600 text-white font-semibold py-3 rounded-2xl disabled:opacity-50">
                @if($picking)
                    {{ __('data.import_picking') }}
                @else
                    <span wire:loading.remove wire:target="pickFile">{{ __('data.import_pick') }}</span>
                    <span wire:loading wire:target="pickFile">{{ __('data.import_opening') }}</span>
                @endif
            </button>
        </div>
    @endif

</div>
