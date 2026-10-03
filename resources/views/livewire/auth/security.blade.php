@php
    $user = auth('web')->user();

    $statusMessages = [
        'two-factor-authentication-enabled' => __('Escanea el código QR y confirma con un código de tu aplicación.'),
        'two-factor-authentication-confirmed' => __('Autenticación de dos factores activada.'),
        'two-factor-authentication-disabled' => __('Autenticación de dos factores desactivada.'),
        'passkey-registered' => __('Passkey añadida.'),
        'passkey-deleted' => __('Passkey eliminada.'),
    ];
    $status = session('status');
@endphp

<x-layouts::auth :title="__('Seguridad')">
    <div class="flex flex-col gap-8">
        <x-auth.header
            :title="__('Seguridad')"
            :description="__('Gestiona la autenticación de dos factores y tus passkeys.')" />

        <x-auth.session-status class="text-center" :status="$statusMessages[$status] ?? $status" />

        {{-- ─── Two-factor authentication ─── --}}
        <section class="flex flex-col gap-4" aria-labelledby="two-factor-heading">
            <h2 id="two-factor-heading" class="type-card-heading">{{ __('Autenticación de dos factores') }}</h2>

            @if (! $user->two_factor_secret)
                <p class="type-body">{{ __('No activada. Al activarla, el inicio de sesión pedirá un código de tu aplicación de autenticación.') }}</p>

                <form method="POST" action="{{ route('two-factor.enable') }}">
                    @csrf
                    <x-ui.button variant="primary" type="submit" class="w-full">
                        {{ __('Activar') }}
                    </x-ui.button>
                </form>
            @elseif (! $user->two_factor_confirmed_at)
                <p class="type-body">{{ __('Escanea este código QR con tu aplicación de autenticación e ingresa el código que genera.') }}</p>

                <div class="flex justify-center">
                    <div class="rounded-field border-2 border-border-strong bg-surface-textarea p-2">
                        {!! $user->twoFactorQrCodeSvg() !!}
                    </div>
                </div>

                <p class="type-body text-center">
                    {{ __('Clave de configuración:') }}
                    <code class="break-all">{{ decrypt($user->two_factor_secret) }}</code>
                </p>

                <form method="POST" action="{{ route('two-factor.confirm') }}" class="flex flex-col gap-4">
                    @csrf
                    <x-ui.input
                        name="code"
                        id="confirm-code"
                        error-bag="confirmTwoFactorAuthentication"
                        :label="__('Código de autenticación')"
                        required
                        inputmode="numeric"
                        pattern="[0-9]{6}"
                        maxlength="6"
                        autocomplete="one-time-code" />

                    <x-ui.button variant="primary" type="submit" class="w-full">
                        {{ __('Confirmar') }}
                    </x-ui.button>
                </form>

                <form method="POST" action="{{ route('two-factor.disable') }}">
                    @csrf
                    @method('DELETE')
                    <x-ui.button variant="action-3" type="submit" class="w-full">
                        {{ __('Cancelar') }}
                    </x-ui.button>
                </form>
            @else
                <p class="type-body">{{ __('Activada. El inicio de sesión pide un código de tu aplicación de autenticación.') }}</p>

                <div x-data="{ open: false }" class="flex flex-col gap-4">
                    <x-ui.button
                        variant="action-3"
                        class="w-full"
                        x-on:click="open = ! open"
                        x-bind:aria-expanded="open.toString()"
                        aria-controls="recovery-codes">
                        <span x-show="!open">{{ __('Ver códigos de recuperación') }}</span>
                        <span x-show="open" x-cloak>{{ __('Ocultar códigos de recuperación') }}</span>
                    </x-ui.button>

                    <div id="recovery-codes" x-show="open" x-cloak class="flex flex-col gap-2">
                        <p class="type-body">{{ __('Guarda estos códigos en un lugar seguro. Cada uno sirve una sola vez si pierdes el acceso a tu aplicación.') }}</p>
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
                        {{ __('Desactivar') }}
                    </x-ui.button>
                </form>
            @endif
        </section>

        {{-- ─── Passkeys ─── --}}
        <section class="flex flex-col gap-4" aria-labelledby="passkeys-heading">
            <h2 id="passkeys-heading" class="type-card-heading">{{ __('Passkeys') }}</h2>

            @if ($user->passkeys->isEmpty())
                <p class="type-body">{{ __('Todavía no tienes passkeys.') }}</p>
            @else
                <ul class="flex flex-col gap-3">
                    @foreach ($user->passkeys as $passkey)
                        <li class="flex items-center justify-between gap-4 rounded-field border-2 border-border-strong bg-surface-input p-3">
                            <div class="min-w-0">
                                <p class="type-label break-words">{{ $passkey->name }}</p>
                                <p class="type-body">
                                    {{ __('Añadida el :date', ['date' => $passkey->created_at->format('d/m/Y')]) }}
                                </p>
                            </div>
                            <form method="POST" action="{{ route('passkey.destroy', $passkey) }}">
                                @csrf
                                @method('DELETE')
                                <x-ui.button variant="action-3" type="submit" :aria-label="__('Eliminar la passkey :name', ['name' => $passkey->name])">
                                    {{ __('Eliminar') }}
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
