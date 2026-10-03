@props([
'status',
])

@if ($status)
<p role="status" {{ $attributes->merge(['class' => 'type-body']) }}>
    {{ $status }}
</p>
@endif
