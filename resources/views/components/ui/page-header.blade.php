@props([
    'eyebrow' => null,
    'title',
    'subtitle' => null,
])

<section {{ $attributes->merge(['class' => 'relative overflow-hidden rounded-3xl border border-cyan-100 bg-white p-6 shadow-xl shadow-sky-900/6 md:p-7']) }}>
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(14,165,233,0.14),transparent_35%),linear-gradient(135deg,rgba(236,254,255,0.86),rgba(255,255,255,0.72)_52%,rgba(240,249,255,0.9))]"></div>
    <div class="relative flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            @if($eyebrow)
                <p class="text-xs font-bold uppercase tracking-widest text-cyan-700">{{ $eyebrow }}</p>
            @endif
            <h2 class="mt-2 text-2xl font-bold tracking-tight text-slate-950 md:text-3xl">{{ $title }}</h2>
            @if($subtitle)
                <p class="mt-3 max-w-3xl text-sm leading-7 text-slate-600">{{ $subtitle }}</p>
            @endif
        </div>
        @isset($actions)
            <div class="flex flex-wrap gap-2">{{ $actions }}</div>
        @endisset
    </div>
</section>
