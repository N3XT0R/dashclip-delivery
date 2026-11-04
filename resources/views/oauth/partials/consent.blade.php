{{-- Shared body of the authorization and device consent pages. --}}
<p>{{ __('oauth.consent.intro', ['client' => $client->name]) }}</p>

<p class="muted">{{ __('oauth.consent.signed_in_as', ['name' => $user->name]) }}</p>

@if (count($scopes) > 0)
    <p>{{ __('oauth.consent.permissions') }}</p>
    <ul>
        @foreach ($scopes as $scope)
            <li>{{ $scope->description }}</li>
        @endforeach
    </ul>
@else
    <p>{{ __('oauth.consent.no_permissions') }}</p>
@endif

<p class="muted">{{ __('oauth.consent.notice') }}</p>

<div class="mt-8 flex flex-wrap gap-3">
    <form method="post" action="{{ $approveRoute }}">
        @csrf
        @foreach ($hiddenFields as $name => $value)
            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
        @endforeach
        <button type="submit" class="public-button public-button-primary">{{ __('oauth.consent.approve') }}</button>
    </form>

    <form method="post" action="{{ $approveRoute }}">
        @csrf
        @method('DELETE')
        @foreach ($hiddenFields as $name => $value)
            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
        @endforeach
        <button type="submit" class="btn">{{ __('oauth.consent.deny') }}</button>
    </form>
</div>
