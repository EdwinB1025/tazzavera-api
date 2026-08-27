<x-layouts::auth :title="__('Log in')">
    <div class="flex flex-col gap-6 p-2">
        <x-auth.header :title="__('Accede a tu cuenta')" :description="__('Ingresa tu e-mail y cotraseña para acceder a tu cuenta!')" />

        <!-- Session Status -->
        <x-auth.session-status class="text-center" :status="session('status')" />

        <!--<x-user.passkey-verify />  future implmentation with trait in user Model-->

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-6">
            @csrf

            <!-- Email Address -->
            <flux:input class="tz-input"
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
                <flux:input
                    name="password"
                    :label="__('Contraseña')"
                    type="password"
                    required
                    autocomplete="current-password"
                    :placeholder="__('*************')"
                    viewable />

                @if (Route::has('password.request'))
                <flux:link class="absolute top-0 text-sm end-0" :href="route('password.request')" wire:navigate>
                    {{ __('Olvidaste tu contraseña?') }}
                </flux:link>
                @endif
            </div>

            <!-- Remember Me -->
            <flux:checkbox name="remember" :label="__('Recuerdame')" :checked="old('remember')" />

            <div class="flex items-center justify-end">
                <flux:button variant="primary" type="submit" class="w-full" data-test="login-button">
                    {{ __('Log in') }}
                </flux:button>
            </div>
        </form>

        <div class="space-x-1 text-sm text-center rtl:space-x-reverse text-zinc-600 dark:text-zinc-400">
            <span class="tz-subtitle2">{{ __('No tienes una cuenta?') }}</span>
            <flux:link :href="route('register')" wire:navigate>{{ __('Registrate') }}</flux:link>
        </div>
    </div>
</x-layouts::auth>