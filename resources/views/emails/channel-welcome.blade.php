@php use App\Facades\Cfg; @endphp
<x-email-layout :title="$subject ?? config('app.name')" :message="$message ?? null">
<h1 style="margin:0 0 16px 0; font-size:20px; font-weight:700;">
    {{__('mails.channel_welcome_email.headline')}}
</h1>
<p>{{__('mails.channel_welcome_email.greeting', ['name' => $channel->name ?? 'Liebes Team'])}}</p>
<p>
    {{__('mails.channel_welcome_email.channel_registered', ['app_name' => config('app.name')])}}
</p>

<p style="margin-top:16px;">
    {{__('mails.channel_welcome_email.weekly_opt_in')}}
</p>

<p style="text-align:center; margin:24px 0;">
    <x-email-button :url="$approveUrl">{{__('mails.channel_welcome_email.approve')}}</x-email-button>
</p>

<p>
    {{__('messages.after_confirmation', [
         'email' => Cfg::get('email_admin_mail', 'email')
     ])}}
</p>

<p style="margin:24px 0 0 0;">
    {{__('mails.channel_welcome_email.signature', ['app_name' => config('app.name')])}}
</p>
</x-email-layout>
