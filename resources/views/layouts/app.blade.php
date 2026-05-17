<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>{{ $title ?? 'Alys' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <style>
        :root { --safe-top: 0px; --safe-bottom: 0px; }
    </style>
    <script>
        (function () {
            // Mesure réelle de env(safe-area-inset-*) via un élément test
            var probe = document.createElement('div');
            probe.style.cssText = 'position:fixed;top:0;left:0;width:0;pointer-events:none;visibility:hidden;' +
                'height:env(safe-area-inset-top,0px);';
            document.documentElement.appendChild(probe);

            function apply() {
                var top = probe.getBoundingClientRect().height;
                // Si CSS env() ne retourne rien (0), on tente window.visualViewport
                // ou on applique un minimum raisonnable pour les encoches Android
                if (top === 0) {
                    var vvOffset = (window.visualViewport && window.visualViewport.offsetTop) || 0;
                    top = vvOffset > 0 ? vvOffset : 48; // 48 px = encoche standard Android
                }
                document.documentElement.style.setProperty('--safe-top', top + 'px');

                // Barre de navigation bas (Android gesture bar)
                var probeBottom = document.createElement('div');
                probeBottom.style.cssText = 'position:fixed;bottom:0;left:0;width:0;pointer-events:none;visibility:hidden;' +
                    'height:env(safe-area-inset-bottom,0px);';
                document.documentElement.appendChild(probeBottom);
                var bottom = probeBottom.getBoundingClientRect().height;
                document.documentElement.removeChild(probeBottom);
                document.documentElement.style.setProperty('--safe-bottom', bottom + 'px');
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', apply);
            } else {
                apply();
            }
        })();
    </script>
</head>
<body class="h-full bg-slate-50 font-sans antialiased">

    @php($hideBottomNav = request()->routeIs('onboarding'))

    <main class="min-h-full {{ $hideBottomNav ? '' : 'pb-20' }}" style="padding-top: var(--safe-top);">
        {{ $slot }}
    </main>

    @unless($hideBottomNav)
    <nav class="fixed bottom-0 left-0 right-0 bg-white border-t border-slate-200 z-50"
         style="padding-bottom: var(--safe-bottom);">
        <div class="flex justify-around items-center h-16 max-w-lg mx-auto px-4">

            <a href="{{ route('home') }}"
               class="flex flex-col items-center gap-1 px-3 py-2 rounded-xl transition-colors
                      {{ request()->routeIs('home') ? 'text-sky-500' : 'text-slate-400 hover:text-slate-600' }}">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/>
                </svg>
                <span class="text-xs font-medium">Accueil</span>
            </a>

            <a href="{{ route('calendar') }}"
               class="flex flex-col items-center gap-1 px-3 py-2 rounded-xl transition-colors
                      {{ request()->routeIs('calendar') ? 'text-sky-500' : 'text-slate-400 hover:text-slate-600' }}">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                </svg>
                <span class="text-xs font-medium">Calendrier</span>
            </a>

            <a href="{{ route('treatments') }}"
               class="flex flex-col items-center gap-1 px-3 py-2 rounded-xl transition-colors
                      {{ request()->routeIs('treatments*') ? 'text-sky-500' : 'text-slate-400 hover:text-slate-600' }}">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h4a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/>
                </svg>
                <span class="text-xs font-medium">Traitements</span>
            </a>

            <a href="{{ route('settings') }}"
               class="flex flex-col items-center gap-1 px-3 py-2 rounded-xl transition-colors
                      {{ request()->routeIs('settings') ? 'text-sky-500' : 'text-slate-400 hover:text-slate-600' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span class="text-xs font-medium">Réglages</span>
            </a>

        </div>
    </nav>
    @endunless

    {{-- Toast global (hors Livewire pour ne pas être réinitialisé au re-render) --}}
    <div
        x-data="{ show: false, message: '' }"
        x-on:toast.window="message = $event.detail.message; show = true; setTimeout(() => show = false, 3000)"
        x-show="show"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-4"
        class="fixed inset-x-4 z-[200] max-w-sm mx-auto pointer-events-none"
        style="display:none; bottom: calc(4rem + var(--safe-bottom) + 0.5rem);"
    >
        <div class="bg-emerald-500 text-white rounded-2xl px-4 py-3 shadow-xl flex items-center gap-3">
            <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            <p class="text-sm font-semibold" x-text="message"></p>
        </div>
    </div>

    @livewireScripts
    <script>
        document.body.style.overscrollBehavior = 'none';
    </script>
</body>
</html>
