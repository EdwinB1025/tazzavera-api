<x-layouts::auth :title="__('Autenticación de dos factores')">
    <div
        class="flex flex-col gap-6"
        x-cloak
        x-data="{
            showRecoveryInput: @js($errors->has('recovery_code')),
            code: '',
            recovery_code: '',
            init() {
                if (! this.showRecoveryInput) {
                    this.$nextTick(() => this.$refs.code?.focus());
                }
            },
            toggleInput() {
                this.showRecoveryInput = !this.showRecoveryInput;

                this.code = '';
                this.recovery_code = '';

                this.$nextTick(() => {
                    this.showRecoveryInput
                        ? this.$refs.recovery_code?.focus()
                        : this.$refs.code?.focus();
                });
            },
        }">
        <div x-show="!showRecoveryInput">
            <x-auth.header
                :title="__('Código de autenticación')"
                :description="__('Ingresa el código de autenticación proporcionado por tu aplicación de autenticación.')" />
        </div>

        <div x-show="showRecoveryInput">
            <x-auth.header
                :title="__('Código de recuperación')"
                :description="__('Por favor confirma el acceso a tu cuenta ingresando uno de tus códigos de recuperación de emergencia.')" />
        </div>

        <form method="POST" action="{{ route('two-factor.login.store') }}" class="flex flex-col gap-6">
            @csrf

            <div x-show="!showRecoveryInput">
                <x-ui.input
                    name="code"
                    :label="__('Código de autenticación')"
                    x-ref="code"
                    x-model="code"
                    x-bind:required="!showRecoveryInput"
                    x-bind:disabled="showRecoveryInput"
                    inputmode="numeric"
                    pattern="[0-9]{6}"
                    maxlength="6"
                    autocomplete="one-time-code" />
            </div>

            <div x-show="showRecoveryInput">
                <x-ui.input
                    name="recovery_code"
                    :label="__('Código de recuperación')"
                    x-ref="recovery_code"
                    x-model="recovery_code"
                    x-bind:required="showRecoveryInput"
                    x-bind:disabled="!showRecoveryInput"
                    autocomplete="one-time-code" />
            </div>

            <x-ui.button variant="primary" type="submit" class="w-full">
                {{ __('Continuar') }}
            </x-ui.button>

            <p class="type-body text-center">
                {{ __('o puedes') }}
                <button type="button" x-show="!showRecoveryInput" x-on:click="toggleInput()" class="underline text-text-link hover:text-text-link-hover focus-visible:focus-ring">{{ __('iniciar sesión con un código de recuperación') }}</button>
                <button type="button" x-show="showRecoveryInput" x-on:click="toggleInput()" class="underline text-text-link hover:text-text-link-hover focus-visible:focus-ring">{{ __('iniciar sesión con un código de autenticación') }}</button>
            </p>
        </form>
    </div>
</x-layouts::auth>
