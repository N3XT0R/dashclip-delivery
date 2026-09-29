<x-email-layout :title="$subject ?? config('app.name')" :message="$message ?? null">
<h1 style="margin:0 0 16px 0; font-size:20px; font-weight:700;">
    {{ __('mails.video_version_switched.headline') }}
</h1>

<p>{{ __('mails.video_version_switched.greeting', ['channel' => $channel->name]) }}</p>

<p>
    {{ __('mails.video_version_switched.body', [
        'video' => $video->original_name,
        'version' => $deliveredVersion->label(),
    ]) }}
</p>

<p>{{ __('mails.video_version_switched.what_to_do') }}</p>

<p style="text-align:center; margin:24px 0;">
    <x-email-button :url="route('filament.standard.tenant')">
        {{ __('mails.video_version_switched.button') }}
    </x-email-button>
</p>

<p style="margin:24px 0 0 0;">
    {{ __('mails.video_version_switched.signature', ['app_name' => config('app.name')]) }}
</p>
</x-email-layout>
