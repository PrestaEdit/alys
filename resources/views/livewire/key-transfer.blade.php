<div class="p-4 max-w-lg mx-auto">

    <div class="flex items-center gap-3 mb-5">
        <a href="{{ route('settings') }}"
           class="w-8 h-8 rounded-xl bg-slate-100 flex items-center justify-center text-slate-500 hover:bg-slate-200 transition-colors text-lg">
            ‹
        </a>
        <h1 class="text-xl font-extrabold text-slate-900">{{ __('data.key_title') }}</h1>
    </div>

    @if($error)
        <div class="bg-red-50 border border-red-200 rounded-2xl p-4 mb-4 text-sm text-red-700">
            {{ $error }}
        </div>
    @endif

    @if($importSuccess)
        <div class="bg-green-50 border border-green-200 rounded-2xl p-4 mb-4 text-sm text-green-700">
            {{ __('data.key_import_success') }}
        </div>
    @endif

    @if($confirmReplace)
        <div class="bg-amber-50 border border-amber-300 rounded-2xl p-5 mb-4">
            <p class="text-sm font-semibold text-amber-800 mb-3">
                {{ __('data.key_confirm_replace') }}
            </p>
            <div class="flex gap-3">
                <button wire:click="confirmReplaceKeys"
                        class="flex-1 bg-amber-600 text-white font-semibold py-2 rounded-xl text-sm">
                    {{ __('data.key_replace') }}
                </button>
                <button wire:click="cancelReplace"
                        class="flex-1 bg-white border border-amber-300 text-amber-700 font-semibold py-2 rounded-xl text-sm">
                    {{ __('common.cancel') }}
                </button>
            </div>
        </div>
    @endif

    <div class="bg-white rounded-2xl p-5 shadow-sm mb-4">
        <p class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">{{ __('data.key_source_device') }}</p>
        <p class="text-sm text-slate-600 mb-4">
            {{ __('data.key_source_intro') }}
        </p>

        <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 mb-4">
            <p class="text-xs text-amber-700">
                {{ __('data.key_warning') }}
            </p>
        </div>

        <button wire:click="showQr"
                class="w-full bg-slate-800 text-white font-semibold py-3 rounded-2xl text-sm">
            {{ __('data.key_show_qr') }}
        </button>

        @if($qrContent)
            <div class="mt-4 flex justify-center"
                 x-data
                 x-init="QRCode.toCanvas($refs.qrCanvas, @js($qrContent), { width: 224, margin: 1 })">
                <canvas x-ref="qrCanvas" class="rounded-xl"></canvas>
            </div>
        @endif
    </div>

    <div class="bg-white rounded-2xl p-5 shadow-sm">
        <p class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">{{ __('data.key_new_device') }}</p>
        <p class="text-sm text-slate-600 mb-4">
            {{ __('data.key_new_intro') }}
        </p>

        <button wire:click="startScan"
                class="w-full bg-blue-600 text-white font-semibold py-3 rounded-2xl text-sm">
            {{ __('data.key_scan') }}
        </button>
    </div>

</div>
