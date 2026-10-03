<x-layouts::auth :title="__('Recuperar contraseña')">
    <div class="flex flex-col gap-6">
        <x-auth.header :title="__('Recuperar contraseña')" :description="__('Ingresa tu e-mail para recibir un enlace de recuperación de contraseña')" />

        <!-- Session Status -->
        <x-auth.session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-6">
            @csrf

            <!-- Email Address -->
            <x-ui.input
                name="email"
                :label="__('Email')"
                type="email"
                required
                autofocus
                placeholder="email@example.com" />

            <x-ui.button variant="primary" type="submit" class="w-full" data-test="email-password-reset-link-button">
                {{ __('Enviar enlace de recuperación') }}
            </x-ui.button>
        </form>

        <p class="type-body text-center">
            {{ __('O regresa a') }}
            <x-ui.link :href="route('login')" wire:navigate>{{ __('Iniciar sesión') }}</x-ui.link>
        </p>
    </div>
</x-layouts::auth>
