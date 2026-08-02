# Channel Reception Paused Notification Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** When an admin pauses video reception for a channel, send the channel owner a notification email with a two-step reactivation link backed by the existing ActionToken system.

**Architecture:** A `ChannelVideoReceptionPaused` event is fired by `ChannelObserver::updated()` when `is_video_reception_paused` flips `false → true`. A listener queues a notification email containing a signed token URL. The reactivation flow is two-step: GET shows a confirmation page (token not consumed); POST consumes the token, which fires `ActionTokenConsumed`, handled by `HandleChannelReceptionReactivation` to set `is_video_reception_paused = false`.

**Tech Stack:** Laravel 11, PHPUnit 12, existing `ActionToken` / `ActionTokenService` / `ChannelObserver` infrastructure.

---

## File Map

| Action | Path |
|---|---|
| Modify | `app/Enum/TokenPurposeEnum.php` |
| Modify | `app/Services/ActionTokenService.php` |
| Create | `app/Events/Channel/ChannelVideoReceptionPaused.php` |
| Modify | `app/Observers/ChannelObserver.php` |
| Create | `app/Listeners/SendChannelVideoReceptionPausedMail.php` |
| Modify | `app/Services/MailService.php` |
| Create | `app/Mail/ChannelVideoReceptionPausedMail.php` |
| Create | `resources/views/emails/channel/video_reception_paused.blade.php` |
| Create | `app/Listeners/Channel/HandleChannelReceptionReactivation.php` |
| Modify | `app/Http/Controllers/TokenApprovalController.php` |
| Modify | `routes/web.php` |
| Create | `resources/views/tokens/channel-reception-confirm.blade.php` |
| Create | `resources/views/tokens/channel-reception-reactivated.blade.php` |
| Modify | `lang/de/mails.php` |
| Modify | `lang/en/mails.php` |
| Modify | `lang/de/action-tokens.php` |
| Modify | `lang/en/action-tokens.php` |
| Create | `tests/Integration/Observers/ChannelObserverTest.php` |
| Create | `tests/Integration/Listeners/SendChannelVideoReceptionPausedMailTest.php` |
| Create | `tests/Integration/Listeners/Channel/HandleChannelReceptionReactivationTest.php` |
| Create | `tests/Integration/Mail/ChannelVideoReceptionPausedMailTest.php` |
| Modify | `tests/Feature/Http/Controllers/TokenApprovalControllerTest.php` |
| Modify | `tests/Integration/Services/ActionTokenServiceTest.php` |

---

## Task 1: Add `CHANNEL_RECEPTION_REACTIVATION` to `TokenPurposeEnum` + `ActionTokenService::findValid()`

The enum case is needed by every subsequent task. `findValid()` on the service lets the controller peek at a token without consuming it (used by the GET confirmation step).

**Files:**
- Modify: `app/Enum/TokenPurposeEnum.php`
- Modify: `app/Services/ActionTokenService.php`
- Modify: `tests/Integration/Services/ActionTokenServiceTest.php`

- [ ] **Step 1: Write a failing test for `ActionTokenService::findValid()`**

Open `tests/Integration/Services/ActionTokenServiceTest.php` and add at the end of the class:

```php
public function testFindValidReturnsTokenWhenValidAndUnused(): void
{
    $service = $this->app->make(\App\Services\ActionTokenService::class);
    $channel = \App\Models\Channel::factory()->create();

    $plainToken = $service->issue(
        \App\Enum\TokenPurposeEnum::CHANNEL_RECEPTION_REACTIVATION,
        subject: $channel,
        expiresAt: \Carbon\Carbon::now()->addMonth(),
    );

    $found = $service->findValid(
        \App\Enum\TokenPurposeEnum::CHANNEL_RECEPTION_REACTIVATION,
        $plainToken
    );

    $this->assertNotNull($found);
    $this->assertSame($channel->getKey(), $found->subject_id);
}

public function testFindValidReturnsNullAfterConsumption(): void
{
    $service = $this->app->make(\App\Services\ActionTokenService::class);
    $channel = \App\Models\Channel::factory()->create();

    $plainToken = $service->issue(
        \App\Enum\TokenPurposeEnum::CHANNEL_RECEPTION_REACTIVATION,
        subject: $channel,
        expiresAt: \Carbon\Carbon::now()->addMonth(),
    );

    $service->consume(\App\Enum\TokenPurposeEnum::CHANNEL_RECEPTION_REACTIVATION, $plainToken);

    $this->assertNull(
        $service->findValid(\App\Enum\TokenPurposeEnum::CHANNEL_RECEPTION_REACTIVATION, $plainToken)
    );
}
```

- [ ] **Step 2: Run to confirm failure**

```bash
php artisan test --filter=testFindValidReturnsTokenWhenValidAndUnused
```

Expected: FAIL — `CHANNEL_RECEPTION_REACTIVATION` undefined + `findValid` undefined

