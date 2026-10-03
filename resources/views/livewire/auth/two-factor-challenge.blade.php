<x-layouts::auth :title="__('auth_ui.two_factor.page_title')">
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
                :title="__('auth_ui.two_factor.code_title')"
                :description="__('auth_ui.two_factor.code_description')" />
        </div>

        <div x-show="showRecoveryInput">
            <x-auth.header
                :title="__('auth_ui.two_factor.recovery_title')"
                :description="__('auth_ui.two_factor.recovery_description')" />
        </div>

        <form method="POST" action="{{ route('two-factor.login.store') }}" class="flex flex-col gap-6">
            @csrf

            <div x-show="!showRecoveryInput">
                <x-ui.input
                    name="code"
                    :label="__('auth_ui.two_factor.code')"
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
                    :label="__('auth_ui.two_factor.recovery_code')"
                    x-ref="recovery_code"
                    x-model="recovery_code"
                    x-bind:required="showRecoveryInput"
                    x-bind:disabled="!showRecoveryInput"
                    autocomplete="one-time-code" />
            </div>

            <x-ui.button variant="primary" type="submit" class="w-full">
                {{ __('auth_ui.two_factor.submit') }}
            </x-ui.button>

            <p class="type-body text-center">
                {{ __('auth_ui.two_factor.or') }}
                <button type="button" x-show="!showRecoveryInput" x-on:click="toggleInput()" class="underline text-text-link hover:text-text-link-hover focus-visible:focus-ring">{{ __('auth_ui.two_factor.use_recovery') }}</button>
                <button type="button" x-show="showRecoveryInput" x-on:click="toggleInput()" class="underline text-text-link hover:text-text-link-hover focus-visible:focus-ring">{{ __('auth_ui.two_factor.use_code') }}</button>
            </p>
        </form>
    </div>
</x-layouts::auth>
