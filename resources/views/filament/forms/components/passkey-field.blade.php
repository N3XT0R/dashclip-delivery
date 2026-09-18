<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div
        x-data="{
            busy: false,
            error: '',
            supported: window.isSecureContext && !!window.PublicKeyCredential,
            async start() {
                this.busy = true;
                this.error = '';
                try {
                    const challenge = await $wire.callSchemaComponentMethod(@js($getKey()), 'generateOptions');
                    if (!challenge) { this.error = @js(__('passkeys.check_fields')); return; }
                    const credential = await window.dashclipPasskeys.perform(challenge.options, @js($getPurpose()));
                    $wire.$set(@js($getStatePath()), JSON.stringify({ token: challenge.token, credential }), false);
                    this.$el.closest('form')?.requestSubmit();
                } catch (error) {
                    this.error = error.name === 'InvalidStateError' ? @js(__('passkeys.already_registered')) : @js(__('passkeys.cancelled'));
                } finally {
                    this.busy = false;
                }
            }
        }"
        {{ $getExtraAttributeBag() }}
    >
        <x-filament::button type="button" x-on:click="start()" x-bind:disabled="busy || !supported" icon="heroicon-o-key">
            <span x-show="!busy">{{ __($getPurpose() === 'register' ? 'passkeys.create' : 'passkeys.use') }}</span>
            <span x-show="busy" x-cloak>{{ __('passkeys.waiting') }}</span>
        </x-filament::button>
        <p x-show="!supported" x-cloak class="mt-2 text-sm text-gray-500">{{ __('passkeys.unsupported') }}</p>
        <p x-show="error" x-text="error" role="alert" x-cloak class="mt-2 text-sm text-danger-600 dark:text-danger-400"></p>
    </div>
</x-dynamic-component>