- [ ] **Step 3: Add enum case**

In `app/Enum/TokenPurposeEnum.php`, add the new case:

```php
enum TokenPurposeEnum: string
{
    case CHANNEL_ACCESS_APPROVAL = 'channel_access_approval';
    case CHANNEL_ACTIVATION_APPROVAL = 'channel_activation_approval';
    case CHANNEL_RECEPTION_REACTIVATION = 'channel_reception_reactivation';
}
```

- [ ] **Step 4: Add `findValid()` to `ActionTokenService`**

In `app/Services/ActionTokenService.php`, add after the `issue()` method:

```php
/**
 * Find a valid (not consumed, not expired) token without consuming it.
 */
public function findValid(TokenPurposeEnum $purpose, string $plainToken): ?ActionToken
{
    $tokenHash = hash('sha256', $plainToken);
    return $this->repository->findValid($purpose->value, $tokenHash);
}
```

- [ ] **Step 5: Run tests**

```bash
php artisan test --filter=testFindValidReturns
```

Expected: 2 tests PASS

- [ ] **Step 6: Commit**

```bash
git add app/Enum/TokenPurposeEnum.php app/Services/ActionTokenService.php tests/Integration/Services/ActionTokenServiceTest.php
git commit -m "feat: add CHANNEL_RECEPTION_REACTIVATION enum case and ActionTokenService::findValid()"
```

---

## Task 2: Create `ChannelVideoReceptionPaused` event

Plain event class — no test needed (zero logic, just a data carrier).

**Files:**
- Create: `app/Events/Channel/ChannelVideoReceptionPaused.php`

- [ ] **Step 1: Create the event**

```php
<?php

declare(strict_types=1);

namespace App\Events\Channel;

use App\Models\Channel;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChannelVideoReceptionPaused implements ShouldQueue, ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Channel $channel,
    ) {
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add app/Events/Channel/ChannelVideoReceptionPaused.php
git commit -m "feat: add ChannelVideoReceptionPaused event"
```

---

## Task 3: Update `ChannelObserver` to fire the event on pause

**Files:**
- Modify: `app/Observers/ChannelObserver.php`
- Create: `tests/Integration/Observers/ChannelObserverTest.php`

- [ ] **Step 1: Write failing tests**

Create `tests/Integration/Observers/ChannelObserverTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Integration\Observers;

use App\Events\Channel\ChannelVideoReceptionPaused;
use App\Models\Channel;
use App\Observers\ChannelObserver;
use Illuminate\Support\Facades\Event;
use Tests\DatabaseTestCase;

final class ChannelObserverTest extends DatabaseTestCase
{
    public function testFiresEventWhenReceptionIsPaused(): void
    {
        Event::fake();

        $observer = new ChannelObserver();
        $channel = Channel::factory()->make();

        $channel->setRawAttributes(['is_video_reception_paused' => false], true);
        $channel->is_video_reception_paused = true;
        $channel->syncChanges();

        $observer->updated($channel);

        Event::assertDispatched(
            ChannelVideoReceptionPaused::class,
            static fn(ChannelVideoReceptionPaused $e) => $e->channel === $channel
        );
    }

    public function testDoesNotFireEventWhenReceptionIsUnpaused(): void
    {
        Event::fake();

        $observer = new ChannelObserver();
        $channel = Channel::factory()->make();

        $channel->setRawAttributes(['is_video_reception_paused' => true], true);
        $channel->is_video_reception_paused = false;
        $channel->syncChanges();

        $observer->updated($channel);

        Event::assertNotDispatched(ChannelVideoReceptionPaused::class);
    }

    public function testDoesNotFireEventWhenPauseFieldUnchanged(): void
    {
        Event::fake();

        $observer = new ChannelObserver();
        $channel = Channel::factory()->make();

        $channel->setRawAttributes(['is_video_reception_paused' => false], true);
        $channel->name = 'changed';
        $channel->syncChanges();

        $observer->updated($channel);

        Event::assertNotDispatched(ChannelVideoReceptionPaused::class);
    }

    public function testDoesNotFireEventWhenAlreadyPausedAndPausedAgain(): void
    {
        Event::fake();

        $observer = new ChannelObserver();
        $channel = Channel::factory()->make();

        $channel->setRawAttributes(['is_video_reception_paused' => true], true);
        $channel->is_video_reception_paused = true;
        $channel->syncChanges();

        $observer->updated($channel);

        Event::assertNotDispatched(ChannelVideoReceptionPaused::class);
    }
}
```

- [ ] **Step 2: Run to confirm failure**

```bash
php artisan test tests/Integration/Observers/ChannelObserverTest.php
```

Expected: FAIL — `updated` method not defined on `ChannelObserver`

- [ ] **Step 3: Add `updated()` to `ChannelObserver`**

