{{-- Field contract copied from tazavera-front/src/shared/ui/Field.tsx: label
     above the control and the error message below it as text, so an error is
     never conveyed by color alone. The control in the slot points its
     aria-describedby at "{id}-error". --}}
@props([
    'label',
    'id',
    'error' => null,
    'required' => false,
])

<div>
    <label for="{{ $id }}" class="type-label block mb-2">
        {{ $label }}
        @if ($required)
            <span aria-hidden="true">*</span>
        @endif
    </label>
    {{ $slot }}
    @if (filled($error))
        <p id="{{ $id }}-error" class="type-error mt-1">{{ $error }}</p>
    @endif
</div>
