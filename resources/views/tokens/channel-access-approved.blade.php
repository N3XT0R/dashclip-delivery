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

@section('content')
    <div class="panel" style="max-width: 600px; margin: 0 auto; text-align: center; padding: 32px;">
        <h1 style="font-size: 22px; margin-bottom: 16px; color: var(--color-primary, #2563eb);">
            {{ __('action-tokens.channel_access.headline') }}
        </h1>

        <p style="margin-bottom: 16px;">
            {{ __('action-tokens.channel_access.thanks') }}
        </p>

        <p style="line-height: 1.6;">
            {{ __('action-tokens.channel_access.description', [
                'channel' => $channel?->name ?? __('action-tokens.channel_access.subtitle'),
            ]) }}
        </p>

        <p style="margin-top: 20px;">
            {{ __('action-tokens.channel_access.access_granted') }}
        </p>

        <p style="margin-top: 12px;">
            {{ __('action-tokens.channel_access.revoke_notice') }}
        </p>

        <div style="margin-top: 24px;">
            <a href="{{ config('app.url') }}" class="btn" style="text-decoration: none;">
                {{ __('action-tokens.channel_access.back') }}
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
