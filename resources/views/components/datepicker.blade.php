@props([
    'model',
    'placeholder' => null,
    'value'       => null,
])

@php
    $placeholder ??= __('components.date_placeholder');
@endphp

@php
$dpOptions = [
    'selectedTheme' => 'light',
    'dateFormat'    => 'DD/MM/YYYY',
];
if ($value) {
    $dpOptions['selectedDates'] = [$value];
}
@endphp

<div
    class="hs-datepicker relative"
    data-hs-datepicker='@json($dpOptions)'
    data-livewire-model="{{ $model }}"
>
    <input
        type="text"
        placeholder="{{ $placeholder }}"
        class="w-full rounded-xl border border-slate-200 px-3 py-2.5 pe-10 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-400"
        autocomplete="off"
        readonly
    >
    <div class="absolute inset-y-0 end-0 flex items-center pe-3 pointer-events-none">
        <svg class="w-4 h-4 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
            <path d="M20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1h-3V1a1 1 0 0 0-2 0v1H6V1a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v2h20V4ZM0 18a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8H0v10Zm5-8h10a1 1 0 0 1 0 2H5a1 1 0 0 1 0-2Z"/>
        </svg>
    </div>
</div>
