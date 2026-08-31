<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    @include('partials.head')
</head>

<body class="min-h-screen bg-white antialiased">
    <div class="tz-bg-inverse flex min-h-svh flex-col items-center justify-center">
        <div class="tz-logincard flex w-ful  max-w-sm sm:max-w-lg flex-col gap-2">
            <div class="flex flex-col items-center gap-2 font-medium">
                <span class="flex h-full w-full mb-1 items-center justify-center rounded-md">
                    <x-logo.app-logo class="size-20 fill-current mx-auto h-full" />
                </span>
                <span class="sr-only">{{ config('app.name', 'TAZAVERA') }}</span>
            </div>
            <div class="flex flex-col gap-6">
                {{ $slot }}
            </div>
        </div>
    </div>

    @persist('toast')
    <flux:toast.group>
        <flux:toast />
    </flux:toast.group>
    @endpersist

    @fluxScripts
</body>

</html>