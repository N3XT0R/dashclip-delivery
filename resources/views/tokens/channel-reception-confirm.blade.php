@php
    /** @var \App\Models\ActionToken $token */
    $channel = $token->subject ?? null;
@endphp

@extends('layouts.app')

@section('title', __('action-tokens.channel_reception_reactivation.confirm.title'))
@section('subtitle', $channel?->name ?? '')

@section('content')
    <x-token-action-panel :headline="__('action-tokens.channel_reception_reactivation.confirm.headline')">
        <p style="line-height: 1.6; margin-bottom: 24px;">
            {!! __('action-tokens.channel_reception_reactivation.confirm.body', [
                'channel' => e($channel?->name ?? ''),
            ]) !!}
        </p>

        <form method="POST"
              action="{{ route('tokens.store', ['purpose' => $purpose->value, 'token' => $plainToken]) }}">
            @csrf
            <button type="submit" class="btn"
                    style="padding:12px 24px; background-color:#2563eb; color:#ffffff;
                           border:none; border-radius:6px; font-weight:bold; cursor:pointer;
                           font-size:16px; text-decoration:none;">
                {{ __('action-tokens.channel_reception_reactivation.confirm.cta') }}
            </button>
        </form>
    </x-token-action-panel>
@endsection
