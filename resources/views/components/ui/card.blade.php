@props([
    'padded' => true,
    'large' => false,
])

<section {{ $attributes->merge(['class' => ($large ? 'si-card-lg' : 'si-card').' '.($padded ? 'p-6' : '')]) }}>
    {{ $slot }}
</section>