Replace the content of `app/Observers/ChannelObserver.php`:

```php
<?php

declare(strict_types=1);

namespace App\Observers;

use App\Events\Channel\ChannelVideoReceptionPaused;
use App\Events\ChannelCreated;
use App\Models\Channel;
use Illuminate\Database\Eloquent\Model;

class ChannelObserver extends BaseObserver
{
    public function created(Channel|Model $model): void
    {
        parent::created($model);
        event(new ChannelCreated($model));
    }

    public function updated(Channel|Model $model): void
    {
        if (
            $model->wasChanged('is_video_reception_paused')
            && $model->is_video_reception_paused === true
            && $model->getOriginal('is_video_reception_paused') === false
        ) {
            event(new ChannelVideoReceptionPaused($model));
        }
    }
}
```

- [ ] **Step 4: Run tests**

```bash
php artisan test tests/Integration/Observers/ChannelObserverTest.php
```

Expected: 4 tests PASS

- [ ] **Step 5: Commit**

```bash
git add app/Observers/ChannelObserver.php tests/Integration/Observers/ChannelObserverTest.php
git commit -m "feat: fire ChannelVideoReceptionPaused event when channel reception is paused"
```

---

## Task 4: Add language keys for the notification email

No tests for lang files — covered implicitly by the mail tests later.

**Files:**
- Modify: `lang/de/mails.php`
- Modify: `lang/en/mails.php`

- [ ] **Step 1: Add keys to `lang/de/mails.php`**

Add after the `channel_welcome_email` block:

```php
'channel_reception_paused' => [
    'subject'        => 'Video-Empfang pausiert – :channel',
    'headline'       => 'Dein Video-Empfang wurde pausiert',
    'greeting'       => 'Hallo,',
    'body'           => 'Der wöchentliche Video-Empfang für den Kanal <strong>:channel</strong> wurde durch einen Administrator pausiert. Wenn du das nicht möchtest, kannst du den Empfang über den folgenden Button wieder aktivieren.',
    'reactivate_cta' => 'Empfang reaktivieren',
    'signature'      => 'Viele Grüße<br>Dein :app-Team',
],
```

- [ ] **Step 2: Add keys to `lang/en/mails.php`**

Add after the `channel_welcome_email` block:

```php
'channel_reception_paused' => [
    'subject'        => 'Video reception paused – :channel',
    'headline'       => 'Your video reception has been paused',
    'greeting'       => 'Hello,',
    'body'           => 'The weekly video reception for channel <strong>:channel</strong> has been paused by an administrator. If you did not request this, you can reactivate reception using the button below.',
    'reactivate_cta' => 'Reactivate reception',
    'signature'      => 'Best regards,<br>Your :app team',
],
```

- [ ] **Step 3: Commit**

```bash
git add lang/de/mails.php lang/en/mails.php
git commit -m "feat: add channel_reception_paused mail language keys (de + en)"
```

---

## Task 5: Create `ChannelVideoReceptionPausedMail` + test

**Files:**
- Create: `app/Mail/ChannelVideoReceptionPausedMail.php`
- Create: `tests/Integration/Mail/ChannelVideoReceptionPausedMailTest.php`

- [ ] **Step 1: Write failing test**

Create `tests/Integration/Mail/ChannelVideoReceptionPausedMailTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Integration\Mail;

use App\Enum\TokenPurposeEnum;
use App\Mail\ChannelVideoReceptionPausedMail;
use App\Models\Channel;
use Carbon\Carbon;
use Tests\DatabaseTestCase;

final class ChannelVideoReceptionPausedMailTest extends DatabaseTestCase
{
    public function testEnvelopeContainsChannelNameInSubject(): void
    {
        $channel = Channel::factory()->create(['name' => 'My Channel']);

        $mail = new ChannelVideoReceptionPausedMail(
            channel: $channel,
            plainToken: 'abc123',
            expireAt: Carbon::now()->addMonth(),
        );

        $this->assertStringContainsString('My Channel', $mail->envelope()->subject);
    }

    public function testViewDataContainsAllExpectedKeys(): void
    {
        $channel = Channel::factory()->create(['name' => 'My Channel']);
        $expireAt = Carbon::now()->addMonth();

        $mail = new ChannelVideoReceptionPausedMail(
            channel: $channel,
            plainToken: 'abc123',
            expireAt: $expireAt,
        );

        $data = $mail->content()->with;

        $this->assertArrayHasKey('channel', $data);
        $this->assertArrayHasKey('expireAt', $data);
        $this->assertArrayHasKey('reactivateUrl', $data);
        $this->assertStringContainsString(
            TokenPurposeEnum::CHANNEL_RECEPTION_REACTIVATION->value,
            $data['reactivateUrl']
        );
        $this->assertStringContainsString('abc123', $data['reactivateUrl']);
    }
}
```

- [ ] **Step 2: Run to confirm failure**

