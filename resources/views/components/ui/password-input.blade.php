{{-- Password input with visibility toggle inside the field contract, copied
     from tazavera-front/src/shared/ui/PasswordInput.tsx: masked by default,
     the toggle names its current action, carries the shared focus indicator
     and is disabled whenever the input is. --}}
@props([
    'label',
    'name',
    'id' => null,
    'required' => false,
    'disabled' => false,
    'showPasswordLabel' => __('auth_ui.fields.show_password'),
    'hidePasswordLabel' => __('auth_ui.fields.hide_password'),
])

@php
    $id ??= $name;
    $error = $errors->first($name);
    $invalid = filled($error);

    $box = 'h-10 w-full px-3 pr-10 rounded-field border-2 bg-surface-input type-control placeholder:italic placeholder:text-text-placeholder focus-visible:focus-ring disabled:opacity-50 disabled:cursor-not-allowed';
    $toggle = 'absolute inset-y-0 right-0 flex w-10 items-center justify-center focus-visible:focus-ring';
@endphp

<x-ui.field :label="$label" :id="$id" :error="$error" :required="$required">
    <div class="relative" x-data="{ revealed: false }">
        <input
            id="{{ $id }}"
            name="{{ $name }}"
            type="password"
            x-bind:type="revealed ? 'text' : 'password'"
            @if ($required) required @endif
            @if ($disabled) disabled @endif
            @if ($invalid) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
            {{ $attributes->class([$box, $invalid ? 'border-feedback-error' : 'border-border-strong']) }} />
        <button
            type="button"
            x-on:click="revealed = ! revealed"
            aria-label="{{ $showPasswordLabel }}"
            x-bind:aria-label="revealed ? @js($hidePasswordLabel) : @js($showPasswordLabel)"
            @if ($disabled) disabled @endif
            class="{{ $toggle }}">
            <x-ui.icon name="eye" x-show="! revealed" class="size-4 text-text-secondary" />
            <x-ui.icon name="eye-off" x-show="revealed" x-cloak class="size-4 text-text-secondary" />
        </button>
    </div>
</x-ui.field>
