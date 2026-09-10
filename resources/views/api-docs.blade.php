@extends('layouts.app')

@section('title', 'API-Dokumentation')

@section('content')
    <div class="panel">
        <h1 class="text-2xl font-bold mb-4">API-Dokumentation</h1>

        <p class="mb-4">
            Interaktive OpenAPI-Dokumentation für die REST-API. Zum Testen im Swagger-UI
            über den „Authorize"-Button authentifizieren – per OAuth2
            (<code>authorization_code</code> oder <code>client_credentials</code>) oder mit
            einem Personal Access Token (im Standard-Panel unter „OAuth" erstellen) über das
            <code>bearerAuth</code>-Schema.
        </p>

        <table class="w-full text-left">
            <thead>
            <tr>
                <th class="py-2 pr-4">Dokumentation</th>
                <th class="py-2 pr-4">Swagger-UI</th>
                <th class="py-2">OpenAPI-Spec</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($documentations as $doc)
                <tr>
                    <td class="py-2 pr-4">{{ $doc->title }}</td>
                    <td class="py-2 pr-4">
                        <a href="{{ $doc->uiUrl }}" target="_blank" rel="noopener">{{ $doc->uiUrl }}</a>
                    </td>
                    <td class="py-2">
                        <a href="{{ $doc->specUrl }}" target="_blank" rel="noopener">{{ $doc->specUrl }}</a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endsection
