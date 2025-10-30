@php
    use App\Facades\Cfg;
@endphp

@extends('layouts.app')

@section('title', 'Teilnahme bestätigt')
@section('subtitle', $channel->name ?? 'Kanalbestätigung')

@section('actions')
    {{-- keine Aktionen hier --}}
@endsection

@section('content')
    <div class="panel" style="max-width: 600px; margin: 0 auto; text-align: center; padding: 32px;">
        <h1 style="font-size: 22px; margin-bottom: 16px; color: var(--color-primary, #2563eb);">
            Teilnahme erfolgreich bestätigt
        </h1>

        <p style="margin-bottom: 16px;">
            Vielen Dank, {{ $channel->name ?? 'Liebes Team' }}!
        </p>

        <p style="line-height: 1.6;">
            Ihr Kanal wurde erfolgreich für den wöchentlichen Video-Versand aktiviert.
            Ab sofort erhalten Sie regelmäßig neue Video-Inhalte direkt über den automatisierten Verteiler.
        </p>

        <p style="margin-top: 20px;">
            Sie können Ihre Teilnahme jederzeit über Ihr Benutzerkonto oder per E-Mail widerrufen.
        </p>

        <div style="margin-top: 24px;">
            <a href="{{ config('app.url') }}" class="btn" style="text-decoration: none;">
                Zur Startseite
            </a>
        </div>

        <hr class="muted-separator" style="margin: 32px 0;">

        <p class="muted" style="font-size: 13px; color: #64748b;">
            &copy; {{ date('Y') }} {{ config('app.name') }}
            {{ Cfg::has('email_your_name', 'email') ? '/ ' . Cfg::get('email_your_name', 'email', '') : '' }}
        </p>
    </div>
@endsection
