<x-layouts::auth :title="__('auth_ui.login.page_title')">
    <div class="flex flex-col gap-6">
        <x-auth.header :title="__('auth_ui.login.title')" :description="__('auth_ui.login.description')" />

        <!-- Session Status -->
        <x-auth.session-status class="text-center" :status="session('status')" />

        <x-user.passkey-verify />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-6">
            @csrf

            <!-- Email Address -->
            <x-ui.input
                name="email"
                :label="__('auth_ui.fields.email')"
                :value="old('email')"
                type="email"
                required
                autofocus
                autocomplete="email"
                :placeholder="__('auth_ui.fields.email_placeholder')" />

            <!-- Password -->
            <div class="relative">
                <x-ui.password-input
                    name="password"
                    :label="__('auth_ui.fields.password')"
                    required
                    autocomplete="current-password" />
                @if (Route::has('password.request'))
                <p class="type-body mt-2 text-end sm:absolute sm:top-0 sm:end-0 sm:mt-0">
                    <x-ui.link :href="route('password.request')" wire:navigate>
                        {{ __('auth_ui.login.forgot_password') }}
                    </x-ui.link>
                </p>
                @endif
            </div>

            <x-ui.checkbox name="remember" value="1" :label="__('auth_ui.login.remember')" :checked="old('remember')" />

            <x-ui.button variant="primary" type="submit" class="w-full" data-test="login-button">
                {{ __('auth_ui.login.submit') }}
            </x-ui.button>
        </form>
    </div>
</x-layouts::auth>
