@php use App\Facades\Cfg; @endphp
        <!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>{{ $subject ?? 'Willkommen beim wöchentlichen Video-Versand' }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f8fafc; font-family:Arial, sans-serif;">
@include('emails.partials.header')

<table role="presentation" width="100%" cellpadding="0" cellspacing="0"
       style="max-width:600px; width:100%; margin:0 auto; background:#ffffff;
              border:1px solid #e2e8f0; border-radius:6px;">
    <tr>
        <td style="padding:24px; color:#0f172a; line-height:1.6; font-size:16px;">
            <h1 style="margin:0 0 16px 0; font-size:20px; font-weight:700;">
                Willkommen beim wöchentlichen Video-Versand
            </h1>

            <p>
                Hallo {{ $channel->creator_name ?: 'Liebes Team' }} ({{ $channel->name }}),
            </p>

            <p>
                vielen Dank für Ihre Registrierung als Kanalpartner.
                Über diesen Service erhalten Sie regelmäßig neue Videos direkt zur Veröffentlichung bereitgestellt.
            </p>

            <p>
                Damit wir Ihren Kanal aktivieren und in den wöchentlichen Versand aufnehmen können,
                bestätigen Sie bitte Ihre Teilnahme über den folgenden Link:
            </p>

            <p style="text-align:center; margin:24px 0;">
                <a href="{{ $approveUrl }}"
                   style="display:inline-block; padding:12px 24px; background-color:#2563eb; color:#ffffff;
                          text-decoration:none; border-radius:6px; font-weight:bold;">
                    Teilnahme bestätigen
                </a>
            </p>

            <p>
                Nach der Bestätigung wird Ihr Kanal automatisch in den wöchentlichen Verteiler aufgenommen.
                Sie können Ihre Teilnahme jederzeit über Ihr Benutzerkonto oder per Antwort auf diese Mail widerrufen.
            </p>

            <p style="margin:24px 0 0 0;">
                Viele Grüße<br>
                {{ config('app.name') }}
                {{ Cfg::has('email_your_name', 'email') ? '/' . Cfg::get('email_your_name', 'email', '') : '' }}
            </p>
        </td>
    </tr>
</table>

@include('emails.partials.footer')
</body>
</html>
