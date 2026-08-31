<x-layouts::auth :title="__('Registro')">
    <div class="flex flex-col gap-6 p-2">
        <x-auth.header :title="__('Crea una cuenta')" :description="__('Ingresa tus datos a continuación para crear tu cuenta')" />

        <!-- Session Status -->
        <x-auth.session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-6">
            @csrf
            <!-- Name -->
            <flux:input class="tz-input"
                name="name"
                :label="__('Nombre')"
                :value="old('name')"
                type="text"
                required
                autofocus
                autocomplete="name"
                :placeholder="__('Nombre completo')" />

            <!-- Surname -->
            <flux:input class="tz-input"
                name="surname"
                :label="__('Apellido')"
                :value="old('surname')"
                type="text"
                required
                autofocus
                autocomplete="surname"
                :placeholder="__('Apellido completo')" />

            <!-- Email Address -->
            <flux:input class="tz-input"
                name="email"
                :label="__('Email')"
                :value="old('email')"
                type="email"
                required
                autocomplete="email"
                placeholder="email@example.com" />

            <!-- Password -->
            <flux:input class="tz-input"
                name="password"
                :label="__('Contraseña')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Contraseña')"
                passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                viewable />

            <!-- Confirm Password -->
            <flux:input class="tz-input"
                name="password_confirmation"
                :label="__('Confirmar contraseña')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Confirmar contraseña')"
                passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                viewable />

            <div class="flex items-center justify-end">
                <flux:button type="submit" variant="primary" class="w-full" data-test="register-user-button">
                    {{ __('Crear cuenta') }}
                </flux:button>
            </div>
        </form>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm">
            <span class="tz-subtitle2">{{ __('¿Ya tienes una cuenta?') }}</span>
            <flux:link :href="route('login')" wire:navigate>{{ __('Iniciar sesión') }}</flux:link>
        </div>
    </div>
</x-layouts::auth>