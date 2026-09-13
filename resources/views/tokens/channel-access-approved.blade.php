@php
    use App\Facades\Cfg;

    /** @var \App\Models\ActionToken $token */
    $application = $token->subject;
    $channel = $application?->channel;
@endphp

@extends('layouts.app')

@section('title', __('action-tokens.channel_access.title'))
@section('subtitle', $channel?->name ?? __('action-tokens.channel_access.subtitle'))

@section('actions')
    {{-- keine Aktionen --}}
@endsection

@section('robots', 'noindex, nofollow')
@section('content')
    <x-token-action-panel :headline="__('action-tokens.channel_access.headline')">

        <p>
            {{ __('action-tokens.channel_access.thanks') }}
        </p>

        <p>
            {{ __('action-tokens.channel_access.description', [
                'channel' => $channel?->name ?? __('action-tokens.channel_access.subtitle'),
            ]) }}
        </p>

        <p>
            {{ __('action-tokens.channel_access.access_granted') }}
        </p>

        <p>
            {{ __('action-tokens.channel_access.revoke_notice') }}
        </p>

        <div>
            <a href="{{ config('app.url') }}" class="btn">
                {{ __('action-tokens.channel_access.back') }}
            </a>
        </div>

    </x-token-action-panel>
@endsection
