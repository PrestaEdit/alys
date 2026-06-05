<div class="relative">
    @if($active)
    <button wire:click="toggle"
            class="w-9 h-9 rounded-full flex items-center justify-center text-white font-bold text-base"
            style="background-color: {{ $active->color }}; box-shadow: 0 0 0 2.5px #fff, 0 0 0 4.5px {{ $active->color }}, 0 2px 6px rgba(0,0,0,.18);">
        {{ $active->icon }}
    </button>
    @else
    <a href="{{ route('onboarding') }}"
       class="w-9 h-9 rounded-full bg-slate-200 flex items-center justify-center text-slate-500 font-bold text-sm">
        ?
    </a>
    @endif

    @if($open)
    <button type="button"
            wire:click="$set('open', false)"
            class="fixed inset-0 z-40 cursor-default"
            aria-label="{{ __('profiles.close') }}"></button>

    <div class="absolute right-0 top-11 w-60 bg-white rounded-2xl z-50"
         style="box-shadow: 0 8px 30px rgba(0,0,0,.12), 0 1px 4px rgba(0,0,0,.06);">

        <div class="px-2 pt-2 pb-1">
            @foreach($profiles as $profile)
            <button wire:click="switchTo({{ $profile->id }})"
                    class="w-full flex items-center gap-3 px-2 py-2.5 rounded-xl hover:bg-slate-50 transition-colors text-left">
                <span class="w-8 h-8 rounded-full flex items-center justify-center text-white text-sm font-bold shrink-0"
                      style="background-color: {{ $profile->color }}; box-shadow: 0 1px 4px {{ $profile->color }}66;">
                    {{ $profile->icon }}
                </span>
                <span class="text-sm font-semibold text-slate-800 flex-1">{{ $profile->name }}</span>
                @if($active && $profile->id === $active->id)
                <span class="w-2 h-2 rounded-full shrink-0" style="background-color: {{ $active->color }};"></span>
                @endif
            </button>
            @endforeach
        </div>

        <div class="border-t border-slate-100 mx-2 mb-2">
            <a href="{{ route('profiles.create') }}"
               class="flex items-center gap-2 px-2 py-2.5 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors mt-1">
                <span class="w-7 h-7 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 text-base font-bold">+</span>
                {{ __('profiles.add_profile') }}
            </a>
            <a href="{{ route('profiles') }}"
               class="flex items-center gap-2 px-2 py-2 rounded-xl text-sm text-slate-400 hover:bg-slate-50 transition-colors">
                <span class="w-7 h-7 flex items-center justify-center text-slate-300 text-base">⚙</span>
                {{ __('profiles.manage_profiles') }}
            </a>
        </div>
    </div>
    @endif
</div>
