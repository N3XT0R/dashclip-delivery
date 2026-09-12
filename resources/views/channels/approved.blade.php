@php
    use App\Facades\Cfg;
@endphp

@extends('layouts.app')

@section('title', 'Teilnahme bestätigt')
@section('subtitle', $channel->name ?? 'Kanalbestätigung')

@section('actions')
    {{-- keine Aktionen hier --}}
@endsection

@section('robots', 'noindex, nofollow')
@section('content')
    <x-token-action-panel headline="Teilnahme erfolgreich bestätigt">

        <p>
            Vielen Dank, {{ $channel->name ?? 'Liebes Team' }}!
        </p>

        <p>
            Ihr Kanal wurde erfolgreich für den wöchentlichen Video-Versand aktiviert.
            Ab sofort erhalten Sie regelmäßig neue Video-Inhalte direkt über den automatisierten Verteiler.
        </p>

        <p>
            Sie können Ihre Teilnahme jederzeit über Ihr Benutzerkonto oder per E-Mail widerrufen.
        </p>

        <div>
            <a href="{{ config('app.url') }}" class="btn">
                Zur Startseite
            </a>
        </div>

    </x-token-action-panel>
@endsection
