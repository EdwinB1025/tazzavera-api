<x-layouts::auth :title="__('auth_ui.confirm.title')">
    <div class="flex flex-col gap-6">
        <x-auth.header
            :title="__('auth_ui.confirm.title')"
            :description="__('auth_ui.confirm.description')" />

        <x-auth.session-status class="text-center" :status="session('status')" />

        <x-user.passkey-verify
            options-route="passkey.confirm-options"
            submit-route="passkey.confirm"
            :label="__('auth_ui.passkey.confirm')"
            :loading-label="__('auth_ui.passkey.confirming')"
            :separator="__('auth_ui.passkey.or_password')" />

        <form method="POST" action="{{ route('password.confirm.store') }}" class="flex flex-col gap-6">
            @csrf

            <x-ui.password-input
                name="password"
                :label="__('auth_ui.fields.password')"
                required
                autocomplete="current-password"
                :placeholder="__('auth_ui.fields.password')" />

            <x-ui.button variant="primary" type="submit" class="w-full" data-test="confirm-password-button">
                {{ __('auth_ui.confirm.submit') }}
            </x-ui.button>
        </form>
    </div>
</x-layouts::auth>
