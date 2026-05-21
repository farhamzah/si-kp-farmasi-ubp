@props([
    'variant' => 'primary',
    'type' => 'button',
])

@php
    $classes = [
        'primary' => 'si-btn si-btn-primary',
        'secondary' => 'si-btn si-btn-secondary',
        'danger' => 'si-btn si-btn-danger',
        'ghost' => 'si-btn text-slate-600 hover:bg-slate-100 focus:ring-slate-100',
    ][$variant] ?? 'si-btn si-btn-primary';
@endphp

<button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</button>
