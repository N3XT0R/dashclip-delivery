@php
    /** @var \App\Models\ActionToken $token */
    $channel = $token->subject ?? null;
@endphp

@extends('layouts.app')

@section('title', __('action-tokens.channel_reception_reactivation.success.title'))
@section('subtitle', $channel?->name ?? '')

@section('robots', 'noindex, nofollow')
@section('content')
    <x-token-action-panel :headline="__('action-tokens.channel_reception_reactivation.success.headline')">
        <p>
            {{ __('action-tokens.channel_reception_reactivation.success.body', [
                'channel' => $channel?->name ?? '',
            ]) }}
        </p>

        <div>
            <a href="{{ config('app.url') }}" class="btn">
                {{ __('action-tokens.channel_reception_reactivation.success.back') }}
            </a>
        </div>
    </x-token-action-panel>
@endsection
