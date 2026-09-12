@php
    /** @var \App\Models\ActionToken $token */
    $channel = $token->subject ?? null;
@endphp

@extends('layouts.app')

@section('title', __('action-tokens.channel_reception_reactivation.confirm.title'))
@section('subtitle', $channel?->name ?? '')

@section('robots', 'noindex, nofollow')
@section('content')
    <x-token-action-panel :headline="__('action-tokens.channel_reception_reactivation.confirm.headline')">
        <p>
            {!! __('action-tokens.channel_reception_reactivation.confirm.body', [
                'channel' => e($channel?->name ?? ''),
            ]) !!}
        </p>

        <form method="POST"
              action="{{ route('tokens.store', ['purpose' => $purpose->value, 'token' => $plainToken]) }}">
            @csrf
            <x-public.button type="submit">
                {{ __('action-tokens.channel_reception_reactivation.confirm.cta') }}
            </x-public.button>
        </form>
    </x-token-action-panel>
@endsection
