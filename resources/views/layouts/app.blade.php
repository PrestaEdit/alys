<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>{{ $title ?? 'Alexis' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full bg-slate-50 font-sans antialiased">

    <main class="min-h-full pb-20">
        {{ $slot }}
    </main>

    <nav class="fixed bottom-0 left-0 right-0 bg-white border-t border-slate-200 z-50">
        <div class="flex justify-around items-center h-16 max-w-lg mx-auto px-4">

            <a href="{{ route('home') }}"
               class="flex flex-col items-center gap-1 px-4 py-2 rounded-xl transition-colors
                      {{ request()->routeIs('home') ? 'text-sky-500' : 'text-slate-400 hover:text-slate-600' }}">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/>
                </svg>
                <span class="text-xs font-medium">Accueil</span>
            </a>

            <a href="{{ route('calendar') }}"
               class="flex flex-col items-center gap-1 px-4 py-2 rounded-xl transition-colors
                      {{ request()->routeIs('calendar') ? 'text-sky-500' : 'text-slate-400 hover:text-slate-600' }}">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                </svg>
                <span class="text-xs font-medium">Calendrier</span>
            </a>

            <a href="{{ route('treatments') }}"
               class="flex flex-col items-center gap-1 px-4 py-2 rounded-xl transition-colors
                      {{ request()->routeIs('treatments*') ? 'text-sky-500' : 'text-slate-400 hover:text-slate-600' }}">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h4a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/>
                </svg>
                <span class="text-xs font-medium">Traitements</span>
            </a>

        </div>
    </nav>

    @livewireScripts
    <script>
        document.body.style.overscrollBehavior = 'none';
    </script>
</body>
</html>
