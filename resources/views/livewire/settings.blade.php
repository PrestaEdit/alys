<div class="p-4 max-w-lg mx-auto">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-5">
        <div class="flex items-center gap-3">
            <a href="{{ route('home') }}"
               class="w-8 h-8 rounded-xl bg-slate-100 flex items-center justify-center text-slate-500 hover:bg-slate-200 transition-colors text-lg">
                ‹
            </a>
            <h1 class="text-xl font-extrabold text-slate-900">Paramètres</h1>
        </div>
        <livewire:profile-switcher />
    </div>

    <a href="{{ route('profiles') }}"
       class="block bg-white rounded-2xl p-5 shadow-sm mb-4 hover:bg-slate-50 transition-colors">
        <p class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">Profils</p>
        <p class="text-sm text-slate-700">Gérer les profils, les couleurs et les périodes de traitement.</p>
    </a>

</div>
