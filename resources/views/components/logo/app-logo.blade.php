{{-- Tazavera logo: the themed mark plus the brand text in type-brand,
     linked to the front-end home. Variants: icon (mark only), inline
     (mark and text in a row) and stacked (text under the mark). --}}
@props([
    'variant' => 'stacked',
    'size' => 'md',
])

@php
    $markSize = [
        'sm' => 'h-8',
        'md' => 'h-12',
        'lg' => 'h-20',
    ][$size];

    $layout = [
        'icon' => 'inline-flex',
        'inline' => 'inline-flex flex-row items-center gap-2',
        'stacked' => 'inline-flex flex-col items-center gap-2',
    ][$variant];
@endphp

<a href="{{ config('app.front_url') }}"
    aria-label="{{ __('auth_ui.brand_name') }}"
    {{ $attributes->class([$layout, 'focus-visible:focus-ring']) }}>
    <x-logo.icon class="{{ $markSize }} w-auto shrink-0" />
    @if ($variant !== 'icon')
        <span class="type-brand" aria-hidden="true">{{ __('auth_ui.brand') }}</span>
    @endif
</a>
