@extends('layouts.app')

@section('title', 'API-Dokumentation')

@section('content')
    <div class="panel">
        <h1 class="text-2xl font-bold mb-4">API-Dokumentation</h1>

        <p class="mb-4">
            Interaktive OpenAPI-Dokumentation der REST-API. Zum Ausprobieren im Swagger-UI
            authentifizierst du dich über den „Authorize"-Button – entweder per OAuth2
            (<code>authorization_code</code>) oder mit einem Personal Access Token, den du im
            Standard-Panel unter „OAuth" erstellst.
        </p>

        @if ($authentication)
            <h2 class="text-xl font-semibold mt-6 mb-2">Zuerst: Authentifizierung</h2>
            <p class="mb-2">
                Jede API erwartet einen Bearer-Token. Die Endpunkte, über die du einen Token
                erhältst und erneuerst, sind hier dokumentiert:
            </p>
            <ul class="mb-6" style="margin-left: 18px; list-style: disc;">
                <li>
                    <strong>{{ $authentication->title }}</strong> –
                    <a href="{{ $authentication->uiUrl }}" target="_blank" rel="noopener">Swagger-UI</a>
                    ·
                    <a href="{{ $authentication->specUrl }}" target="_blank" rel="noopener">OpenAPI-Spec</a>
                </li>
            </ul>
        @endif

        <h2 class="text-xl font-semibold mt-6 mb-2">Ressourcen-APIs</h2>
        <table class="w-full text-left">
            <thead>
            <tr>
                <th class="py-2 pr-4">Dokumentation</th>
                <th class="py-2 pr-4">Swagger-UI</th>
                <th class="py-2">OpenAPI-Spec</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($resourceApis as $doc)
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