```bash
php artisan test tests/Integration/Mail/ChannelVideoReceptionPausedMailTest.php
```

Expected: FAIL — class not found

- [ ] **Step 3: Create the mail class**

Create `app/Mail/ChannelVideoReceptionPausedMail.php`:

```php
<?php

declare(strict_types=1);

namespace App\Mail;

use App\Enum\TokenPurposeEnum;
use App\Models\Channel;
use Carbon\CarbonInterface;
use Illuminate\Mail\Mailables\Envelope;

final class ChannelVideoReceptionPausedMail extends AbstractLoggedMail
{
    public function __construct(
        public readonly Channel $channel,
        public readonly string $plainToken,
        public readonly CarbonInterface $expireAt,
    ) {
        $this->subjectLine = __('mails.channel_reception_paused.subject', [
            'channel' => $channel->name,
        ]);
    }

    protected function viewName(): string
    {
        return 'emails.channel.video_reception_paused';
    }

    protected function viewData(): array
    {
        return [
            'channel'       => $this->channel,
            'expireAt'      => $this->expireAt,
            'reactivateUrl' => route('tokens.update', [
                'purpose' => TokenPurposeEnum::CHANNEL_RECEPTION_REACTIVATION->value,
                'token'   => $this->plainToken,
            ]),
        ];
    }
}
```

- [ ] **Step 4: Run tests**

```bash
php artisan test tests/Integration/Mail/ChannelVideoReceptionPausedMailTest.php
```

Expected: 2 tests PASS

- [ ] **Step 5: Commit**

```bash
git add app/Mail/ChannelVideoReceptionPausedMail.php tests/Integration/Mail/ChannelVideoReceptionPausedMailTest.php
git commit -m "feat: add ChannelVideoReceptionPausedMail class"
```

---

## Task 6: Create the email blade template

**Files:**
- Create: `resources/views/emails/channel/video_reception_paused.blade.php`

- [ ] **Step 1: Create the template**

Create `resources/views/emails/channel/video_reception_paused.blade.php`:

```blade
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <title>{{ $subject }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f8fafc; font-family:Arial, sans-serif;">
@include('emails.partials.header')

<table role="presentation" width="100%" cellpadding="0" cellspacing="0"
       style="max-width:600px; width:100%; margin:0 auto; background:#ffffff;
              border:1px solid #e2e8f0; border-radius:6px;">
    <tr>
        <td style="padding:24px; color:#0f172a; line-height:1.6; font-size:16px;">
            <h1 style="margin:0 0 16px 0; font-size:20px; font-weight:700;">
                {{ __('mails.channel_reception_paused.headline') }}
            </h1>

            <p>{{ __('mails.channel_reception_paused.greeting') }}</p>

            <p>
                {!! __('mails.channel_reception_paused.body', ['channel' => $channel->name]) !!}
            </p>

            <p style="text-align:center; margin:24px 0;">
                <a href="{{ $reactivateUrl }}"
                   style="display:inline-block; padding:12px 24px; background-color:#2563eb; color:#ffffff;
                          text-decoration:none; border-radius:6px; font-weight:bold;">
                    {{ __('mails.channel_reception_paused.reactivate_cta') }}
                </a>
            </p>

            <p style="margin-top:16px; font-size:14px; color:#64748b;">
                {{ __('mails.common.expires_at', [
                    'date' => $expireAt
                        ->timezone(config('app.timezone'))
                        ->locale(app()->getLocale())
                        ->translatedFormat('d. F Y H:i'),
                ]) }}
            </p>

            <p style="margin:24px 0 0 0;">
                {!! __('mails.channel_reception_paused.signature', ['app' => config('app.name')]) !!}
            </p>
        </td>
    </tr>
</table>

@include('emails.partials.footer')
</body>
</html>
```

- [ ] **Step 2: Commit**

```bash
git add resources/views/emails/channel/video_reception_paused.blade.php
git commit -m "feat: add video_reception_paused email template"
```

---

## Task 7: Create `SendChannelVideoReceptionPausedMail` listener + `MailService` method + test

**Files:**
- Create: `app/Listeners/SendChannelVideoReceptionPausedMail.php`
- Modify: `app/Services/MailService.php`
- Create: `tests/Integration/Listeners/SendChannelVideoReceptionPausedMailTest.php`

- [ ] **Step 1: Write failing test**

