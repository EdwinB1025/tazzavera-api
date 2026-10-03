@props([
'title',
'description',
])

<div class="flex w-full flex-col gap-2 text-center">
    <h1 class="type-heading">{{ $title }}</h1>
    <p class="type-body">{{ $description }}</p>
</div>
