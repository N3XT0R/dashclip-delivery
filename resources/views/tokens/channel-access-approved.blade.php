@php
    use App\Facades\Cfg;

    /** @var \App\Models\ActionToken $token */
    $channel = $token->subject ?? null;
@endphp

@extends('layouts.app')

@section('title', __('Teilnahme bestätigt'))
@section('subtitle', $channel?->name ?? __('Kanalbestätigung'))

@section('content')
    <div class="panel" style="max-width: 600px; margin: 0 auto; text-align: center; padding: 32px;">
        <h1 style="font-size: 22px; margin-bottom: 16px; color: var(--color-primary, #2563eb);">
            {{ __('Teilnahme erfolgreich bestätigt') }}
        </h1>

        <p style="margin-bottom: 16px;">
            {{ __('Vielen Dank, :name!', ['name' => $channel?->name ?? __('liebes Team')]) }}
        </p>

        <p style="line-height: 1.6;">
            {{ __('Der Zugriff auf den Kanal wurde erfolgreich freigegeben.') }}
            {{ __('Ab sofort steht der Kanal den berechtigten Nutzern vollständig zur Verfügung.') }}
        </p>

        <p style="margin-top: 20px;">
            {{ __('Die Freigabe kann jederzeit über das Benutzerkonto oder durch einen Administrator widerrufen werden.') }}
        </p>

        <div style="margin-top: 24px;">
            <a href="{{ config('app.url') }}" class="btn" style="text-decoration: none;">
                {{ __('Zur Startseite') }}
            </a>
        </div>

        <hr class="muted-separator" style="margin: 32px 0;">

        <p class="muted" style="font-size: 13px; color: #64748b;">
            &copy; {{ date('Y') }} {{ config('app.name') }}
            {{ Cfg::has('email_your_name', 'email') ? '/ ' . Cfg::get('email_your_name', 'email', '') : '' }}
        </p>
    </div>
@endsection
