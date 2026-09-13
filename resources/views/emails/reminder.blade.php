<x-email-layout :title="$subject ?? config('app.name')" :message="$message ?? null">
<h1 style="margin:0 0 16px 0; font-size:20px; font-weight:700;">Angebote laufen bald ab</h1>
<p style="margin:0 0 16px 0;">
    Hallo {{ $channel->creator_name ?: 'Liebes Team' }} ({{ $channel->name }}),
</p>
<p style="margin:0 0 16px 0;">
    deine aktuellen Links verfallen
    am {{ $expiresAt->timezone(config('app.timezone'))->format('d.m.Y, H:i') }}.
</p>
<p style="margin:0 0 20px 0;">Nutze den folgenden Button, um die Angebote noch einmal aufzurufen:</p>
<p><x-email-button :url="$offerUrl">Zu den Angeboten</x-email-button></p>
@if($assignments->isNotEmpty())
    <p style="margin:0 0 16px 0;">Folgende Videos werden weiterhin angeboten:</p>
    <ul style="margin:0 0 20px 0; padding-left:20px;">
        @foreach($assignments as $a)
            @php($video = $a->video)
            @php($note = optional($video->clips->first())->note)
            <li>
                {{ $video->original_name ?: basename($video->path) }}@if($note)
                    - {{ $note }}
                @endif
            </li>
        @endforeach
    </ul>
@endif
<p style="margin:0 0 24px 0;">Viele Grüße<br>Dein {{ config('app.name') }}-Team</p>
</x-email-layout>
