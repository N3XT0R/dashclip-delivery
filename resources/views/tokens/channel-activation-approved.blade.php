@php
    use App\Facades\Cfg;

    /** @var \App\Models\ActionToken $token */
    $channel = $token->subject ?? null;
@endphp

@extends('layouts.app')

@section('title', __('action-tokens.channel_activation.title'))
@section('subtitle', $channel?->name ?? __('action-tokens.channel_activation.subtitle'))

@section('content')
    <div class="panel" style="max-width: 600px; margin: 0 auto; text-align: center; padding: 32px;">
        <h1 style="font-size: 22px; margin-bottom: 16px; color: var(--color-primary, #2563eb);">
            {{ __('action-tokens.channel_activation.headline') }}
        </h1>

        <p style="margin-bottom: 16px;">
            {{ __('action-tokens.channel_activation.thanks', [
                'name' => $channel?->name ?? __('action-tokens.channel_activation.subtitle'),
            ]) }}
        </p>

        <p style="line-height: 1.6;">
            {{ __('action-tokens.channel_activation.description') }}
        </p>

        <p style="margin-top: 16px;">
            {{ __('action-tokens.channel_activation.availability_notice') }}
        </p>

        <p style="margin-top: 20px;">
            {{ __('action-tokens.channel_activation.revoke_notice') }}
        </p>

        <div style="margin-top: 24px;">
            <a href="{{ config('app.url') }}" class="btn" style="text-decoration: none;">
                {{ __('action-tokens.channel_activation.back') }}
            </a>
        </div>

        <hr class="muted-separator" style="margin: 32px 0;">

        <p class="muted" style="font-size: 13px; color: #64748b;">
            &copy; {{ date('Y') }} {{ config('app.name') }}
            {{ Cfg::has('email_your_name', 'email')
                ? '/ ' . Cfg::get('email_your_name', 'email', '')
                : '' }}
        </p>
    </div>
@endsection
