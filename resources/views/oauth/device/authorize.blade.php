@extends('layouts.app')

@section('title', __('oauth.device.title'))
@section('robots', 'noindex, nofollow')
@section('content')
    <x-token-action-panel :headline="__('oauth.consent.headline')">
        <p class="flash">{{ __('oauth.device.consent_hint') }}</p>

        @include('oauth.partials.consent', [
            'approveRoute' => route('passport.device.authorizations.approve'),
            'hiddenFields' => [
                'auth_token' => $authToken,
            ],
        ])
    </x-token-action-panel>
@endsection
