@php
    $user = auth('web')->user();

    $status = session('status');
    $statusKey = 'auth_ui.security.status.'.$status;
    $statusMessage = $status && Lang::has($statusKey) ? __($statusKey) : $status;
@endphp

<x-layouts::auth :title="__('auth_ui.security.title')">
    <div class="flex flex-col gap-8">
        <x-auth.header
            :title="__('auth_ui.security.title')"
            :description="__('auth_ui.security.description')" />

        <x-auth.session-status class="text-center" :status="$statusMessage" />

        {{-- ─── Two-factor authentication ─── --}}
        <section class="flex flex-col gap-4" aria-labelledby="two-factor-heading">
            <h2 id="two-factor-heading" class="type-card-heading">{{ __('auth_ui.security.two_factor.title') }}</h2>

            @if (! $user->two_factor_secret)
                <p class="type-body">{{ __('auth_ui.security.two_factor.disabled') }}</p>

                <form method="POST" action="{{ route('two-factor.enable') }}">
                    @csrf
                    <x-ui.button variant="primary" type="submit" class="w-full">
                        {{ __('auth_ui.security.two_factor.enable') }}
                    </x-ui.button>
                </form>
            @elseif (! $user->two_factor_confirmed_at)
                <p class="type-body">{{ __('auth_ui.security.two_factor.scan') }}</p>

                <div class="flex justify-center">
                    <div class="rounded-field border-2 border-border-strong bg-surface-textarea p-2">
                        {!! $user->twoFactorQrCodeSvg() !!}
                    </div>
                </div>

                <p class="type-body text-center">
                    {{ __('auth_ui.security.two_factor.setup_key') }}
                    <code class="break-all">{{ decrypt($user->two_factor_secret) }}</code>
                </p>

                <form method="POST" action="{{ route('two-factor.confirm') }}" class="flex flex-col gap-4">
                    @csrf
                    <x-ui.input
                        name="code"
                        id="confirm-code"
                        error-bag="confirmTwoFactorAuthentication"
                        :label="__('auth_ui.security.two_factor.code')"
                        required
                        inputmode="numeric"
                        pattern="[0-9]{6}"
                        maxlength="6"
                        autocomplete="one-time-code" />

                    <x-ui.button variant="primary" type="submit" class="w-full">
                        {{ __('auth_ui.security.two_factor.confirm') }}
                    </x-ui.button>
                </form>

                <form method="POST" action="{{ route('two-factor.disable') }}">
                    @csrf
                    @method('DELETE')
                    <x-ui.button variant="action-3" type="submit" class="w-full">
                        {{ __('auth_ui.security.two_factor.cancel') }}
                    </x-ui.button>
                </form>
            @else
                <p class="type-body">{{ __('auth_ui.security.two_factor.enabled') }}</p>

                <div x-data="{ open: false }" class="flex flex-col gap-4">
                    <x-ui.button
                        variant="action-3"
                        class="w-full"
                        x-on:click="open = ! open"
                        x-bind:aria-expanded="open.toString()"
                        aria-controls="recovery-codes">
                        <span x-show="!open">{{ __('auth_ui.security.two_factor.show_codes') }}</span>
                        <span x-show="open" x-cloak>{{ __('auth_ui.security.two_factor.hide_codes') }}</span>
                    </x-ui.button>

                    <div id="recovery-codes" x-show="open" x-cloak class="flex flex-col gap-2">
                        <p class="type-body">{{ __('auth_ui.security.two_factor.codes_help') }}</p>
                        <ul class="rounded-field border-2 border-border-strong bg-surface-input p-3 type-control font-mono">
                            @foreach ($user->recoveryCodes() as $code)
                                <li>{{ $code }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                <form method="POST" action="{{ route('two-factor.disable') }}">
                    @csrf
                    @method('DELETE')
                    <x-ui.button variant="action-3" type="submit" class="w-full">
                        {{ __('auth_ui.security.two_factor.disable') }}
                    </x-ui.button>
                </form>
            @endif
        </section>

        {{-- ─── Passkeys ─── --}}
        <section class="flex flex-col gap-4" aria-labelledby="passkeys-heading">
            <h2 id="passkeys-heading" class="type-card-heading">{{ __('auth_ui.security.passkeys.title') }}</h2>

            @if ($user->passkeys->isEmpty())
                <p class="type-body">{{ __('auth_ui.security.passkeys.empty') }}</p>
            @else
                <ul class="flex flex-col gap-3">
                    @foreach ($user->passkeys as $passkey)
                        <li class="flex items-center justify-between gap-4 rounded-field border-2 border-border-strong bg-surface-input p-3">
                            <div class="min-w-0">
                                <p class="type-label break-words">{{ $passkey->name }}</p>
                                <p class="type-body">
                                    {{ __('auth_ui.security.passkeys.added_on', ['date' => $passkey->created_at->format('d/m/Y')]) }}
                                </p>
                            </div>
                            <form method="POST" action="{{ route('passkey.destroy', $passkey) }}">
                                @csrf
                                @method('DELETE')
                                <x-ui.button variant="action-3" type="submit" :aria-label="__('auth_ui.security.passkeys.delete_named', ['name' => $passkey->name])">
                                    {{ __('auth_ui.security.passkeys.delete') }}
                                </x-ui.button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif

            <x-user.passkey-registration />
        </section>
    </div>
</x-layouts::auth>
