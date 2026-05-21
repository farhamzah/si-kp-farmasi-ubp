@props([
    'steps' => [],
])

<div {{ $attributes->merge(['class' => 'rounded-3xl border border-sky-100 bg-white p-5 shadow-sm shadow-sky-900/5']) }}>
    <div class="grid gap-3 md:grid-cols-5">
        @foreach($steps as $step)
            @php
                $state = $step['state'] ?? 'pending';
                $stateClass = [
                    'done' => 'border-emerald-200 bg-emerald-50 text-emerald-800',
                    'active' => 'border-cyan-200 bg-cyan-50 text-cyan-800',
                    'warning' => 'border-amber-200 bg-amber-50 text-amber-800',
                    'pending' => 'border-slate-200 bg-slate-50 text-slate-500',
                ][$state] ?? 'border-slate-200 bg-slate-50 text-slate-500';
            @endphp
            <div class="relative rounded-2xl border p-4 {{ $stateClass }}">
                @unless($loop->last)
                    <div class="absolute left-1/2 top-6 hidden h-px w-full bg-sky-100 md:block"></div>
                @endunless
                <div class="relative flex items-center gap-3">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-white text-xs font-bold shadow-sm ring-1 ring-current/10">
                        {{ $loop->iteration }}
                    </span>
                    <div>
                        <p class="text-sm font-bold text-slate-900">{{ $step['label'] }}</p>
                        <p class="mt-1 text-xs text-current">{{ $step['description'] ?? '' }}</p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
