@props(['title' => null, 'message' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <title>{{ $title ?? config('app.name') }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f5f6f8; color:#182633; font-family:Arial, Helvetica, sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f5f6f8;">
    <tr>
        <td align="center" style="padding:24px 12px;">
            <!--[if mso]><table role="presentation" width="600" align="center"><tr><td><![endif]-->
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px; margin:0 auto;">
                <tr><td>@include('emails.partials.header')</td></tr>
                <tr>
                    <td style="padding:32px 24px; background-color:#ffffff; border:1px solid #dce2e8; border-top:0; border-radius:0 0 16px 16px; font-size:16px; line-height:1.7; overflow-wrap:anywhere; word-break:break-word;">
                        {{ $slot }}
                    </td>
                </tr>
                <tr><td>@include('emails.partials.footer')</td></tr>
            </table>
            <!--[if mso]></td></tr></table><![endif]-->
        </td>
    </tr>
</table>
</body>
</html>
