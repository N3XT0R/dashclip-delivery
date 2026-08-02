@props(['headline' => ''])

<div class="panel" style="max-width: 600px; margin: 0 auto; text-align: center; padding: 32px;">
    <h1 style="font-size: 22px; margin-bottom: 16px; color: var(--color-primary, #2563eb);">
        {{ $headline }}
    </h1>

    {{ $slot }}

    <hr class="muted-separator" style="margin: 32px 0;">

    <p class="muted" style="font-size: 13px; color: #64748b;">
        &copy; {{ date('Y') }} {{ config('app.name') }}
    </p>
</div>
