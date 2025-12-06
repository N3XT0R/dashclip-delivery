<x-filament-panels::page>
    <div class="mb-6 p-4 rounded bg-gray-50 border border-gray-200">
        <h2 class="text-lg font-semibold mb-2">
            {{ __('filament.channel_application.form.about_title') }}
        </h2>
        <p class="mb-4">
            {{ __('filament.channel_application.form.about_intro') }}
        </p>
        <div class="mb-4 space-y-2">
            <p><strong>{{ __('filament.channel_application.form.about_benefit_security_title') }}</strong><br>
                {{ __('filament.channel_application.form.about_benefit_security') }}</p>
            <p><strong>{{ __('filament.channel_application.form.about_benefit_control_title') }}</strong><br>
                {{ __('filament.channel_application.form.about_benefit_control') }}</p>
            <p><strong>{{ __('filament.channel_application.form.about_benefit_remain_title') }}</strong><br>
                {{ __('filament.channel_application.form.about_benefit_remain') }}</p>
        </div>
        <p class="text-sm text-gray-500">
            {{ __('filament.channel_application.form.about_footer') }}
        </p>
    </div>
    {{ $this->form }}
    <x-filament::button type="submit" color="primary" form="form">
        {{ __('filament.channel_application.form.submit') }}
    </x-filament::button>
</x-filament-panels::page>