{{-- Classes copied from tazavera-front/src/shared/ui/Button.tsx.
     `busy` is an optional Alpine expression: while true the button sets
     aria-busy/aria-disabled (never native disabled), drops the hover
     treatment and shows the spinner, as the front's `loading` prop does. --}}
@props([
    'variant' => 'primary',
    'type' => 'button',
    'busy' => null,
])

@php
    $base = 'type-button inline-flex items-center justify-center gap-2 rounded-pill focus-visible:focus-ring disabled:opacity-50 disabled:cursor-not-allowed';

    $rest = [
        'primary' => 'bg-action-primary text-action-primary-text',
        'action-3' => 'bg-action-3-bg text-action-3-text',
    ][$variant];

    $hover = [
        'primary' => 'enabled:hover:bg-action-primary-hover',
        'action-3' => 'enabled:hover:bg-action-3-bg-hover enabled:hover:text-action-3-text-hover',
    ][$variant];

    $geometry = 'h-10 px-4';
@endphp

@if ($busy)
<button
    type="{{ $type }}"
    x-bind:aria-busy="({{ $busy }}) || undefined"
    x-bind:aria-disabled="({{ $busy }}) || undefined"
    x-bind:class="({{ $busy }}) ? '' : '{{ $hover }}'"
    {{ $attributes->class([$base, $rest, $geometry]) }}>
    <x-ui.icon name="loader" x-show="{{ $busy }}" x-cloak class="size-4 shrink-0 animate-spin motion-reduce:animate-none" />
    {{ $slot }}
</button>
@else
<button type="{{ $type }}" {{ $attributes->class([$base, $rest, $hover, $geometry]) }}>
    {{ $slot }}
</button>
@endif
