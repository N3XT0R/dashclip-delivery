<x-filament-panels::page>
    <div class="channel-application-benefits-panel mb-6 rounded border p-4">
        <h2 class="channel-application-benefits-panel__title mb-2 text-lg font-semibold">
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
        <p class="channel-application-benefits-panel__footer text-sm">
            {{ __('filament.channel_application.form.about_footer') }}
        </p>
    </div>
    @if($this->table->getAllSelectableRecordsCount() > 0)
        <div class="mb-6">
            {{$this->table}}
        </div>
    @endif


    @if ($pendingApplication)
        <div class="mb-6 rounded border border-yellow-400 bg-yellow-50 p-4 text-yellow-800 dark:border-yellow-500/50 dark:bg-yellow-950/30 dark:text-yellow-100">
            <h3 class="font-semibold mb-2">
                {{ __('filament.channel_application.form.status_title', ['channel' => $pendingApplication->meta->channel['name'] ?? optional($pendingApplication->channel)->name]) }}
            </h3>
            <p class="mb-2">
                {{ __('filament.channel_application.form.status_message', ['status' => __(sprintf('filament.channel_application.status.%s', $pendingApplication->status))]) }}
            </p>
            <div class="text-sm text-yellow-900 dark:text-yellow-100">
                <b>{{ __('filament.channel_application.form.submitted_at') }}</b>
                {{ $pendingApplication->created_at->format('d.m.Y H:i') }}
                <br>
            </div>
            @if (!empty($pendingApplication->meta->channel['name'] ?? null))
                <div class="mt-2 text-sm text-yellow-900 dark:text-yellow-100">
                    <b>{{ __('filament.channel_application.form.new_channel_name_label') }}</b>
                    {{ $pendingApplication->meta->channel['name'] }}
                    <br>
                    <b>{{ __('filament.channel_application.form.new_channel_creator_name_label') }}</b>
                    {{ $pendingApplication->meta->channel['creator_name'] }}
                    <br>
                    <b>{{ __('filament.channel_application.form.new_channel_email_label') }}</b>
                    {{ $pendingApplication->meta->channel['email'] }}
                    <br>
                    <b>{{ __('filament.channel_application.form.new_channel_youtube_name_label') }}</b>
                    {{ $pendingApplication->meta->channel['youtube_name'] }}
                </div>
            @endif
            <p class="mt-4 text-xs text-yellow-700 dark:text-yellow-200/80">
                {{ __('filament.channel_application.form.status_note') }}
            </p>
        </div>
    @else
        <form wire:submit.prevent="submit" id="form" wire:loading.class="opacity-50 pointer-events-none"
              wire:target="submit">
            {{ $this->form }}
            <br>
            <x-filament::button
                    type="submit"
                    color="primary"
                    form="form"
                    wire:loading.attr="disabled"
                    wire:target="submit"
            >
        <span wire:loading wire:target="submit" class="mr-2 inline-block">
            <svg class="w-4 h-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
            </svg>
        </span>
                {{ __('filament.channel_application.form.submit') }}
            </x-filament::button>
        </form>
    @endif
</x-filament-panels::page>
