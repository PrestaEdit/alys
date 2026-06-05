<div class="p-4 max-w-lg mx-auto">

    <div class="flex items-center gap-3 mb-5">
        <a href="{{ route('profiles') }}"
           class="w-8 h-8 rounded-xl bg-slate-100 flex items-center justify-center text-slate-500 hover:bg-slate-200 transition-colors text-lg">
            ‹
        </a>
        <h1 class="text-xl font-extrabold text-slate-900">{{ __('profiles.title_create') }}</h1>
    </div>

    <div class="bg-white rounded-2xl p-5 shadow-sm mb-4">
        <label class="block text-xs font-semibold text-slate-600 mb-1.5">{{ __('profiles.first_name') }}</label>
        <input type="text" wire:model="name" placeholder="{{ __('profiles.first_name_placeholder') }}"
               class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-sky-400">
        @error('name')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
    </div>

    <div class="bg-white rounded-2xl p-5 shadow-sm mb-4">
        <p class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-3">{{ __('profiles.color') }}</p>
        <div class="flex flex-wrap gap-3">
            @foreach($colors as $hex)
            <button type="button" wire:click="$set('color', '{{ $hex }}')"
                    class="w-10 h-10 rounded-xl transition-all"
                    style="background-color: {{ $hex }};{{ $color === $hex ? ' box-shadow: 0 0 0 2px #fff, 0 0 0 5px #0f172a;' : '' }}"></button>
            @endforeach
        </div>
        @error('color')<p class="text-xs text-red-500 mt-2">{{ $message }}</p>@enderror
    </div>

    <div class="bg-white rounded-2xl p-5 shadow-sm mb-4">
        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Date de début</label>
        <x-datepicker model="treatmentStart" :value="$treatmentStart" />
        @error('treatmentStart')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror

        <div class="mt-3">
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Date de fin</label>
            <x-datepicker model="treatmentEnd" :value="$treatmentEnd" />
            @error('treatmentEnd')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
        </div>
    </div>

    <button wire:click="save"
            class="w-full py-3 rounded-xl text-sm font-bold text-white transition-colors hover:opacity-90"
            style="background: linear-gradient(135deg, #0ea5e9, #6366f1);">
        {{ __('profiles.create_profile') }}
    </button>
</div>
