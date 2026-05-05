<div class="p-4 max-w-lg mx-auto">

    <div class="text-center mb-6">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl mb-3"
             style="background: linear-gradient(135deg, #0ea5e9, #6366f1);">
            <span class="text-3xl">🌸</span>
        </div>
        <h1 class="text-2xl font-extrabold text-slate-900">Bienvenue</h1>
        <p class="text-sm text-slate-500 mt-1">Étape {{ $step }} sur 3</p>
    </div>

    {{-- Step 1: prénom --}}
    @if($step === 1)
    <div class="bg-white rounded-2xl p-5 shadow-sm mb-4">
        <p class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-4">Quel est votre prénom ?</p>
        <input type="text"
               wire:model="patientName"
               placeholder="Alys"
               class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-400">
        @error('patientName')
        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
        @enderror
    </div>

    <button wire:click="nextStep"
            class="w-full py-3 rounded-xl text-sm font-bold text-white transition-colors hover:opacity-90"
            style="background: linear-gradient(135deg, #0ea5e9, #6366f1);">
        Suivant
    </button>
    @endif

    {{-- Step 2: dates --}}
    @if($step === 2)
    <div class="bg-white rounded-2xl p-5 shadow-sm mb-4">
        <p class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-4">Période de traitement</p>

        <div class="mb-4">
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Date de début</label>
            <input type="date"
                   wire:model="treatmentStart"
                   class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-400">
            @error('treatmentStart')
            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-1">
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Date de fin</label>
            <input type="date"
                   wire:model="treatmentEnd"
                   class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-400">
            @error('treatmentEnd')
            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="flex gap-2">
        <button wire:click="previousStep"
                class="flex-1 py-3 rounded-xl text-sm font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 transition-colors">
            Retour
        </button>
        <button wire:click="nextStep"
                class="flex-1 py-3 rounded-xl text-sm font-bold text-white transition-colors hover:opacity-90"
                style="background: linear-gradient(135deg, #0ea5e9, #6366f1);">
            Suivant
        </button>
    </div>
    @endif

    {{-- Step 3: traitements --}}
    @if($step === 3)
    <div class="bg-white rounded-2xl p-5 shadow-sm mb-4">
        <p class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">Vos traitements</p>
        <p class="text-sm text-slate-600">Voulez-vous ajouter vos traitements maintenant ? Vous pourrez aussi le faire plus tard depuis l'application.</p>
    </div>

    <div class="flex flex-col gap-2">
        <button wire:click="completeAndAddTreatment"
                class="w-full py-3 rounded-xl text-sm font-bold text-white transition-colors hover:opacity-90"
                style="background: linear-gradient(135deg, #0ea5e9, #6366f1);">
            Ajouter un traitement
        </button>
        <button wire:click="complete"
                class="w-full py-3 rounded-xl text-sm font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 transition-colors">
            Plus tard
        </button>
        <button wire:click="previousStep"
                class="w-full py-2 text-xs font-semibold text-slate-500 hover:text-slate-700">
            ← Retour
        </button>
    </div>
    @endif

</div>
