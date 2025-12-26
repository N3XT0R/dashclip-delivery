@php use App\Facades\Cfg; @endphp
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
        </td>
    </tr>
</table>

@include('emails.partials.footer')
</body>
</html>
