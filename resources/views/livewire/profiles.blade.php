<div class="p-4 max-w-lg mx-auto">

    <div class="flex items-center justify-between mb-5">
        <div class="flex items-center gap-3">
            <a href="{{ route('settings') }}"
               class="w-8 h-8 rounded-xl bg-slate-100 flex items-center justify-center text-slate-500 hover:bg-slate-200 transition-colors text-lg">
                ‹
            </a>
            <h1 class="text-xl font-extrabold text-slate-900">Profils</h1>
        </div>
        <a href="{{ route('profiles.create') }}"
           class="px-3 py-1.5 rounded-xl text-white text-sm font-bold"
           style="background: linear-gradient(135deg, #0ea5e9, #6366f1);">+ Ajouter</a>
    </div>

    @foreach($active as $profile)
    <div class="bg-white rounded-2xl p-4 shadow-sm mb-3">
        @if($editingId === $profile->id)
            <input type="text" wire:model="editName"
                   class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm mb-3 focus:outline-none focus:ring-2 focus:ring-sky-400">
            @error('editName')<p class="text-xs text-red-500 mb-2">{{ $message }}</p>@enderror

            <div class="flex flex-wrap gap-2 mb-3">
                @foreach($colors as $hex)
                <button type="button" wire:click="$set('editColor', '{{ $hex }}')"
                        class="w-8 h-8 rounded-lg transition-all"
                        style="background-color: {{ $hex }};{{ $editColor === $hex ? ' box-shadow: 0 0 0 2px #fff, 0 0 0 4px #0f172a;' : '' }}"></button>
                @endforeach
            </div>

            <label class="block text-xs font-semibold text-slate-600 mb-1">Date de début</label>
            <div class="mb-2">
                <x-datepicker model="editStart" :value="$editStart" />
            </div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">Date de fin</label>
            <div class="mb-3">
                <x-datepicker model="editEnd" :value="$editEnd" />
            </div>
            @error('editEnd')<p class="text-xs text-red-500 mb-2">{{ $message }}</p>@enderror

            <div class="flex gap-2">
                <button wire:click="saveEdit" class="flex-1 py-2 rounded-lg text-white text-sm font-bold"
                        style="background: linear-gradient(135deg, #0ea5e9, #6366f1);">Enregistrer</button>
                <button wire:click="cancelEdit" class="flex-1 py-2 rounded-lg bg-slate-100 text-slate-700 text-sm font-semibold">Annuler</button>
            </div>
        @else
            <div class="flex items-center gap-3">
                <span class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold"
                      style="background-color: {{ $profile->color }};">{{ $profile->icon }}</span>
                <div class="flex-1">
                    <p class="font-bold text-slate-800">
                        {{ $profile->name }}
                        @if($profile->id === $activeId)<span class="text-xs text-sky-500 ml-1">● actif</span>@endif
                    </p>
                    @if($profile->treatment_start || $profile->treatment_end)
                    <p class="text-xs text-slate-500">
                        {{ $profile->treatment_start?->format('d/m/Y') ?? '—' }} → {{ $profile->treatment_end?->format('d/m/Y') ?? '—' }}
                    </p>
                    @else
                    <p class="text-xs text-slate-400 italic">Pas de période renseignée</p>
                    @endif
                </div>
            </div>
            <div class="flex gap-2 mt-3">
                <button wire:click="startEdit({{ $profile->id }})" class="flex-1 py-1.5 rounded-lg bg-slate-100 text-slate-700 text-xs font-semibold">Modifier</button>
                <button wire:click="archive({{ $profile->id }})" class="flex-1 py-1.5 rounded-lg bg-slate-100 text-slate-700 text-xs font-semibold">Archiver</button>
            </div>
        @endif
    </div>
    @endforeach

    @if($archived->isNotEmpty())
    <details class="mt-6">
        <summary class="text-sm font-semibold text-slate-500 cursor-pointer">Profils archivés ({{ $archived->count() }})</summary>
        @foreach($archived as $profile)
        <div class="bg-slate-50 rounded-2xl p-4 mt-3 flex items-center gap-3">
            <span class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold opacity-60"
                  style="background-color: {{ $profile->color }};">{{ $profile->icon }}</span>
            <p class="flex-1 text-sm text-slate-600">{{ $profile->name }}</p>
            <button wire:click="unarchive({{ $profile->id }})" class="text-xs font-semibold text-sky-500">Désarchiver</button>
        </div>
        @endforeach
    </details>
    @endif
</div>
