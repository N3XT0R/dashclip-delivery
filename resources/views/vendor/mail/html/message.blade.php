<x-email-layout :message="$message ?? null">
{!! Illuminate\Mail\Markdown::parse($slot) !!}
@isset($subcopy)
<div style="margin-top:24px; padding-top:20px; border-top:1px solid #dce2e8; font-size:12px; color:#526171; word-break:break-all;">
{!! Illuminate\Mail\Markdown::parse($subcopy) !!}
</div>
@endisset
</x-email-layout>
