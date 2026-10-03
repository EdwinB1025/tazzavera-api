{{-- Text input inside the field contract, classes copied from
     tazavera-front/src/shared/ui/Input.tsx. The border swaps to the error
     treatment when the field has a validation error. --}}
@props([
    'label',
    'name',
    'id' => null,
    'required' => false,
])

@php
    $id ??= $name;
    $error = $errors->first($name);
    $invalid = filled($error);

    $box = 'h-10 w-full px-3 rounded-field border-2 bg-surface-input type-control placeholder:italic placeholder:text-text-placeholder focus-visible:focus-ring disabled:opacity-50 disabled:cursor-not-allowed';
@endphp

<x-ui.field :label="$label" :id="$id" :error="$error" :required="$required">
    <input
        id="{{ $id }}"
        name="{{ $name }}"
        @if ($required) required @endif
        @if ($invalid) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
        {{ $attributes->class([$box, $invalid ? 'border-feedback-error' : 'border-border-strong'])->merge(['type' => 'text']) }} />
</x-ui.field>
