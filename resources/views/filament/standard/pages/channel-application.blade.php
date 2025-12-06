<x-filament-panels::page>
    <div class="mb-6 p-4 rounded bg-gray-50 border border-gray-200">
        <h2 class="text-lg font-semibold mb-2">
            {{ __('filament.channel_application.form.about_title') }}
        </h2>
        <p class="mb-2">
            {{ __('filament.channel_application.form.about_intro') }}
        </p>
        <div class="space-y-3 mb-2">
            <div class="flex items-start gap-2">
                <x-filament::icon name="heroicon-o-check-circle" class="w-5 h-5 text-primary-600 shrink-0"/>
                <span>{{ __('filament.channel_application.form.about_benefit_security') }}</span>
            </div>
            <div class="flex items-start gap-2">
                <x-filament::icon name="heroicon-o-check-circle" class="w-5 h-5 text-primary-600 shrink-0"/>
                <span>{{ __('filament.channel_application.form.about_benefit_control') }}</span>
            </div>
            <div class="flex items-start gap-2">
                <x-filament::icon name="heroicon-o-check-circle" class="w-5 h-5 text-primary-600 shrink-0"/>
                <span>{{ __('filament.channel_application.form.about_benefit_remain') }}</span>
            </div>
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