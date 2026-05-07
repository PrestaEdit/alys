<div class="p-4 max-w-lg mx-auto">

    <div class="flex items-center gap-3 mb-5">
        <a href="{{ route('settings') }}"
           class="w-8 h-8 rounded-xl bg-slate-100 flex items-center justify-center text-slate-500 hover:bg-slate-200 transition-colors text-lg">
            ‹
        </a>
        <h1 class="text-xl font-extrabold text-slate-900">Transfert de clés</h1>
    </div>

    @if($error)
        <div class="bg-red-50 border border-red-200 rounded-2xl p-4 mb-4 text-sm text-red-700">
            {{ $error }}
        </div>
    @endif

    @if($importSuccess)
        <div class="bg-green-50 border border-green-200 rounded-2xl p-4 mb-4 text-sm text-green-700">
            Clés importées avec succès. Vos exports seront maintenant déchiffrables sur cet appareil.
        </div>
    @endif

    @if($confirmReplace)
        <div class="bg-amber-50 border border-amber-300 rounded-2xl p-5 mb-4">
            <p class="text-sm font-semibold text-amber-800 mb-3">
                Des clés existent déjà sur cet appareil. Les remplacer rendra illisibles les exports précédents chiffrés avec ces clés. Confirmer ?
            </p>
            <div class="flex gap-3">
                <button wire:click="confirmReplaceKeys"
                        class="flex-1 bg-amber-600 text-white font-semibold py-2 rounded-xl text-sm">
                    Remplacer
                </button>
                <button wire:click="cancelReplace"
                        class="flex-1 bg-white border border-amber-300 text-amber-700 font-semibold py-2 rounded-xl text-sm">
                    Annuler
                </button>
            </div>
        </div>
    @endif

    <div class="bg-white rounded-2xl p-5 shadow-sm mb-4">
        <p class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">Appareil source</p>
        <p class="text-sm text-slate-600 mb-4">
            Générez le QR code de votre clé privée, puis scannez-le depuis votre nouvel appareil pour lui transférer l'accès.
        </p>

        <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 mb-4">
            <p class="text-xs text-amber-700">
                ⚠️ Ce QR code donne accès à tous vos exports. Ne le montrez qu'à votre nouvel appareil.
            </p>
        </div>

        <button wire:click="showQr"
                class="w-full bg-slate-800 text-white font-semibold py-3 rounded-2xl text-sm">
            Afficher le QR code de ma clé
        </button>

        @if($qrDataUri)
            <div class="mt-4 flex justify-center">
                <img src="{{ $qrDataUri }}" alt="QR code clé privée" class="w-56 h-56 rounded-xl">
            </div>
        @endif
    </div>

    <div class="bg-white rounded-2xl p-5 shadow-sm">
        <p class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">Nouvel appareil</p>
        <p class="text-sm text-slate-600 mb-4">
            Scannez le QR code affiché sur votre ancien appareil pour récupérer les clés de chiffrement.
        </p>

        <button wire:click="startScan"
                class="w-full bg-blue-600 text-white font-semibold py-3 rounded-2xl text-sm">
            Scanner le QR code d'un autre appareil
        </button>
    </div>

</div>
