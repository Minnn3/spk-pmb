@php
    $toasts = [
        ['key' => 'toast_success', 'class' => 'toast-success', 'icon' => 'CheckCircle2'],
        ['key' => 'toast_danger', 'class' => 'toast-danger', 'icon' => 'XCircle'],
        ['key' => 'toast_warning', 'class' => 'toast-warning', 'icon' => 'AlertTriangle'],
        ['key' => 'toast_info', 'class' => 'toast-info', 'icon' => 'Info'],
    ];
@endphp

<div class="toast-container" aria-live="polite">
    @foreach($toasts as $t)
        @if(session()->has($t['key']))
            <div class="toast {{ $t['class'] }}" data-toast role="status">
                <x-icon :name="$t['icon']" :size="16" :strokeWidth="2" />
                <div class="toast-message">{{ session($t['key']) }}</div>
                <button type="button" class="toast-close" data-toast-close aria-label="Tutup notifikasi">
                    <x-icon name="X" :size="14" :strokeWidth="2" />
                </button>
            </div>
        @endif
    @endforeach
</div>

