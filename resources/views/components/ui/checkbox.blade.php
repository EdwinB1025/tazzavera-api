{{-- Checkbox copied from tazavera-front/src/shared/ui/Checkbox.tsx: custom box,
     a check mark that follows the input's real state through `peer`, and a
     clickable label keeping the hit area at least 24 by 24 pixels. --}}
@props(['label'])

@php
    $box = 'peer size-4 appearance-none rounded-check border-2 checked:bg-surface-checked focus-visible:focus-ring disabled:opacity-50 disabled:cursor-not-allowed border-border-strong';
    $mark = 'pointer-events-none absolute top-1/2 left-1/2 size-3 -translate-x-1/2 -translate-y-1/2 text-checked-mark invisible peer-checked:visible peer-disabled:opacity-50';
@endphp

<div>
    <label class="flex min-h-6 min-w-6 items-center gap-2">
        <span class="relative inline-flex">
            <input type="checkbox" {{ $attributes->class([$box]) }} />
            <x-ui.icon name="check" class="{{ $mark }}" />
        </span>
        <span class="type-label">{{ $label }}</span>
    </label>
</div>
