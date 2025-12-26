@php
    use App\Facades\Cfg;
@endphp
    <!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <title>{{ $subject }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f8fafc; font-family:Arial, sans-serif;">
@include('emails.partials.header')

<table role="presentation" width="100%" cellpadding="0" cellspacing="0"
       style="max-width:600px; width:100%; margin:0 auto; background:#ffffff;
              border:1px solid #e2e8f0; border-radius:6px;">
    <tr>
        <td style="padding:24px; color:#0f172a; line-height:1.6; font-size:16px;">
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
                        <a href="mailto:{{ $user->email }}" style="color:#0ea5e9; text-decoration:none;">
                            {{ $user->email }}
                        </a>
                    @endif
                </p>
            @endif
            @if (! empty($note))
                <p style="margin:12px 0; padding:12px; background:#fff7ed; border-radius:6px; font-size:15px;">
                    <strong>{{ __('mails.channel_access_request.note_label') }}</strong><br>
                    {{ $note }}
                </p>
            @endif
            @if ($channel)
                <p style="font-weight:600; margin:12px 0;">
                    {{ $channel->name }}
                </p>
            @endif

            <p style="margin-top:16px;">
                {{ __('mails.channel_access_request.instruction') }}
            </p>

            <p style="text-align:center; margin:24px 0;">
                <a href="{{ $approveUrl }}"
                   style="display:inline-block; padding:12px 24px;
                          background-color:#2563eb; color:#ffffff;
                          text-decoration:none; border-radius:6px;
                          font-weight:bold;">
                    {{ __('mails.channel_access_request.approve') }}
                </a>
            </p>
            @if (! empty($expireAt))
                <p style="margin-top:16px; font-size:14px; color:#64748b;">
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
        </td>
    </tr>
</table>

@include('emails.partials.footer')
</body>
</html>
