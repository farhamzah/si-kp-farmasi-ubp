@props([
    'label',
    'value',
    'tone' => 'cyan',
    'description' => null,
])

@php
    $toneClasses = [
        'cyan' => 'bg-cyan-50 text-cyan-700 ring-cyan-100',
        'emerald' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
        'amber' => 'bg-amber-50 text-amber-700 ring-amber-100',
        'rose' => 'bg-rose-50 text-rose-700 ring-rose-100',
        'slate' => 'bg-slate-100 text-slate-700 ring-slate-200',
    ][$tone] ?? 'bg-cyan-50 text-cyan-700 ring-cyan-100';
@endphp

<div {{ $attributes->merge(['class' => 'si-card p-5 transition hover:-translate-y-0.5 hover:shadow-lg']) }}>
    <div class="flex items-start justify-between gap-4">
        <div>
            <p class="text-xs font-bold uppercase tracking-widest text-slate-500">{{ $label }}</p>
            <p class="mt-3 text-2xl font-bold text-slate-950">{{ $value }}</p>
            @if($description)
                <p class="mt-2 text-xs leading-5 text-slate-500">{{ $description }}</p>
            @endif
        </div>
        <div class="rounded-2xl p-3 ring-1 {{ $toneClasses }}">
            {{ $icon ?? '' }}
        </div>
    </div>
</div>
