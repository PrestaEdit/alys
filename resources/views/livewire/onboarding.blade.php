<div class="p-4 max-w-lg mx-auto">

    <div class="text-center mb-6">
        <img src="{{ asset('icon.png') }}"
             alt=""
             class="w-20 h-20 mx-auto mb-3 rounded-2xl shadow-sm">
        <h1 class="text-2xl font-extrabold text-slate-900">{{ __('onboarding.welcome') }}</h1>
        <p class="text-sm text-slate-500 mt-1">{{ __('onboarding.step_of', ['current' => $step, 'total' => 4]) }}</p>
    </div>

    {{-- Step 1: prénom --}}
    @if($step === 1)
    <div class="bg-white rounded-2xl p-5 shadow-sm mb-4">
        <p class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">{{ __('onboarding.step1_title') }}</p>
        <p class="text-xs text-slate-500 mb-4">{{ __('onboarding.step1_help') }}</p>
        <input type="text"
               wire:model="patientName"
               placeholder="{{ __('onboarding.first_name_placeholder') }}"
               class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-400">
        @error('patientName')
        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
        @enderror
    </div>

    <button wire:click="nextStep"
            class="w-full py-3 rounded-xl text-sm font-bold text-white transition-colors hover:opacity-90"
            style="background: linear-gradient(135deg, #0ea5e9, #6366f1);">
        {{ __('onboarding.next') }}
    </button>
    @endif

    {{-- Step 2: couleur --}}
    @if($step === 2)
    <div class="bg-white rounded-2xl p-5 shadow-sm mb-4">
        <p class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">{{ __('onboarding.step2_title') }}</p>
        <p class="text-xs text-slate-500 mb-4">{{ __('onboarding.step2_help') }}</p>
        <div class="flex flex-wrap gap-3">
            @foreach($colors as $hex)
            <button type="button"
                    wire:click="$set('color', '{{ $hex }}')"
                    class="w-10 h-10 rounded-xl transition-all"
                    style="background-color: {{ $hex }};{{ $color === $hex ? ' box-shadow: 0 0 0 2px #fff, 0 0 0 5px #0f172a;' : '' }}"></button>
            @endforeach
        </div>
        @error('color')<p class="text-xs text-red-500 mt-2">{{ $message }}</p>@enderror
    </div>

    <div class="flex gap-2">
        <button wire:click="previousStep"
                class="flex-1 py-3 rounded-xl text-sm font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 transition-colors">
            {{ __('common.back') }}
        </button>
        <button wire:click="nextStep"
                class="flex-1 py-3 rounded-xl text-sm font-bold text-white transition-colors hover:opacity-90"
                style="background: linear-gradient(135deg, #0ea5e9, #6366f1);">
            {{ __('onboarding.next') }}
        </button>
    </div>
    @endif

    {{-- Step 3: dates --}}
    @if($step === 3)
    <div class="bg-white rounded-2xl p-5 shadow-sm mb-4">
        <p class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">{{ __('onboarding.step3_title') }}</p>
        <p class="text-xs text-slate-500 mb-4">{{ __('onboarding.step3_help') }}</p>

        <div class="mb-4">
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">{{ __('onboarding.start_date') }}</label>
            <x-datepicker model="treatmentStart" :value="$treatmentStart" />
            @error('treatmentStart')
            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-1">
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">{{ __('onboarding.end_date') }}</label>
            <x-datepicker model="treatmentEnd" :value="$treatmentEnd" />
            @error('treatmentEnd')
            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="flex gap-2">
        <button wire:click="previousStep"
                class="flex-1 py-3 rounded-xl text-sm font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 transition-colors">
            {{ __('common.back') }}
        </button>
        <button wire:click="nextStep"
                class="flex-1 py-3 rounded-xl text-sm font-bold text-white transition-colors hover:opacity-90"
                style="background: linear-gradient(135deg, #0ea5e9, #6366f1);">
            {{ __('onboarding.next') }}
        </button>
    </div>
    @endif

    {{-- Step 4: traitements --}}
    @if($step === 4)
    <div class="bg-white rounded-2xl p-5 shadow-sm mb-4">
        <p class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">{{ __('onboarding.step4_title') }}</p>
        <p class="text-sm text-slate-600">{{ __('onboarding.step4_help') }}</p>
    </div>

    <div class="flex flex-col gap-2">
        <button wire:click="completeAndAddTreatment"
                class="w-full py-3 rounded-xl text-sm font-bold text-white transition-colors hover:opacity-90"
                style="background: linear-gradient(135deg, #0ea5e9, #6366f1);">
            {{ __('onboarding.add_treatment') }}
        </button>
        <button wire:click="complete"
                class="w-full py-3 rounded-xl text-sm font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 transition-colors">
            {{ __('onboarding.later') }}
        </button>
        <button wire:click="previousStep"
                class="w-full py-2 text-xs font-semibold text-slate-500 hover:text-slate-700">
            ← {{ __('common.back') }}
        </button>
    </div>
    @endif

</div>
