<ul class="space-y-3" aria-label="{{ __('passkeys.registered') }}">
    @forelse ($passkeys as $passkey)
        <li class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
            <p class="font-medium break-words">{{ $passkey->name }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ __('passkeys.last_used') }}:
                {{ $passkey->last_used_at?->diffForHumans() ?? __('passkeys.never_used') }}
            </p>
        </li>
    @empty
        <li class="text-sm text-gray-500 dark:text-gray-400">{{ __('passkeys.empty') }}</li>
    @endforelse
</ul>
