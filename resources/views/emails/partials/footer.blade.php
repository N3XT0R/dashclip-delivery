<table role="presentation" width="100%" cellpadding="0" cellspacing="0">
    <tr>
        <td align="center" style="padding:24px 12px; font-family:Arial, Helvetica, sans-serif; font-size:12px; line-height:1.8; color:#526171;">
            © {{ date('Y') }} {{ config('app.name') }}<br>
            <a href="{{ route('impressum') }}" style="color:#526171; text-decoration:underline;">{{ __('public.imprint') }}</a> ·
            <a href="{{ route('datenschutz') }}" style="color:#526171; text-decoration:underline;">{{ __('public.privacy') }}</a> ·
            <a href="{{ route('tos') }}" style="color:#526171; text-decoration:underline;">{{ __('public.terms') }}</a>
        </td>
    </tr>
</table>
