<x-layouts::auth :title="__('Verificación de email')">
    <div class="mt-4 flex flex-col gap-6 p-2">
        <flux:text class="text-center">
            {{ __('Por favor verifica tu dirección de email haciendo clic en el enlace que acabamos de enviarte.') }}
        </flux:text>

        @if (session('status') == 'verification-link-sent')
        <flux:text class="text-center font-medium !dark:text-green-400 !text-green-600">
            {{ __('Se ha enviado un nuevo enlace de verificación a la dirección de email que proporcionaste durante el registro.') }}
        </flux:text>
        @endif

        <div class="flex flex-col items-center justify-between space-y-3">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <flux:button type="submit" variant="primary" class="w-full">
                    {{ __('Reenviar email de verificación') }}
                </flux:button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <flux:button variant="ghost" type="submit" class="text-sm cursor-pointer" data-test="logout-button">
                    {{ __('Cerrar sesión') }}
                </flux:button>
            </form>
        </div>
    </div>
</x-layouts::auth>