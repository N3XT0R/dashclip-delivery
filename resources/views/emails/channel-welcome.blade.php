@php use App\Facades\Cfg; @endphp
        <!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>{{ $subject ?? 'Bitte bestätige den wöchentlichen Video-Versand' }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f8fafc; font-family:Arial, sans-serif;">
@include('emails.partials.header')

<table role="presentation" width="100%" cellpadding="0" cellspacing="0"
       style="max-width:600px; width:100%; margin:0 auto; background:#ffffff;
              border:1px solid #e2e8f0; border-radius:6px;">
    <tr>
        <td style="padding:24px; color:#0f172a; line-height:1.6; font-size:16px;">
            <h1 style="margin:0 0 16px 0; font-size:20px; font-weight:700;">
                Bitte bestätige den wöchentlichen Video-Versand
            </h1>

            <p>Hi {{ $channel->name ?? 'Liebes Team' }},</p>

            <p>
                dein Kanal wurde in <strong>{{ config('app.name') }}</strong> eingetragen,
                damit du regelmäßig neue Videos direkt zur Veröffentlichung bekommst.
                Bevor der Versand startet, musst du nur kurz deine Teilnahme bestätigen.
            </p>

            <p style="margin-top:16px;">
                Wenn du mit dem wöchentlichen Versand einverstanden bist, klick einfach hier:
            </p>

            <p style="text-align:center; margin:24px 0;">
                <a href="{{ $approveUrl }}"
                   style="display:inline-block; padding:12px 24px; background-color:#2563eb; color:#ffffff;
                          text-decoration:none; border-radius:6px; font-weight:bold;">
                    Teilnahme bestätigen
                </a>
            </p>

            <p>
                Nach der Bestätigung bekommst du automatisch die neuen Videos im gewohnten Rhythmus.
                Wenn du das irgendwann nicht mehr möchtest, reicht eine kurze Mail an
                <a href="mailto:{{ Cfg::get('email_admin_mail', 'email') }}">{{ Cfg::get('email_admin_mail', 'email') }}</a>.
            </p>

            <p style="margin:24px 0 0 0;">
                Viele Grüße<br>Dein {{ config('app.name') }}-Team
            </p>
        </td>
    </tr>
</table>

@include('emails.partials.footer')
</body>
</html>
