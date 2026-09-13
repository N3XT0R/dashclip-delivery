<x-email-layout :title="$subject ?? config('app.name')" :message="$message ?? null">
<h1 style="margin:0 0 16px 0; font-size:20px; font-weight:700;">
    {{ __('mails.channel_access_approved.headline') }}
</h1>

<p>
    {{ __('mails.channel_access_approved.greeting', [
        'name' => $application->user->name ?? __('Hi'),
    ]) }}
</p>

<p>
    {{ __('mails.channel_access_approved.intro') }}
</p>

<p style="font-weight:600; margin:12px 0;">
    {{ $channel->name }}
</p>

<p>
    {{ __('mails.channel_access_approved.access_notice') }}
</p>

<p style="margin:24px 0 0 0;">
    {!! __('mails.channel_access_approved.signature', [
        'app' => config('app.name'),
    ]) !!}
</p>
</x-email-layout>
