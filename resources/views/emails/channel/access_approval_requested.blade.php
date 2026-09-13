<x-email-layout :title="$subject ?? config('app.name')" :message="$message ?? null">
<h1 style="margin:0 0 16px 0; font-size:20px; font-weight:700;">
    {{ __('mails.channel_access_request.headline') }}
</h1>

<p>
    {{ __('mails.channel_access_request.greeting', [
        'name' => $channel->creator_name ?? $channel->name ?? __('mails.channel_access.subtitle'),
    ]) }}
</p>

<p>
    {{ __('mails.channel_access_request.intro') }}
</p>
@if (! empty($user))
    <p style="margin:12px 0; padding:12px; background:#f8fafc; border-radius:6px;">
        <strong>{{ __('mails.channel_access_request.requested_by') }}</strong><br>
        {{ $user->name ?? __('mails.common.unknown_user') }}
        @if(! empty($user->email))
            <br>
            <a href="mailto:{{ $user->email }}" style="color:#9a3412; text-decoration:none;">
                {{ $user->email }}
            </a>
        @endif
    </p>
@endif
@if (! empty($note))
    <p style="margin:12px 0; padding:12px; background:#fff7ed; border-radius:6px; font-size:15px;">
        <strong>{{ __('mails.channel_access_request.note_label') }}</strong><br>
        {!!
            str($note)
            ->markdown(['renderer' => ['soft_break' => "<br />"]])
            ->replace(['<p>', '</p>'], '')
            ->sanitizeHtml()
        !!}
    </p>
@endif
@if ($channel)
    <p style="font-weight:600; margin:12px 0;">
        {{__('mails.common.channel')}} {{ $channel->name }}
    </p>
@endif

<p style="margin-top:16px;">
    {{ __('mails.channel_access_request.instruction') }}
</p>

<p style="text-align:center; margin:24px 0;">
    <x-email-button :url="$approveUrl">{{ __('mails.channel_access_request.approve') }}</x-email-button>
</p>
@if (! empty($expireAt))
    <p style="margin-top:16px; font-size:14px; color:#526171;">
        {{ __('mails.common.expires_at', [
            'date' => $expireAt
                ->timezone(config('app.timezone'))
                ->locale(app()->getLocale())
                ->translatedFormat('d. F Y H:i'),
        ]) }}
    </p>
@endif


<p>
    {{ __('mails.channel_access_request.outro') }}
</p>

<p style="margin-top:12px;">
    {{ __('mails.channel_access_request.revoke_hint') }}
</p>

<p style="margin:24px 0 0 0;">
    {!! __('mails.channel_access_request.signature', [
        'app' => config('app.name'),
    ]) !!}
</p>
</x-email-layout>