Create `tests/Integration/Listeners/SendChannelVideoReceptionPausedMailTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Integration\Listeners;

use App\Events\Channel\ChannelVideoReceptionPaused;
use App\Listeners\SendChannelVideoReceptionPausedMail;
use App\Mail\ChannelVideoReceptionPausedMail;
use App\Models\Channel;
use Illuminate\Support\Facades\Mail;
use Tests\DatabaseTestCase;

final class SendChannelVideoReceptionPausedMailTest extends DatabaseTestCase
{
    public function testListenerQueuesMailWhenEmailIsPresent(): void
    {
        Mail::fake();

        $channel = Channel::factory()->create(['email' => 'owner@example.com']);
        $event = new ChannelVideoReceptionPaused($channel);

        (new SendChannelVideoReceptionPausedMail())->handle($event);

        Mail::assertQueued(
            ChannelVideoReceptionPausedMail::class,
            static fn(ChannelVideoReceptionPausedMail $mail) =>
                $mail->hasTo('owner@example.com') &&
                $mail->channel->is($channel)
        );
    }

    public function testListenerDoesNothingWhenEmailIsMissing(): void
    {
        Mail::fake();

        $channel = Channel::factory()->make(['email' => null]);
        $event = new ChannelVideoReceptionPaused($channel);

        (new SendChannelVideoReceptionPausedMail())->handle($event);

        Mail::assertNothingSent();
    }
}
```

- [ ] **Step 2: Run to confirm failure**

```bash
php artisan test tests/Integration/Listeners/SendChannelVideoReceptionPausedMailTest.php
```

Expected: FAIL — listener class not found

- [ ] **Step 3: Add `sendChannelVideoReceptionPausedMail()` to `MailService`**

In `app/Services/MailService.php`, add at the end of the class (before the closing `}`):

```php
/**
 * Send channel video reception paused notification to the channel owner.
 * @throws \Random\RandomException
 */
public function sendChannelVideoReceptionPausedMail(Channel $channel): void
{
    $expireAt = Carbon::now()->addMonth();
    $tokenService = app(ActionTokenService::class);

    // Invalidate any existing unconsumed reactivation token so the old link stops working
    $tokenService->invalidateExistingFor(TokenPurposeEnum::CHANNEL_RECEPTION_REACTIVATION, $channel);

    $plainToken = $tokenService->issue(
        purpose: TokenPurposeEnum::CHANNEL_RECEPTION_REACTIVATION,
        subject: $channel,
        expiresAt: $expireAt,
    );

    $this->queueMail(
        $channel->email,
        new \App\Mail\ChannelVideoReceptionPausedMail(
            channel: $channel,
            plainToken: $plainToken,
            expireAt: $expireAt,
        )
    );
}
```

Also add the missing import at the top of `MailService.php`:
```php
use App\Mail\ChannelVideoReceptionPausedMail;
```

- [ ] **Step 4: Create the listener**

Create `app/Listeners/SendChannelVideoReceptionPausedMail.php`:

```php
<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\Channel\ChannelVideoReceptionPaused;
use App\Services\MailService;

class SendChannelVideoReceptionPausedMail
{
    public function handle(ChannelVideoReceptionPaused $event): void
    {
        $channel = $event->channel;

        if (!$channel->email) {
            return;
        }

        app(MailService::class)->sendChannelVideoReceptionPausedMail($channel);
    }
}
```

- [ ] **Step 5: Run tests**

```bash
php artisan test tests/Integration/Listeners/SendChannelVideoReceptionPausedMailTest.php
```

Expected: 2 tests PASS

- [ ] **Step 6: Commit**

```bash
git add app/Listeners/SendChannelVideoReceptionPausedMail.php app/Services/MailService.php
git add tests/Integration/Listeners/SendChannelVideoReceptionPausedMailTest.php
git commit -m "feat: add SendChannelVideoReceptionPausedMail listener and MailService method"
```

---

## Task 8: Create `HandleChannelReceptionReactivation` listener + test

This listener handles `ActionTokenConsumed` for purpose `CHANNEL_RECEPTION_REACTIVATION` and sets `is_video_reception_paused = false` on the channel.

**Files:**
- Create: `app/Listeners/Channel/HandleChannelReceptionReactivation.php`
- Create: `tests/Integration/Listeners/Channel/HandleChannelReceptionReactivationTest.php`

- [ ] **Step 1: Write failing test**

