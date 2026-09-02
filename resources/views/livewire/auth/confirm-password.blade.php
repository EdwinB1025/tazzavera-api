<x-layouts::auth :title="__('Confirmar contraseña')">
    <div class="flex flex-col gap-6 p-2">
        <x-auth.header
            :title="__('Confirmar contraseña')"
            :description="__('Esta es un área segura de la aplicación. Por favor confirma tu contraseña antes de continuar.')" />

        <x-auth.session-status class="text-center" :status="session('status')" />

        <x-user.passkey-verify
            options-route="passkey.confirm-options"
            submit-route="passkey.confirm"
            :label="__('Confirmar con passkey')"
            :loading-label="__('Confirmando...')"
            :separator="__('O confirma con tu contraseña')" />

        <form method="POST" action="{{ route('password.confirm.store') }}" class="flex flex-col gap-6">
            @csrf

            <flux:input class="tz-input"
                name="password"
                :label="__('Contraseña')"
                type="password"
                required
                autocomplete="current-password"
                :placeholder="__('Contraseña')"
                viewable />

            <flux:button variant="primary" type="submit" class="w-full" data-test="confirm-password-button">
                {{ __('Confirmar') }}
            </flux:button>
        </form>
    </div>
</x-layouts::auth>