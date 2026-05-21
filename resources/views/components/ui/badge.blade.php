@props([
    'variant' => 'slate',
])

@php
    $classes = [
        'success' => 'si-badge bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100',
        'warning' => 'si-badge bg-amber-50 text-amber-700 ring-1 ring-amber-100',
        'danger' => 'si-badge bg-rose-50 text-rose-700 ring-1 ring-rose-100',
        'info' => 'si-badge bg-sky-50 text-sky-700 ring-1 ring-sky-100',
        'primary' => 'si-badge bg-cyan-50 text-cyan-700 ring-1 ring-cyan-100',
        'slate' => 'si-badge bg-slate-100 text-slate-600 ring-1 ring-slate-200',
    ][$variant] ?? 'si-badge bg-slate-100 text-slate-600 ring-1 ring-slate-200';
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</span>