Create `tests/Integration/Listeners/Channel/HandleChannelReceptionReactivationTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Integration\Listeners\Channel;

use App\Enum\TokenPurposeEnum;
use App\Events\ActionToken\ActionTokenConsumed;
use App\Listeners\Channel\HandleChannelReceptionReactivation;
use App\Models\ActionToken;
use App\Models\Channel;
use Tests\DatabaseTestCase;

final class HandleChannelReceptionReactivationTest extends DatabaseTestCase
{
    public function testReactivatesChannelWhenTokenIsConsumed(): void
    {
        $channel = Channel::factory()->create(['is_video_reception_paused' => true]);

        $token = ActionToken::factory()->create([
            'purpose'      => TokenPurposeEnum::CHANNEL_RECEPTION_REACTIVATION->value,
            'subject_type' => $channel->getMorphClass(),
            'subject_id'   => $channel->getKey(),
        ]);

        $event = new ActionTokenConsumed($token);
        (new HandleChannelReceptionReactivation())->handle($event);

        $this->assertFalse($channel->fresh()->is_video_reception_paused);
    }

    public function testIgnoresOtherPurposes(): void
    {
        $channel = Channel::factory()->create(['is_video_reception_paused' => true]);

        $token = ActionToken::factory()->create([
            'purpose'      => TokenPurposeEnum::CHANNEL_ACTIVATION_APPROVAL->value,
            'subject_type' => $channel->getMorphClass(),
            'subject_id'   => $channel->getKey(),
        ]);

        $event = new ActionTokenConsumed($token);
        (new HandleChannelReceptionReactivation())->handle($event);

        $this->assertTrue($channel->fresh()->is_video_reception_paused);
    }

    public function testIgnoresTokenWithNonChannelSubject(): void
    {
        $channel = Channel::factory()->create(['is_video_reception_paused' => true]);

        // Token with no subject (subject_id null)
        $token = ActionToken::factory()->create([
            'purpose'      => TokenPurposeEnum::CHANNEL_RECEPTION_REACTIVATION->value,
            'subject_type' => null,
            'subject_id'   => null,
        ]);

        $event = new ActionTokenConsumed($token);
        (new HandleChannelReceptionReactivation())->handle($event);

        $this->assertTrue($channel->fresh()->is_video_reception_paused);
    }
}
```

- [ ] **Step 2: Run to confirm failure**

```bash
php artisan test tests/Integration/Listeners/Channel/HandleChannelReceptionReactivationTest.php
```

Expected: FAIL — listener class not found

- [ ] **Step 3: Create the listener**

Create `app/Listeners/Channel/HandleChannelReceptionReactivation.php`:

```php
<?php

declare(strict_types=1);

namespace App\Listeners\Channel;

use App\Enum\TokenPurposeEnum;
use App\Events\ActionToken\ActionTokenConsumed;
use App\Models\Channel;

final readonly class HandleChannelReceptionReactivation
{
    public function handle(ActionTokenConsumed $event): void
    {
        $token = $event->token;

        if ($token->purpose !== TokenPurposeEnum::CHANNEL_RECEPTION_REACTIVATION->value) {
            return;
        }

        if (!$token->subject instanceof Channel) {
            return;
        }

        $token->subject->update(['is_video_reception_paused' => false]);
    }
}
```

- [ ] **Step 4: Run tests**

```bash
php artisan test tests/Integration/Listeners/Channel/HandleChannelReceptionReactivationTest.php
```

Expected: 3 tests PASS

- [ ] **Step 5: Commit**

```bash
git add app/Listeners/Channel/HandleChannelReceptionReactivation.php
git add tests/Integration/Listeners/Channel/HandleChannelReceptionReactivationTest.php
git commit -m "feat: add HandleChannelReceptionReactivation listener"
```

---

## Task 9: Add language keys for token views

**Files:**
- Modify: `lang/de/action-tokens.php`
- Modify: `lang/en/action-tokens.php`

- [ ] **Step 1: Add keys to `lang/de/action-tokens.php`**

Add inside the return array:

```php
'channel_reception_reactivation' => [
    'confirm' => [
        'title'    => 'Empfang reaktivieren',
        'headline' => 'Video-Empfang reaktivieren',
        'body'     => 'Möchtest du den wöchentlichen Video-Empfang für den Kanal <strong>:channel</strong> wieder aktivieren?',
        'cta'      => 'Ja, Empfang reaktivieren',
    ],
    'success' => [
        'title'    => 'Empfang reaktiviert',
        'headline' => 'Video-Empfang erfolgreich reaktiviert',
        'body'     => 'Der wöchentliche Video-Empfang für den Kanal :channel wurde erfolgreich wieder aktiviert.',
        'back'     => 'Zur Startseite',
    ],
],
```

- [ ] **Step 2: Add keys to `lang/en/action-tokens.php`**

Add inside the return array:

```php
'channel_reception_reactivation' => [
    'confirm' => [
        'title'    => 'Reactivate reception',
        'headline' => 'Reactivate video reception',
        'body'     => 'Do you want to reactivate the weekly video reception for channel <strong>:channel</strong>?',
        'cta'      => 'Yes, reactivate reception',
    ],
    'success' => [
        'title'    => 'Reception reactivated',
        'headline' => 'Video reception successfully reactivated',
        'body'     => 'The weekly video reception for channel :channel has been successfully reactivated.',
        'back'     => 'Back to home',
    ],
],
```

- [ ] **Step 3: Commit**

```bash
git add lang/de/action-tokens.php lang/en/action-tokens.php
git commit -m "feat: add channel_reception_reactivation language keys (de + en)"
```

---

## Task 10: Create token views (confirm + success)

**Files:**
- Create: `resources/views/tokens/channel-reception-confirm.blade.php`
- Create: `resources/views/tokens/channel-reception-reactivated.blade.php`

