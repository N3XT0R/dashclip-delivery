<x-email-layout :title="$subject ?? config('app.name')" :message="$message ?? null">
<h1 style="margin:0 0 16px 0; font-size:20px; font-weight:700;">
    {{ __('mails.user_inactivity_reminder.headline') }}
</h1>
<p style="margin:0 0 16px 0;">
    {{ __('mails.user_inactivity_reminder.greeting', ['name' => $user->name]) }}
</p>
<p style="margin:0 0 20px 0;">
    {{ __($lastLoginAt ? 'mails.user_inactivity_reminder.body' : 'mails.user_inactivity_reminder.body_without_login', [
        'date' => $lastLoginAt?->timezone(config('app.timezone'))->format('d.m.Y'),
        'app' => config('app.name'),
    ]) }}
</p>
<p><x-email-button :url="$loginUrl">{{ __('mails.user_inactivity_reminder.cta') }}</x-email-button></p>
<p style="margin:0 0 20px 0; font-size:13px; color:#526171;">
    {{ __('mails.user_inactivity_reminder.opt_out_hint') }}
</p>
<p style="margin:0 0 24px 0;">
    {!! __('mails.user_inactivity_reminder.signature', ['app' => config('app.name')]) !!}
</p>
</x-email-layout>
