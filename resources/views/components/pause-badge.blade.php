@props(['treatment'])

@php
    $today = \Carbon\Carbon::today();
    $fmt = fn ($d) => $d->locale(app()->getLocale())->isoFormat('D MMM');
    $label = null;

    if ($treatment->hasPendingPause()) {
        $from  = $treatment->paused_from;
        $until = $treatment->paused_until;
        if ($from->lte($today)) {
            $label = $until
                ? __('treatments.pause_badge_until', ['date' => $fmt($until)])
                : __('treatments.pause_badge');
        } else {
            $label = $until
                ? __('treatments.pause_badge_scheduled', ['from' => $fmt($from), 'until' => $fmt($until)])
                : __('treatments.pause_badge_scheduled_open', ['from' => $fmt($from)]);
        }
    } elseif ($treatment->parent_treatment_id && $treatment->parentTreatment?->hasPendingPause()) {
        $label = __('treatments.pause_by_parent', ['name' => $treatment->parentTreatment->displayName()]);
    }
@endphp

@if($label)
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full bg-amber-50 text-amber-700']) }}>
    <svg class="w-3 h-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
        <path d="M6 4a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h1.5a1 1 0 0 0 1-1V5a1 1 0 0 0-1-1H6zm6.5 0a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1H14a1 1 0 0 0 1-1V5a1 1 0 0 0-1-1h-1.5z"/>
    </svg>
    {{ $label }}
</span>
@endif
