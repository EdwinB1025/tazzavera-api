<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    @include('partials.head')
</head>

<body class="bg-surface-inverse antialiased">
    <div class="flex min-h-dvh flex-col items-center gap-6 px-4 py-8 sm:justify-center">
        <div class="flex flex-col items-center gap-2">
            <x-logo.app-logo class="size-20 fill-current mx-auto h-full" />
            <span class="type-brand">TAZAVERA</span>
        </div>

        <main class="w-full max-w-md bg-surface-card border border-border-strong rounded-container p-6 sm:p-8">
            {{ $slot }}
        </main>
    </div>

    @fluxScripts
</body>

</html>
