@props([
'optionsRoute' => 'passkey.registration-options',
'submitRoute' => 'passkey.store',
])

@assets
@vite('resources/js/passkeys.js')
@endassets

<div
    x-data="{
        supported: false,
        loading: false,
        name: '',
        error: null,
        updateSupport() {
            this.supported = Boolean(window.Passkeys?.isSupported());
        },
        init() {
            this.updateSupport();

            window.addEventListener('passkeys:ready', () => this.updateSupport(), { once: true });
        },
        async register() {
            if (this.loading) return;
            this.error = null;
            if (this.name.trim() === '') {
                this.error = @js(__('Escribe un nombre para la passkey.'));
                return;
            }
            this.loading = true;
            try {
                await window.Passkeys.register({
                    name: this.name.trim(),
                    routes: {
                        options: '{{ route($optionsRoute) }}',
                        submit: '{{ route($submitRoute) }}',
                    },
                });
                window.location.reload();
            } catch (e) {
                if (e.constructor?.name !== 'UserCancelledError') {
                    this.error = e.message;
                }
            } finally {
                this.loading = false;
            }
        },
    }">
    <p x-show="!supported" class="type-body">
        {{ __('Este navegador no admite passkeys.') }}
    </p>

    <form x-show="supported" x-cloak x-on:submit.prevent="register()" class="flex flex-col gap-4">
        <x-ui.field :label="__('Nombre de la passkey')" id="passkey-name">
            <input
                id="passkey-name"
                type="text"
                x-model="name"
                autocomplete="off"
                placeholder="{{ __('Ej.: portátil del trabajo') }}"
                x-bind:aria-invalid="error ? 'true' : null"
                x-bind:aria-describedby="error ? 'passkey-name-error' : null"
                x-bind:class="error ? 'border-feedback-error' : 'border-border-strong'"
                class="h-10 w-full px-3 rounded-field border-2 bg-surface-input type-control placeholder:italic placeholder:text-text-placeholder focus-visible:focus-ring disabled:opacity-50 disabled:cursor-not-allowed" />
            <p id="passkey-name-error" x-show="error" x-text="error" x-cloak role="alert" class="type-error mt-1"></p>
        </x-ui.field>

        <x-ui.button variant="primary" type="submit" busy="loading" class="w-full">
            {{ __('Añadir passkey') }}
        </x-ui.button>
    </form>
</div>
