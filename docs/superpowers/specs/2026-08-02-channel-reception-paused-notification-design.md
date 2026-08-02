# Channel Reception Paused — Notification & Reactivation Design

**Date:** 2026-08-02
**Status:** Approved

---

## Scope

When an admin pauses video reception for a channel (`is_video_reception_paused` toggles `false → true`), the channel owner receives an email notifying them. The email contains a reactivation link backed by the existing `ActionToken` system. The reactivation flow is two-step: the owner must actively confirm on a dedicated page before the token is consumed and the channel is reactivated.

No email is sent when an admin manually reactivates a channel via the admin panel.

---

## Architecture

### Event

`App\Events\Channel\ChannelVideoReceptionPaused`

- Implements `ShouldQueue`, `ShouldDispatchAfterCommit`
- Carries the `Channel` model
- Fired by `ChannelObserver::updated()` when `is_video_reception_paused` changes from `false` to `true`

### Observer change

`App\Observers\ChannelObserver::updated()` is added:

```php
public function updated(Channel|Model $model): void
{
    if ($model->wasChanged('is_video_reception_paused')
        && $model->is_video_reception_paused === true
        && $model->getOriginal('is_video_reception_paused') === false
    ) {
        event(new ChannelVideoReceptionPaused($model));
    }
}
```

### Listener — send mail

`App\Listeners\SendChannelVideoReceptionPausedMail`

- Handles `ChannelVideoReceptionPaused`
- Guards against empty channel email
- Delegates to `MailService::sendChannelVideoReceptionPausedMail(Channel $channel)`

### MailService method

`sendChannelVideoReceptionPausedMail(Channel $channel)`

- Issues `ActionToken` with purpose `CHANNEL_RECEPTION_REACTIVATION`, subject = `$channel`, expiry = +1 month
- Queues `ChannelVideoReceptionPausedMail` to `$channel->email`

### Mail class

`App\Mail\ChannelVideoReceptionPausedMail` extends `AbstractLoggedMail`

View data:
- `$channel`
- `$expireAt`
- `$reactivateUrl` — `route('tokens.update', ['purpose' => 'channel_reception_reactivation', 'token' => $plainToken])`

### Token enum

`TokenPurposeEnum::CHANNEL_RECEPTION_REACTIVATION = 'channel_reception_reactivation'`

---

## Two-Step Token Flow (Option A)

### Step 1 — Confirmation page (GET, token NOT consumed)

`TokenApprovalController::update()` is extended with a pre-consume check:

```
GET /action-tokens/approve/channel_reception_reactivation/{token}
```

For this purpose the controller validates the token exists and is unused/unexpired (via `ActionTokenRepository::findValid()`), then returns the confirmation view **without consuming the token**.

For all other existing purposes the GET behavior is unchanged (consume immediately).

### Step 2 — Confirm & consume (POST)

New route:
```
POST /action-tokens/approve/{purpose}/{token}  →  TokenApprovalController::store()
```

`store()` consumes the token via `ActionTokenService::consume()` and returns the success view. The actual channel state change is handled by the `ActionTokenConsumed` listener.

### Listener — reactivate channel

`App\Listeners\Channel\HandleChannelReceptionReactivation`

- Handles `ActionTokenConsumed`
- Guards: purpose must be `CHANNEL_RECEPTION_REACTIVATION`, subject must be a `Channel`
- Sets `is_video_reception_paused = false` and saves the channel

---

## Views

| Path | Purpose |
|---|---|
| `resources/views/emails/channel/video_reception_paused.blade.php` | Email to channel owner |
| `resources/views/tokens/channel-reception-confirm.blade.php` | Step 1: confirmation page with POST form |
| `resources/views/tokens/channel-reception-reactivated.blade.php` | Step 2: success page after reactivation |

`TokenApprovalController::resolveViewForPurpose()` is extended with the two new views.

---

## Language files

Keys added to `lang/de/mails.php` and `lang/en/mails.php`:

```
mails.channel_reception_paused.subject
mails.channel_reception_paused.headline
mails.channel_reception_paused.greeting
mails.channel_reception_paused.body
mails.channel_reception_paused.reactivate_cta
mails.channel_reception_paused.expire_note
mails.channel_reception_paused.signature
```

Keys added to `lang/de/action-tokens.php` and `lang/en/action-tokens.php`:

```
action-tokens.channel_reception_reactivation.confirm.title
action-tokens.channel_reception_reactivation.confirm.headline
action-tokens.channel_reception_reactivation.confirm.body
action-tokens.channel_reception_reactivation.confirm.cta
action-tokens.channel_reception_reactivation.success.title
action-tokens.channel_reception_reactivation.success.headline
action-tokens.channel_reception_reactivation.success.body
action-tokens.channel_reception_reactivation.success.back
```

---

## Files changed / created

| File | Action |
|---|---|
| `app/Enum/TokenPurposeEnum.php` | Add `CHANNEL_RECEPTION_REACTIVATION` case |
| `app/Events/Channel/ChannelVideoReceptionPaused.php` | Create |
| `app/Observers/ChannelObserver.php` | Add `updated()` |
| `app/Listeners/SendChannelVideoReceptionPausedMail.php` | Create |
| `app/Services/MailService.php` | Add `sendChannelVideoReceptionPausedMail()` |
| `app/Mail/ChannelVideoReceptionPausedMail.php` | Create |
| `resources/views/emails/channel/video_reception_paused.blade.php` | Create |
| `app/Listeners/Channel/HandleChannelReceptionReactivation.php` | Create |
| `app/Http/Controllers/TokenApprovalController.php` | Add pre-consume GET path + `store()` method |
| `routes/web.php` | Add POST route `tokens.store` |
| `resources/views/tokens/channel-reception-confirm.blade.php` | Create |
| `resources/views/tokens/channel-reception-reactivated.blade.php` | Create |
| `lang/de/mails.php` | Add keys |
| `lang/en/mails.php` | Add keys |
| `lang/de/action-tokens.php` | Add keys |
| `lang/en/action-tokens.php` | Add keys |

---

## Edge cases

- **Channel email is empty:** `SendChannelVideoReceptionPausedMail` exits early — no token issued, no mail queued.
- **Token already consumed:** Step 1 GET aborts with 410 Gone (existing `findValid()` behavior).
- **Token expired:** Same — 410 Gone.
- **Channel already active when token consumed:** `HandleChannelReceptionReactivation` saves `is_video_reception_paused = false` regardless (idempotent, no harm).
- **Admin re-pauses while token is live:** The existing unconsumed token is deleted before a new one is issued. The old reactivation link becomes invalid and returns HTTP 410 Gone; only the freshest link works.
