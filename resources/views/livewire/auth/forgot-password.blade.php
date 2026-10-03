<x-layouts::auth :title="__('auth_ui.forgot.title')">
    <div class="flex flex-col gap-6">
        <x-auth.header :title="__('auth_ui.forgot.title')" :description="__('auth_ui.forgot.description')" />

        <!-- Session Status -->
        <x-auth.session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-6">
            @csrf

            <!-- Email Address -->
            <x-ui.input
                name="email"
                :label="__('auth_ui.fields.email')"
                type="email"
                required
                autofocus
                :placeholder="__('auth_ui.fields.email_placeholder')" />

            <x-ui.button variant="primary" type="submit" class="w-full" data-test="email-password-reset-link-button">
                {{ __('auth_ui.forgot.submit') }}
            </x-ui.button>
        </form>

        <p class="type-body text-center">
            {{ __('auth_ui.forgot.back') }}
            <x-ui.link :href="route('login')" wire:navigate>{{ __('auth_ui.forgot.back_link') }}</x-ui.link>
        </p>
    </div>
</x-layouts::auth>
