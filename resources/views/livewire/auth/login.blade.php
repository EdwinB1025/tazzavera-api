<x-layouts::auth :title="__('Log in')">
    <div class="flex flex-col gap-6">
        <x-auth.header :title="__('Accede a tu cuenta')" :description="__('Ingresa tu e-mail y cotraseña para acceder a tu cuenta!')" />

        <!-- Session Status -->
        <x-auth.session-status class="text-center" :status="session('status')" />

        <x-user.passkey-verify />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-6">
            @csrf

            <!-- Email Address -->
            <x-ui.input
                name="email"
                :label="__('Email')"
                :value="old('email')"
                type="email"
                required
                autofocus
                autocomplete="email"
                placeholder="email@example.com" />

            <!-- Password -->
            <div class="relative">
                <x-ui.password-input
                    name="password"
                    :label="__('Contraseña')"
                    required
                    autocomplete="current-password"
                    :placeholder="__('*************')" />
                @if (Route::has('password.request'))
                <p class="type-body absolute top-0 end-0">
                    <x-ui.link :href="route('password.request')" wire:navigate>
                        {{ __('Olvidaste tu contraseña?') }}
                    </x-ui.link>
                </p>
                @endif
            </div>

            <x-ui.checkbox name="remember" value="1" :label="__('Recuerdame')" :checked="old('remember')" />

            <x-ui.button variant="primary" type="submit" class="w-full" data-test="login-button">
                {{ __('Log in') }}
            </x-ui.button>
        </form>
    </div>
</x-layouts::auth>
