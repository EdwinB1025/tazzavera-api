<x-layouts::auth :title="__('auth_ui.verify_email.title')">
    <div class="flex flex-col gap-6">
        <x-auth.header :title="__('auth_ui.verify_email.title')" :description="__('auth_ui.verify_email.description')" />

        <!-- Session Status -->
        <x-auth.session-status
            class="text-center"
            :status="session('status') === 'verification-link-sent' ? __('auth_ui.verify_email.sent') : null" />

        <form method="POST" action="{{ route('verification.send') }}" class="flex flex-col gap-6">
            @csrf

            <x-ui.button variant="primary" type="submit" class="w-full">
                {{ __('auth_ui.verify_email.resend') }}
            </x-ui.button>
        </form>

        <p class="type-body text-center">
            {{ __('auth_ui.verify_email.back') }}
            <x-ui.link :href="config('app.front_url')">{{ __('auth_ui.verify_email.back_link') }}</x-ui.link>
        </p>
    </div>
</x-layouts::auth>
