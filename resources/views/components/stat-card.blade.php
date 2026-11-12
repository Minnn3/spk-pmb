@props([
    'label',
    'value',
    'subtext' => null,
    'accent' => 'default',
])

@php
    $accentClass = match ($accent) {
        'blue' => 'stat-accent-blue',
        'green' => 'stat-accent-green',
        'amber' => 'stat-accent-amber',
        'red' => 'stat-accent-red',
        default => '',
    };
@endphp

<div class="stat-card">
    <div class="stat-label">{{ $label }}</div>
    <div class="stat-value">{{ $value }}</div>
    @if($subtext !== null && $subtext !== '')
        <div class="stat-subtext">{{ $subtext }}</div>
    @endif
    <div class="stat-accent-line {{ $accentClass }}"></div>
</div>

