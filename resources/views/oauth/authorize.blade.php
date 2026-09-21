@extends('layouts.app')

@section('title', __('oauth.consent.title'))
@section('robots', 'noindex, nofollow')
@section('content')
    <x-token-action-panel :headline="__('oauth.consent.headline')">
        @include('oauth.partials.consent', [
            'approveRoute' => route('passport.authorizations.approve'),
            'hiddenFields' => [
                'state' => $request->state,
                'client_id' => $client->getKey(),
                'auth_token' => $authToken,
            ],
        ])
    </x-token-action-panel>
@endsection
