<x-email-layout :title="$subject ?? config('app.name')" :message="$message ?? null">
<h1 style="margin:0 0 16px 0; font-size:20px; font-weight:700;">
    {{ __('mails.offer_download_ready.headline') }}
</h1>

<p>
    {{ __('mails.offer_download_ready.greeting', ['name' => $user?->name ?? '']) }}
</p>

<p>
    @if ($channelName)
        {{ trans_choice('mails.offer_download_ready.ready', $ready, ['count' => $ready, 'channel' => $channelName]) }}
    @else
        {{ trans_choice('mails.offer_download_ready.ready_without_channel', $ready, ['count' => $ready]) }}
    @endif
</p>

@if ($skipped > 0)
    <p>
        {{ trans_choice('mails.offer_download_ready.skipped', $skipped, ['count' => $skipped]) }}
    </p>
@endif

<p><x-email-button :url="$downloadUrl">{{ __('mails.offer_download_ready.button') }}</x-email-button></p>

<p>
    {{ __('mails.offer_download_ready.validity') }}
</p>

<p style="font-size:13px; color:#6b7280;">
    {{ __('mails.offer_download_ready.opt_out_hint') }}
</p>

<p style="margin:24px 0 0 0;">
    {!! __('mails.offer_download_ready.signature', ['app' => config('app.name')]) !!}
</p>
</x-email-layout>
