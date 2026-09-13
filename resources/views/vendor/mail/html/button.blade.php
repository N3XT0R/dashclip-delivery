@props(['url', 'color' => 'primary', 'align' => 'center'])
<table role="presentation" width="100%" cellpadding="0" cellspacing="0">
<tr><td align="{{ $align }}" style="padding:12px 0;">
<x-email-button :url="$url">{{ $slot }}</x-email-button>
</td></tr>
</table>
