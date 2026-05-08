<div class="p-4 max-w-lg mx-auto">

    <div class="flex items-center gap-3 mb-5">
        <a href="{{ route('settings') }}"
           class="w-8 h-8 rounded-xl bg-slate-100 flex items-center justify-center text-slate-500 hover:bg-slate-200 transition-colors text-lg">
            ‹
        </a>
        <h1 class="text-xl font-extrabold text-slate-900">Importer</h1>
    </div>

    @if($error)
        <div class="bg-red-50 border border-red-200 rounded-2xl p-4 mb-4">
            <p class="text-sm text-red-700">{{ $errorMessage }}</p>
        </div>
    @endif

    @if($success)
        <div class="bg-green-50 border border-green-200 rounded-2xl p-5 shadow-sm text-center">
            <p class="text-2xl mb-2">✓</p>
            <p class="text-sm font-semibold text-green-800">Importation réussie !</p>
            <p class="text-xs text-green-600 mt-1">Votre calendrier de traitement a été restauré.</p>
            <a href="{{ route('home') }}"
               class="mt-4 inline-block bg-green-600 text-white font-semibold py-2 px-6 rounded-2xl text-sm">
                Retour à l'accueil
            </a>
        </div>
    @elseif($importing)
        <div class="bg-white rounded-2xl p-5 shadow-sm text-center">
            <p class="text-sm text-slate-600">Importation en cours…</p>
        </div>
    @else
        <div class="bg-white rounded-2xl p-5 shadow-sm">
            <p class="text-sm text-slate-600 mb-5">
                Sélectionnez votre fichier <strong>.alys</strong> depuis votre gestionnaire de fichiers ou
                messagerie.
            </p>

            <button wire:click="pickFile"
                    wire:loading.attr="disabled"
                    class="w-full bg-blue-600 text-white font-semibold py-3 rounded-2xl disabled:opacity-50">
                @if($picking)
                    Sélection en cours…
                @else
                    <span wire:loading.remove wire:target="pickFile">Sélectionner un fichier .alys</span>
                    <span wire:loading wire:target="pickFile">Ouverture…</span>
                @endif
            </button>
        </div>
    @endif

</div>
