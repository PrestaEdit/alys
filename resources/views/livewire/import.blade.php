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

    <div class="bg-white rounded-2xl p-5 shadow-sm">
        <p class="text-sm text-slate-600 mb-4">
            Sélectionnez un fichier <strong>.alys</strong> exporté depuis cet appareil
            (ou depuis un appareil dont vous avez transféré les clés).
        </p>

        <label class="block">
            <span class="sr-only">Fichier .alys</span>
            <input type="file"
                   wire:model="file"
                   accept=".alys"
                   class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
        </label>

        <button wire:click="import"
                wire:loading.attr="disabled"
                class="mt-4 w-full bg-blue-600 text-white font-semibold py-3 rounded-2xl disabled:opacity-50">
            <span wire:loading.remove>Importer</span>
            <span wire:loading>Importation…</span>
        </button>
    </div>

</div>