- [ ] **Step 1: Create confirmation view**

Create `resources/views/tokens/channel-reception-confirm.blade.php`:

```blade
@php
    /** @var \App\Models\ActionToken $token */
    $channel = $token->subject ?? null;
@endphp

@extends('layouts.app')

@section('title', __('action-tokens.channel_reception_reactivation.confirm.title'))
@section('subtitle', $channel?->name ?? '')

@section('content')
    <div class="panel" style="max-width: 600px; margin: 0 auto; text-align: center; padding: 32px;">
        <h1 style="font-size: 22px; margin-bottom: 16px; color: var(--color-primary, #2563eb);">
            {{ __('action-tokens.channel_reception_reactivation.confirm.headline') }}
        </h1>

        <p style="line-height: 1.6; margin-bottom: 24px;">
            {!! __('action-tokens.channel_reception_reactivation.confirm.body', [
                'channel' => $channel?->name ?? '',
            ]) !!}
        </p>

        <form method="POST"
              action="{{ route('tokens.store', ['purpose' => $purpose->value, 'token' => $plainToken]) }}">
            @csrf
            <button type="submit" class="btn"
                    style="padding:12px 24px; background-color:#2563eb; color:#ffffff;
                           border:none; border-radius:6px; font-weight:bold; cursor:pointer;
                           font-size:16px; text-decoration:none;">
                {{ __('action-tokens.channel_reception_reactivation.confirm.cta') }}
            </button>
        </form>

        <hr class="muted-separator" style="margin: 32px 0;">

        <p class="muted" style="font-size: 13px; color: #64748b;">
            &copy; {{ date('Y') }} {{ config('app.name') }}
        </p>
    </div>
@endsection
```

- [ ] **Step 2: Create success view**

Create `resources/views/tokens/channel-reception-reactivated.blade.php`:

```blade
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
```

- [ ] **Step 3: Commit**

```bash
git add resources/views/tokens/channel-reception-confirm.blade.php
git add resources/views/tokens/channel-reception-reactivated.blade.php
git commit -m "feat: add channel reception confirm and success token views"
```

---

## Task 11: Extend `TokenApprovalController` + add POST route + tests

This is the core of the two-step flow. GET validates (no consume) and shows the confirm view; POST consumes and shows success. Existing GET behavior for other purposes is unchanged.

**Files:**
- Modify: `app/Http/Controllers/TokenApprovalController.php`
- Modify: `routes/web.php`
- Modify: `tests/Feature/Http/Controllers/TokenApprovalControllerTest.php`

- [ ] **Step 1: Write failing tests**

Add to `tests/Feature/Http/Controllers/TokenApprovalControllerTest.php`:

```php
public function testChannelReceptionConfirmPageShowsWithoutConsumingToken(): void
{
    $service = $this->app->make(\App\Services\ActionTokenService::class);
    $channel = \App\Models\Channel::factory()->create(['is_video_reception_paused' => true]);

    $plainToken = $service->issue(
        \App\Enum\TokenPurposeEnum::CHANNEL_RECEPTION_REACTIVATION,
        subject: $channel,
        expiresAt: \Carbon\Carbon::now()->addMonth(),
    );

    $urlSegment = \App\Enum\TokenPurposeEnum::CHANNEL_RECEPTION_REACTIVATION->value;

    // GET: should show confirm view WITHOUT consuming the token
    $this->get("/action-tokens/approve/{$urlSegment}/{$plainToken}")
        ->assertStatus(200)
        ->assertViewIs('tokens.channel-reception-confirm')
        ->assertViewHas('plainToken', $plainToken);

    // Token must still be valid (not consumed)
    $this->assertNotNull(
        $service->findValid(\App\Enum\TokenPurposeEnum::CHANNEL_RECEPTION_REACTIVATION, $plainToken)
    );
}

public function testChannelReceptionPostConsumesTokenAndReactivatesChannel(): void
{
    $service = $this->app->make(\App\Services\ActionTokenService::class);
    $channel = \App\Models\Channel::factory()->create(['is_video_reception_paused' => true]);

    $plainToken = $service->issue(
        \App\Enum\TokenPurposeEnum::CHANNEL_RECEPTION_REACTIVATION,
        subject: $channel,
        expiresAt: \Carbon\Carbon::now()->addMonth(),
    );

    $urlSegment = \App\Enum\TokenPurposeEnum::CHANNEL_RECEPTION_REACTIVATION->value;

    $this->post("/action-tokens/approve/{$urlSegment}/{$plainToken}")
        ->assertStatus(200)
        ->assertViewIs('tokens.channel-reception-reactivated');

    $this->assertFalse($channel->fresh()->is_video_reception_paused);

    // second POST → 410 (token consumed)
    $this->post("/action-tokens/approve/{$urlSegment}/{$plainToken}")
        ->assertStatus(410);
}

public function testChannelReceptionGetReturns410WhenTokenConsumed(): void
{
    $service = $this->app->make(\App\Services\ActionTokenService::class);
    $channel = \App\Models\Channel::factory()->create(['is_video_reception_paused' => true]);

    $plainToken = $service->issue(
        \App\Enum\TokenPurposeEnum::CHANNEL_RECEPTION_REACTIVATION,
        subject: $channel,
        expiresAt: \Carbon\Carbon::now()->addMonth(),
    );

    $urlSegment = \App\Enum\TokenPurposeEnum::CHANNEL_RECEPTION_REACTIVATION->value;

    $service->consume(\App\Enum\TokenPurposeEnum::CHANNEL_RECEPTION_REACTIVATION, $plainToken);

    $this->get("/action-tokens/approve/{$urlSegment}/{$plainToken}")
        ->assertStatus(410);
}

public function testPostForNonReactivationPurposeReturns404(): void
{
    $this->post(
        '/action-tokens/approve/' .
        \App\Enum\TokenPurposeEnum::CHANNEL_ACCESS_APPROVAL->value .
        '/some-token'
    )->assertStatus(404);
}
```

