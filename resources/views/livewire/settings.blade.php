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

    {{-- Vibrations (haptics) --}}
    @php($hapticsOn = \App\Models\Setting::get('haptics_enabled', '1') === '1')
    <div class="bg-white rounded-2xl shadow-sm mb-4 overflow-hidden">
        <button wire:click="toggleHaptics"
                class="w-full p-5 text-left hover:bg-slate-50 transition-colors flex items-center gap-3">
            <div class="flex-1">
                <p class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">{{ __('settings.haptics') }}</p>
                <p class="text-sm text-slate-700">{{ __('settings.haptics_desc') }}</p>
            </div>
            <span aria-hidden="true"
                  class="relative inline-flex h-6 w-11 flex-shrink-0 rounded-full transition-colors
                         {{ $hapticsOn ? 'bg-sky-500' : 'bg-slate-300' }}">
                <span class="pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow transform transition-transform
                             {{ $hapticsOn ? 'translate-x-5' : 'translate-x-0.5' }} translate-y-0.5"></span>
            </span>
            <span class="sr-only">{{ $hapticsOn ? __('settings.haptics_on') : __('settings.haptics_off') }}</span>
        </button>
        @if(config('app.debug'))
        {{-- Bouton diagnostic (builds debug uniquement) : émet une vibration
             longue et affiche le retour de navigator.vibrate + l'état des
             capacités. Utile pour investiguer un rapport « ça ne vibre pas ». --}}
        <div class="border-t border-slate-100 px-5 py-3"
             x-data="{ status: null, vibrateResult: null }">
            <div class="flex items-center justify-between gap-3">
                <button type="button"
                        x-on:click="vibrateResult = window.alysHapticTest?.() || 'no-fn'; status = window.alysHapticStatus?.() || { hasApi: false, magicRegistered: false, enabled: false }"
                        class="text-xs font-semibold text-sky-500 hover:text-sky-600 transition-colors">
                    {{ __('settings.haptics_test') }}
                </button>
                <p class="text-[10px] font-mono text-slate-400 text-right"
                   x-show="status"
                   x-text="'api:' + (status.hasApi ? 'ok' : 'ko') + ' · magic:' + (status.magicRegistered ? 'ok' : 'ko') + ' · on:' + (status.enabled ? 'ok' : 'ko')"></p>
            </div>
            <p class="text-[10px] font-mono text-slate-400 mt-1"
               x-show="vibrateResult"
               x-text="'navigator.vibrate → ' + vibrateResult"></p>
        </div>
        @endif
    </div>

    @if(config('app.debug'))
    <button wire:click="diagNotifications"
            class="w-full bg-white rounded-2xl p-4 shadow-sm mb-4 text-left hover:bg-slate-50 transition-colors border border-dashed border-slate-300">
        <p class="text-xs font-bold text-slate-400 uppercase tracking-wide">{{ __('settings.diag_notifications') }}</p>
    </button>
    @endif

    <p class="text-center text-xs text-slate-300 mt-2">v{{ config('nativephp.version') }}</p>

    <div class="text-xs text-slate-400 mt-4">
        <p>{{ __('settings.attributions') }} :</p>
        <ul class="list-disc list-inside mt-1">
            <li>{{ __('settings.attributions_medical') }} : <a href="https://healthicons.org" class="underline">healthicons.org</a> (MIT)</li>
            <li>{{ __('settings.attributions_emoji') }} : Twemoji (jdecked, CC-BY 4.0)</li>
        </ul>
    </div>

</div>
