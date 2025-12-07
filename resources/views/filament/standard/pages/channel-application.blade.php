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
            <p><strong>{{ __('filament.channel_application.form.about_benefit_portal_title') }}</strong><br>
                {{ __('filament.channel_application.form.about_benefit_portal') }}</p>
            <p><strong>{{ __('filament.channel_application.form.about_benefit_remain_title') }}</strong><br>
                {{ __('filament.channel_application.form.about_benefit_remain') }}</p>
        </div>
        <p class="text-sm text-gray-500">
            {{ __('filament.channel_application.form.about_footer') }}
        </p>
    </div>

    @if ($pendingApplication)
        <div class="p-4 rounded bg-yellow-50 border border-yellow-400 text-yellow-800 mb-6">
            <h3 class="font-semibold mb-2">
                {{ __('filament.channel_application.form.status_title', ['channel' => optional($pendingApplication->channel)->name]) }}
            </h3>
            <p class="mb-2">
                {{ __('filament.channel_application.form.status_message', ['status' => __(sprintf('filament.channel_application.status.%s', $pendingApplication->status))]) }}
            </p>
            <div class="text-sm text-gray-700">
                <b>{{ __('filament.channel_application.form.submitted_at') }}</b>
                {{ $pendingApplication->created_at->format('d.m.Y H:i') }}
                <br>
                <b>{{ __('filament.channel_application.form.note_label') }}</b>
                {{ $pendingApplication->note }}
            </div>
            @if (!empty($pendingApplication->meta['new_channel']['name'] ?? null))
                <div class="mt-2 text-sm text-gray-700">
                    <b>{{ __('filament.channel_application.form.new_channel_name_label') }}</b>
                    {{ $pendingApplication->meta['new_channel']['name'] }}
                    <br>
                    <b>{{ __('filament.channel_application.form.new_channel_creator_name_label') }}</b>
                    {{ $pendingApplication->meta['new_channel']['creator_name'] }}
                    <br>
                    <b>{{ __('filament.channel_application.form.new_channel_email_label') }}</b>
                    {{ $pendingApplication->meta['new_channel']['email'] }}
                    <br>
                    <b>{{ __('filament.channel_application.form.new_channel_youtube_name_label') }}</b>
                    {{ $pendingApplication->meta['new_channel']['youtube_name'] }}
                </div>
            @endif
            <p class="text-gray-500 text-xs mt-4">
                {{ __('filament.channel_application.form.status_note') }}
            </p>
        </div>
    @else
        <form wire:submit="submit" id="form">
            {{ $this->form }}
            <br>
            <x-filament::button type="submit" color="primary" form="form">
                {{ __('filament.channel_application.form.submit') }}
            </x-filament::button>
        </form>
    @endif
</x-filament-panels::page>