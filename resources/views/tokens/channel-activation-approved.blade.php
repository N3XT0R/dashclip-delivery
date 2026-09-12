@php
    use App\Facades\Cfg;

    /** @var \App\Models\ActionToken $token */
    $channel = $token->subject ?? null;
@endphp

@extends('layouts.app')

@section('title', __('action-tokens.channel_activation.title'))
@section('subtitle', $channel?->name ?? __('action-tokens.channel_activation.subtitle'))

@section('robots', 'noindex, nofollow')
@section('content')
    <x-token-action-panel :headline="__('action-tokens.channel_activation.headline')">

        <p>
            {{ __('action-tokens.channel_activation.thanks', [
                'name' => $channel?->name ?? __('action-tokens.channel_activation.subtitle'),
            ]) }}
        </p>

        <p>
            {{ __('action-tokens.channel_activation.description') }}
        </p>

        <p>
            {{ __('action-tokens.channel_activation.availability_notice') }}
        </p>

        <p>
            {{ __('action-tokens.channel_activation.revoke_notice') }}
        </p>

        <div>
            <a href="{{ config('app.url') }}" class="btn">
                {{ __('action-tokens.channel_activation.back') }}
            </a>
        </div>

    </x-token-action-panel>
@endsection
