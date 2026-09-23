@php($impersonation = app(App\Services\Auth\ImpersonationService::class))
@if ($impersonation->isActive())
    <div style="display:flex; flex-wrap:wrap; align-items:center; gap:12px; justify-content:space-between;
                padding:10px 16px; background:#fef3c7; color:#78350f; font-size:14px;">
        <span>{{ __('impersonation.banner', ['name' => auth()->user()?->name]) }}</span>
        <form method="post" action="{{ route('impersonation.stop') }}">
            @csrf
            <button type="submit"
                    style="border-radius:6px; border:1px solid #78350f; padding:4px 12px; font-weight:600;">
                {{ __('impersonation.stop') }}
            </button>
        </form>
    </div>
@endif
