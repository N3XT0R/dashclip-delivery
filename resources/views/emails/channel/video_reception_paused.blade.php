<x-email-layout :title="$subject ?? config('app.name')" :message="$message ?? null">
<h1 style="margin:0 0 16px 0; font-size:20px; font-weight:700;">
    {{ __('mails.channel_reception_paused.headline') }}
</h1>

<p>{{ __('mails.channel_reception_paused.greeting') }}</p>

<p>
    {!! __('mails.channel_reception_paused.body', ['channel' => e($channel->name)]) !!}
</p>

<p style="text-align:center; margin:24px 0;">
    <x-email-button :url="$reactivateUrl">{{ __('mails.channel_reception_paused.reactivate_cta') }}</x-email-button>
</p>

<p style="margin-top:16px; font-size:14px; color:#526171;">
    {{ __('mails.common.expires_at', [
        'date' => $expireAt
            ->timezone(config('app.timezone'))
            ->locale(app()->getLocale())
            ->translatedFormat('d. F Y H:i'),
    ]) }}
</p>

<p style="margin:24px 0 0 0;">
    {!! __('mails.channel_reception_paused.signature', ['app' => config('app.name')]) !!}
</p>
</x-email-layout>
