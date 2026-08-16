<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>{{ __('mails.user_inactivity_reminder.subject') }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f8fafc; font-family:Arial, sans-serif;">
@include('emails.partials.header')
<table role="presentation" width="100%" cellpadding="0" cellspacing="0"
       style="max-width:600px; width:100%; margin:0 auto; background:#ffffff; border:1px solid #e2e8f0; border-radius:6px;">
    <tr>
        <td style="padding:24px; color:#0f172a; line-height:1.6; font-size:16px;">
            <h1 style="margin:0 0 16px 0; font-size:20px; font-weight:700;">
                {{ __('mails.user_inactivity_reminder.headline') }}
            </h1>
            <p style="margin:0 0 16px 0;">
                {{ __('mails.user_inactivity_reminder.greeting', ['name' => $user->name]) }}
            </p>
            <p style="margin:0 0 20px 0;">
                {{ __('mails.user_inactivity_reminder.body', [
                    'date' => $lastLoginAt->timezone(config('app.timezone'))->format('d.m.Y'),
                    'app' => config('app.name'),
                ]) }}
            </p>
            <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 20px 0;">
                <tr>
                    <td align="center" style="border-radius:4px; background:#22c55e;">
                        <a href="{{ $loginUrl }}" target="_blank"
                           style="display:inline-block; padding:12px 20px; font-size:14px; font-weight:700; color:#ffffff; text-decoration:none;">
                            {{ __('mails.user_inactivity_reminder.cta') }}
                        </a>
                    </td>
                </tr>
            </table>
            <p style="margin:0 0 20px 0; font-size:13px; color:#64748b;">
                {!! __('mails.user_inactivity_reminder.opt_out_hint') !!}
            </p>
            <p style="margin:0 0 24px 0;">
                {!! __('mails.user_inactivity_reminder.signature', ['app' => config('app.name')]) !!}
            </p>
        </td>
    </tr>
</table>
@include('emails.partials.footer')
</body>
</html>
