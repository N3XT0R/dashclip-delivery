@php
    /** @var \App\Models\ActionToken $token */
    $channel = $token->subject ?? null;
@endphp

@extends('layouts.app')

@section('title', __('action-tokens.channel_reception_reactivation.success.title'))
@section('subtitle', $channel?->name ?? '')

@section('content')
    <div class="panel" style="max-width: 600px; margin: 0 auto; text-align: center; padding: 32px;">
        <h1 style="font-size: 22px; margin-bottom: 16px; color: var(--color-primary, #2563eb);">
            {{ __('action-tokens.channel_reception_reactivation.success.headline') }}
        </h1>

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

        <hr class="muted-separator" style="margin: 32px 0;">

        <p class="muted" style="font-size: 13px; color: #64748b;">
            &copy; {{ date('Y') }} {{ config('app.name') }}
        </p>
    </div>
@endsection
