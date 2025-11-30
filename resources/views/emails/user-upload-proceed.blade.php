@php use App\Facades\Cfg; @endphp
        <!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Upload verarbeitet – {{ config('app.name') }}</title>
</head>
<body style="font-family:Arial, sans-serif; background:#f8fafc; margin:0;">
@include('emails.partials.header')

<table width="100%" cellpadding="0" cellspacing="0"
       style="max-width:600px; margin:0 auto; background:#fff; border:1px solid #e2e8f0; border-radius:6px;">
    <tr>
        <td style="padding:24px; color:#0f172a; font-size:15px; line-height:1.6;">

            <h1 style="font-size:20px; font-weight:700; margin:0 0 16px;">
                Upload verarbeitet
            </h1>

            {{-- Datum / Zeit --}}
            <p style="font-size:13px; color:#64748b; margin:0 0 18px;">
                Stand: {{ $date->format('d.m.Y H:i') }} Uhr
            </p>

            <p>Hallo {{ $user->name }},</p>

            <p>
                dein Upload wurde erfolgreich verarbeitet. Die Datei
                <strong>{{ $filename }}</strong> wurde vom System vollständig eingelesen und steht nun für die weiteren
                Schritte zur Verfügung.
            </p>

            @if(!empty($note))
                <p>
                    <strong>Hinweis:</strong><br>
                    <em>{{ $note }}</em>
                </p>
            @endif

            <p>
                Über den folgenden Link kannst du dich jederzeit anmelden, um die Daten einzusehen oder weitere Dateien
                hochzuladen.
            </p>

            <p style="margin:24px 0 0 0;">
                Viele Grüße<br>
                Dein {{ config('app.name') }}-Team
            </p>
        </td>
    </tr>
</table>

@include('emails.partials.footer')
</body>
</html>
