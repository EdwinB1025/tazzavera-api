<x-layouts::auth :title="__('auth_ui.reset.title')">
    <div class="flex flex-col gap-6">
        <x-auth.header :title="__('auth_ui.reset.title')" :description="__('auth_ui.reset.description')" />

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
                :label="__('auth_ui.fields.email')"
                type="email"
                required
                autocomplete="email" />

            <!-- Password -->
            <x-ui.password-input
                name="password"
                :label="__('auth_ui.fields.password')"
                required
                autocomplete="new-password"
                :placeholder="__('auth_ui.fields.password')"
                passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}" />

            <!-- Confirm Password -->
            <x-ui.password-input
                id="password_confirmation"
                name="password_confirmation"
                :label="__('auth_ui.fields.password_confirmation')"
                required
                autocomplete="new-password"
                :placeholder="__('auth_ui.fields.password_confirmation')"
                passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}" />

            <x-ui.button variant="primary" type="submit" class="w-full" data-test="reset-password-button">
                {{ __('auth_ui.reset.submit') }}
            </x-ui.button>
        </form>
    </div>
</x-layouts::auth>
