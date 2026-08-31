<x-layouts::auth :title="__('Recuperar contraseña')">
    <div class="flex flex-col gap-6 p-2">
        <x-auth.header :title="__('Recuperar contraseña')" :description="__('Ingresa tu e-mail para recibir un enlace de recuperación de contraseña')" />

        <!-- Session Status -->
        <x-auth.session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-6">
            @csrf

            <!-- Email Address -->
            <flux:input class="tz-input"
                name="email"
                :label="__('Email')"
                type="email"
                required
                autofocus
                placeholder="email@example.com" />

            <flux:button variant="primary" type="submit" class="w-full" data-test="email-password-reset-link-button">
                {{ __('Enviar enlace de recuperación') }}
            </flux:button>
        </form>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-400">
            <span class="tz-subtitle2">{{ __('O regresa a') }}</span>
            <flux:link :href="route('login')" wire:navigate>{{ __('Iniciar sesión') }}</flux:link>
        </div>
    </div>
</x-layouts::auth>