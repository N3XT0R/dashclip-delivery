@php
    /** @var \App\Models\ActionToken $token */
    $channel = $token->subject ?? null;
@endphp

@extends('layouts.app')

@section('title', __('action-tokens.channel_reception_reactivation.confirm.title'))
@section('subtitle', $channel?->name ?? '')

@section('content')
    <div class="panel" style="max-width: 600px; margin: 0 auto; text-align: center; padding: 32px;">
        <h1 style="font-size: 22px; margin-bottom: 16px; color: var(--color-primary, #2563eb);">
            {{ __('action-tokens.channel_reception_reactivation.confirm.headline') }}
        </h1>

        <p style="line-height: 1.6; margin-bottom: 24px;">
            {!! __('action-tokens.channel_reception_reactivation.confirm.body', [
                'channel' => $channel?->name ?? '',
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

        <hr class="muted-separator" style="margin: 32px 0;">

        <p class="muted" style="font-size: 13px; color: #64748b;">
            &copy; {{ date('Y') }} {{ config('app.name') }}
        </p>
    </div>
@endsection
