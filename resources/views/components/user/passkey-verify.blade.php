@props([
'optionsRoute' => 'passkey.login-options',
'submitRoute' => 'passkey.login',
'label' => __('Sign in with a passkey'),
'loadingLabel' => __('Authenticating...'),
'separator' => __('Or continue with email'),
])

@assets
@vite('resources/js/passkeys.js')
@endassets

<div
    x-data="{
        supported: false,
        loading: false,
        error: null,
        updateSupport() {
            this.supported = Boolean(window.Passkeys?.isSupported());
        },
        init() {
            this.updateSupport();

            window.addEventListener('passkeys:ready', () => this.updateSupport(), { once: true });
        },
        async verify() {
            if (this.loading) return;
            this.loading = true;
            this.error = null;
            try {
                const response = await window.Passkeys.verify({
                    routes: {
                        options: '{{ route($optionsRoute) }}',
                        submit: '{{ route($submitRoute) }}',
                    },
                });
                {{-- Full navigation: the intended URL (oauth/authorize) redirects to the client's callback on another origin. --}}
                window.location.assign(response.redirect || '/');
            } catch (e) {
                if (e.constructor?.name !== 'UserCancelledError') {
                    this.error = e.message;
                }
            } finally {
                this.loading = false;
            }
        },
    }">
    <template x-if="supported">
        <div>
            <div class="grid gap-2">
                <x-ui.button variant="action-3" busy="loading" class="w-full" x-on:click="verify()">
                    <span x-show="!loading">{{ $label }}</span>
                    <span x-show="loading" x-cloak>{{ $loadingLabel }}</span>
                </x-ui.button>
                <p x-show="error" x-text="error" x-cloak role="alert" class="type-error text-center"></p>
            </div>

            <div class="relative my-6">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-border-subtle"></div>
                </div>
                <div class="relative flex justify-center">
                    <span class="type-body bg-surface-card px-2">
                        {{ $separator }}
                    </span>
                </div>
            </div>
        </div>
    </template>
</div>
