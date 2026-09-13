@php
    $publicLogo = public_path('images/marketing/logo-mail.png');
    $logoSrc = isset($message) && file_exists($publicLogo)
        ? $message->embed($publicLogo)
        : asset('images/marketing/logo-mail.png');
@endphp
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#0c1924; border-radius:16px 16px 0 0; border-bottom:4px solid #f97316;">
    <tr>
        <td style="padding:28px 24px;">
            <a href="{{ config('app.url') }}" style="color:#ffffff; text-decoration:none;">
                <img src="{{ $logoSrc }}" alt="{{ config('app.name') }}" width="64" height="64" style="display:block; width:64px; height:64px; padding:8px; background-color:#ffffff; border-radius:12px; border:0; margin-bottom:16px;">
                <span style="font-family:Arial, Helvetica, sans-serif; font-size:24px; font-weight:700; line-height:1.3; color:#ffffff;">{{ config('app.name') }}</span>
            </a>
            <p style="margin:10px 0 0; color:#b5c2ce; font-size:14px; line-height:1.6;">{{ __('public.footer_tagline') }}</p>
        </td>
    </tr>
</table>
