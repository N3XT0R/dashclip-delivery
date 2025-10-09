@php use App\Facades\Cfg; @endphp
        <!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>{{ $subject ?? 'Automatische Antwort – bitte nicht direkt antworten' }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f8fafc; font-family:Arial, sans-serif;">
@include('emails.partials.header')

<table role="presentation" width="100%" cellpadding="0" cellspacing="0"
       style="max-width:600px; width:100%; margin:0 auto; background:#ffffff;
                  border:1px solid #e2e8f0; border-radius:6px;">
    <tr>
        <td style="padding:24px; color:#0f172a; line-height:1.6; font-size:16px;">
            <h1 style="margin:0 0 16px 0; font-size:20px; font-weight:700;">
                {{ $subject ?? 'Automatische Antwort – bitte nicht direkt antworten' }}
            </h1>
            
            <div style="margin:0 auto; text-align:left;">
                <x-page slug="email_faq"/>
            </div>

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