- [ ] **Step 2: Run to confirm failure**

```bash
php artisan test --filter=testChannelReception
```

Expected: FAIL — no POST route + GET still consumes the token

- [ ] **Step 3: Add POST route to `routes/web.php`**

After the existing `tokens.update` route, add:

```php
Route::post('/action-tokens/approve/{purpose}/{token}', [TokenApprovalController::class, 'store'])
    ->name('tokens.store');
```

- [ ] **Step 4: Extend `TokenApprovalController`**

Replace the full content of `app/Http/Controllers/TokenApprovalController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enum\TokenPurposeEnum;
use App\Services\ActionTokenService;
use Symfony\Component\HttpFoundation\Response;

final class TokenApprovalController extends Controller
{
    public function __construct(
        private readonly ActionTokenService $actionTokenService,
    ) {
    }

    public function update(string $purpose, string $token)
    {
        $purposeEnum = TokenPurposeEnum::tryFrom($purpose);
        if (!$purposeEnum) {
            abort(Response::HTTP_NOT_FOUND);
        }

        // Two-step purposes: validate without consuming, show confirmation page
        if ($purposeEnum === TokenPurposeEnum::CHANNEL_RECEPTION_REACTIVATION) {
            $actionToken = $this->actionTokenService->findValid($purposeEnum, $token);
            if (!$actionToken) {
                abort(Response::HTTP_GONE);
            }
            return view('tokens.channel-reception-confirm', [
                'token'      => $actionToken,
                'purpose'    => $purposeEnum,
                'plainToken' => $token,
            ]);
        }

        $actionToken = $this->actionTokenService->consume($purposeEnum, $token);
        if (!$actionToken) {
            abort(Response::HTTP_GONE);
        }

        $view = $this->resolveViewForPurpose($purposeEnum);
        if ($view && view()->exists($view)) {
            return view($view, ['token' => $actionToken, 'purpose' => $purposeEnum]);
        }

        return response()->noContent();
    }

    public function store(string $purpose, string $token)
    {
        $purposeEnum = TokenPurposeEnum::tryFrom($purpose);
        if (!$purposeEnum || $purposeEnum !== TokenPurposeEnum::CHANNEL_RECEPTION_REACTIVATION) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $actionToken = $this->actionTokenService->consume($purposeEnum, $token);
        if (!$actionToken) {
            abort(Response::HTTP_GONE);
        }

        return view('tokens.channel-reception-reactivated', [
            'token'   => $actionToken,
            'purpose' => $purposeEnum,
        ]);
    }

    private function resolveViewForPurpose(TokenPurposeEnum $purpose): ?string
    {
        return match ($purpose) {
            TokenPurposeEnum::CHANNEL_ACCESS_APPROVAL =>
                'tokens.channel-access-approved',

            TokenPurposeEnum::CHANNEL_ACTIVATION_APPROVAL =>
                'tokens.channel-activation-approved',

            default => null,
        };
    }
}
```

- [ ] **Step 5: Run all controller tests**

```bash
php artisan test tests/Feature/Http/Controllers/TokenApprovalControllerTest.php
```

Expected: all tests PASS (including the existing ones for CHANNEL_ACCESS_APPROVAL and CHANNEL_ACTIVATION_APPROVAL)

- [ ] **Step 6: Run the full test suite**

```bash
php artisan test
```

Expected: all tests PASS

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/TokenApprovalController.php routes/web.php
git add tests/Feature/Http/Controllers/TokenApprovalControllerTest.php
git commit -m "feat: extend TokenApprovalController with two-step reactivation flow"
```

---

## Final check

- [ ] Run the full test suite one last time

```bash
php artisan test
```

Expected: all tests PASS with no regressions.
