<x-layouts::auth :title="__('Restablecer contraseña')">
    <div class="flex flex-col gap-6">
        <x-auth.header :title="__('Restablecer contraseña')" :description="__('Ingresa tu nueva contraseña a continuación')" />

        <!-- Session Status -->
        <x-auth.session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('password.update') }}" class="flex flex-col gap-6">
            @csrf
            <!-- Token -->
            <input type="hidden" name="token" value="{{ request()->route('token') }}">

            <!-- Email Address -->
            <x-ui.input
                name="email"
                value="{{ request('email') }}"
                :label="__('Email')"
                type="email"
                required
                autocomplete="email" />

            <!-- Password -->
            <x-ui.password-input
                name="password"
                :label="__('Contraseña')"
                required
                autocomplete="new-password"
                :placeholder="__('Contraseña')"
                passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}" />

            <!-- Confirm Password -->
            <x-ui.password-input
                id="password_confirmation"
                name="password_confirmation"
                :label="__('Confirmar contraseña')"
                required
                autocomplete="new-password"
                :placeholder="__('Confirmar contraseña')"
                passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}" />

            <x-ui.button variant="primary" type="submit" class="w-full" data-test="reset-password-button">
                {{ __('Restablecer contraseña') }}
            </x-ui.button>
        </form>
    </div>
</x-layouts::auth>
