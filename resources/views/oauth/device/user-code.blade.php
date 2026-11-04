@extends('layouts.app')

@section('title', __('oauth.device.title'))
@section('robots', 'noindex, nofollow')
@section('content')
    <x-token-action-panel :headline="__('oauth.device.headline')">
        @if (session('status') === 'authorization-approved')
            <p class="flash flash--ok">{{ __('oauth.device.approved') }}</p>
        @elseif (session('status') === 'authorization-denied')
            <p class="flash flash--err">{{ __('oauth.device.denied') }}</p>
        @endif

        @error('user_code')
            <p class="flash flash--err">{{ __('oauth.device.invalid_code') }}</p>
        @enderror

        <p>{{ __('oauth.device.intro') }}</p>

        <form method="get" action="{{ route('passport.device') }}" class="mt-6 flex flex-wrap items-end gap-3">
            <label class="flex flex-col gap-2">
                <span class="font-semibold">{{ __('oauth.device.code_label') }}</span>
                <input
                    type="text"
                    name="user_code"
                    value="{{ old('user_code') }}"
                    placeholder="{{ __('oauth.device.code_placeholder') }}"
                    autocomplete="one-time-code"
                    autocapitalize="characters"
                    spellcheck="false"
                    required
                    class="chip min-h-11 px-4 text-lg tracking-widest uppercase"
                >
            </label>
            <button type="submit" class="public-button public-button-primary">{{ __('oauth.device.continue') }}</button>
        </form>
    </x-token-action-panel>
@endsection
