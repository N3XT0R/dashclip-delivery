@php
    /** @var \App\Models\ActionToken $token */
    $channel = $token->subject ?? null;
@endphp

@extends('layouts.app')

@section('title', __('action-tokens.channel_reception_reactivation.success.title'))
@section('subtitle', $channel?->name ?? '')

@section('content')
    <x-token-action-panel :headline="__('action-tokens.channel_reception_reactivation.success.headline')">
        <p style="line-height: 1.6;">
            {{ __('action-tokens.channel_reception_reactivation.success.body', [
                'channel' => $channel?->name ?? '',
            ]) }}
        </p>

        <div style="margin-top: 24px;">
            <a href="{{ config('app.url') }}" class="btn" style="text-decoration: none;">
                {{ __('action-tokens.channel_reception_reactivation.success.back') }}
            </a>
        </div>
    </x-token-action-panel>
@endsection
