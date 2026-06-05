<div class="p-4 max-w-lg mx-auto">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-5">
        <div class="flex items-center gap-3">
            <a href="{{ route('home') }}"
               class="w-8 h-8 rounded-xl bg-slate-100 flex items-center justify-center text-slate-500 hover:bg-slate-200 transition-colors text-lg">
                ‹
            </a>
            <h1 class="text-xl font-extrabold text-slate-900">{{ __('settings.title') }}</h1>
        </div>
        <livewire:profile-switcher />
    </div>

    <a href="{{ route('profiles') }}"
       class="block bg-white rounded-2xl p-5 shadow-sm mb-4 hover:bg-slate-50 transition-colors">
        <p class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">{{ __('settings.profiles') }}</p>
        <p class="text-sm text-slate-700">{{ __('settings.profiles_desc') }}</p>
    </a>

    <a href="{{ route('import') }}"
       class="block bg-white rounded-2xl p-5 shadow-sm mb-4 hover:bg-slate-50 transition-colors">
        <p class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">{{ __('settings.import') }}</p>
        <p class="text-sm text-slate-700">{{ __('settings.import_desc') }}</p>
    </a>

    <a href="{{ route('key-transfer') }}"
       class="block bg-white rounded-2xl p-5 shadow-sm mb-4 hover:bg-slate-50 transition-colors">
        <p class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">{{ __('settings.key_transfer') }}</p>
        <p class="text-sm text-slate-700">{{ __('settings.key_transfer_desc') }}</p>
    </a>

    <button wire:click="enableNotifications"
            class="w-full bg-white rounded-2xl p-5 shadow-sm mb-4 text-left hover:bg-slate-50 transition-colors">
        <p class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">{{ __('settings.notifications') }}</p>
        <p class="text-sm text-slate-700">{{ __('settings.notifications_desc') }}</p>
    </button>

    {{-- Sélecteur de langue --}}
    <div class="bg-white rounded-2xl p-5 shadow-sm mb-4">
        <p class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-3">{{ __('settings.language') }}</p>
        <div class="flex gap-2">
            @foreach (['fr' => __('settings.language_fr'), 'en' => __('settings.language_en')] as $code => $label)
                <button wire:click="setLocale('{{ $code }}')"
                        class="flex-1 rounded-xl py-2 text-sm font-semibold transition-colors
                               {{ app()->getLocale() === $code ? 'bg-sky-500 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </div>

    @if(config('app.debug'))
    <button wire:click="diagNotifications"
            class="w-full bg-white rounded-2xl p-4 shadow-sm mb-4 text-left hover:bg-slate-50 transition-colors border border-dashed border-slate-300">
        <p class="text-xs font-bold text-slate-400 uppercase tracking-wide">{{ __('settings.diag_notifications') }}</p>
    </button>
    @endif

    <p class="text-center text-xs text-slate-300 mt-2">v{{ config('nativephp.version') }}</p>

</div>
