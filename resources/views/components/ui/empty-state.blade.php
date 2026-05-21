@props([
    'title',
    'description' => null,
])

<div {{ $attributes->merge(['class' => 'rounded-3xl border border-dashed border-sky-200 bg-sky-50/60 px-6 py-10 text-center']) }}>
    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-white text-cyan-700 shadow-sm ring-1 ring-sky-100">
        {{ $icon ?? '' }}
        @unless(isset($icon))
            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6M8 4h8l4 4v10a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2Z"/>
            </svg>
        @endunless
    </div>
    <h3 class="mt-5 text-base font-bold text-slate-950">{{ $title }}</h3>
    @if($description)
        <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500">{{ $description }}</p>
    @endif
    @isset($action)
        <div class="mt-5">{{ $action }}</div>
    @endisset
</div>
