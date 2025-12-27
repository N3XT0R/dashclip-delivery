<?php

declare(strict_types=1);

namespace Tests\Integration\Services;

use App\Enum\TokenPurposeEnum;
use App\Mail\ChannelAccessApprovalRequestedMail;
use App\Mail\ChannelWelcomeMail;
use App\Mail\NewOfferMail;
use App\Mail\NoReplyFAQMail;
use App\Mail\ReminderMail;
use App\Mail\UserWelcomeMail;
use App\Models\ActionToken;
use App\Models\Assignment;
use App\Models\Batch;
use App\Models\Channel;
use App\Models\ChannelApplication;
use App\Models\User;
use App\Services\ActionTokenService;
use App\Services\LinkService;
use App\Services\MailService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Mockery\MockInterface;
use Tests\DatabaseTestCase;

class MailServiceTest extends DatabaseTestCase
{
    public function test_it_sends_channel_access_approval_requested_mail(): void
    {
        Mail::fake();
        Carbon::setTestNow('2024-02-10 08:00:00');

        $application = ChannelApplication::factory()
            ->forExistingChannel()
            ->create();

        $ownerEmail = 'owner@example.com';
        $expireAt = Carbon::now()->addMonth();

        $service = new MailService();
        $service->sendChannelAccessApprovalRequestedMail($ownerEmail, $application);

        $actionToken = ActionToken::latest('id')->first();

        Mail::assertQueued(ChannelAccessApprovalRequestedMail::class, function (ChannelAccessApprovalRequestedMail $mail) use ($application, $expireAt, $ownerEmail) {
            return $mail->hasTo($ownerEmail)
                && $mail->channelApplication->is($application)
                && strlen($mail->plainToken) === 64
                && $mail->expireAt->equalTo($expireAt);
        });

        $this->assertNotNull($actionToken);
        $this->assertSame(TokenPurposeEnum::CHANNEL_ACCESS_APPROVAL->value, $actionToken->purpose);
        $this->assertTrue($expireAt->equalTo($actionToken->expires_at));
        $this->assertTrue($application->is($actionToken->subject));
        $this->assertSame([
            'user_id' => $application->user->getKey(),
            'channel_id' => $application->channel->getKey(),
            'owner' => $ownerEmail,
        ], $actionToken->meta);

        Carbon::setTestNow();
    }

    public function test_it_sends_channel_welcome_mail_to_channel_email(): void
    {
        Mail::fake();

        $channel = Channel::factory()->create();

        (new MailService())->sendChannelWelcomeMail($channel);

        Mail::assertQueued(ChannelWelcomeMail::class, function (ChannelWelcomeMail $mail) use ($channel) {
            return $mail->hasTo($channel->email)
                && $mail->channel->is($channel);
        });
    }

    public function test_it_sends_new_offer_mail_for_regular_channel(): void
    {
        Mail::fake();
        $expireAt = Carbon::parse('2024-03-15 12:00:00');

        $batch = Batch::factory()->type('assign')->create();
        $channel = Channel::factory()->create();

        $this->mock(LinkService::class, function (MockInterface $mock) use ($batch, $channel, $expireAt) {
            $mock->shouldReceive('getOfferUrl')
                ->once()
                ->with($batch, $channel, $expireAt)
                ->andReturn('https://example.test/offers/signed');
            $mock->shouldReceive('getUnusedUrl')
                ->once()
                ->with($batch, $channel, $expireAt)
                ->andReturn('https://example.test/offers/unused');
        });

        (new MailService())->sendNewOfferMail($channel, $batch, $expireAt, false);

        Mail::assertQueued(NewOfferMail::class, function (NewOfferMail $mail) use ($batch, $channel, $expireAt) {
            return $mail->hasTo($channel->email)
                && $mail->batch->is($batch)
                && $mail->channel->is($channel)
                && $mail->offerUrl === 'https://example.test/offers/signed'
                && $mail->unusedUrl === 'https://example.test/offers/unused'
                && $mail->expiresAt->equalTo($expireAt)
                && $mail->isChannelOperator === false;
        });
    }

    public function test_it_sends_new_offer_mail_for_channel_operator(): void
    {
        Mail::fake();
        $expireAt = Carbon::parse('2024-04-01 09:00:00');

        $batch = Batch::factory()->type('assign')->create();
        $channel = Channel::factory()->create();

        $this->mock(LinkService::class, function (MockInterface $mock) use ($batch, $channel, $expireAt) {
            $mock->shouldReceive('getOfferUrl')->never();
            $mock->shouldReceive('getUnusedUrl')
                ->once()
                ->with($batch, $channel, $expireAt)
                ->andReturn('https://example.test/offers/unused');
        });

        $loginRoute = route('filament.standard.auth.login');

        (new MailService())->sendNewOfferMail($channel, $batch, $expireAt, true);

        Mail::assertQueued(NewOfferMail::class, function (NewOfferMail $mail) use ($batch, $channel, $expireAt, $loginRoute) {
            return $mail->hasTo($channel->email)
                && $mail->batch->is($batch)
                && $mail->channel->is($channel)
                && $mail->offerUrl === $loginRoute
                && $mail->unusedUrl === 'https://example.test/offers/unused'
                && $mail->expiresAt->equalTo($expireAt)
                && $mail->isChannelOperator === true;
        });
    }

    public function test_it_sends_user_welcome_mail_with_optional_password(): void
    {
        Mail::fake();

        $user = User::factory()->create(['email' => 'newuser@example.com']);
        $plainPassword = 'secret-pass';

        (new MailService())->sendUserWelcomeEmail($user, true, $plainPassword);

        Mail::assertQueued(UserWelcomeMail::class, function (UserWelcomeMail $mail) use ($user, $plainPassword) {
            return $mail->hasTo($user->email)
                && $mail->user->is($user)
                && $mail->fromBackend === true
                && $mail->plainPassword === $plainPassword;
        });
    }

    public function test_it_sends_reminder_mail_using_first_assignment_details(): void
    {
        Mail::fake();

        $channel = Channel::factory()->create();
        $batch = Batch::factory()->type('assign')->create();
        $assignments = Assignment::factory()
            ->count(2)
            ->forChannel($channel)
            ->withBatch($batch)
            ->create();

        $firstAssignment = $assignments->first();
        $expireAt = $firstAssignment->expires_at;

        $this->mock(LinkService::class, function (MockInterface $mock) use ($batch, $channel, $expireAt) {
            $mock->shouldReceive('getOfferUrl')
                ->once()
                ->withArgs(function (Batch $batchArg, Channel $channelArg, Carbon $expiresAtArg) use ($batch, $channel, $expireAt) {
                    return $batchArg->is($batch)
                        && $channelArg->is($channel)
                        && $expiresAtArg->equalTo($expireAt);
                })
                ->andReturn('https://example.test/offers/reminder');
        });

        (new MailService())->sendReminderMail($channel, new Collection($assignments));

        Mail::assertQueued(ReminderMail::class, function (ReminderMail $mail) use ($channel, $expireAt, $assignments) {
            return $mail->hasTo($channel->email)
                && $mail->channel->is($channel)
                && $mail->offerUrl === 'https://example.test/offers/reminder'
                && $mail->expiresAt->equalTo($expireAt)
                && $mail->assignments->count() === $assignments->count();
        });
    }

    public function test_it_sends_faq_mail_to_given_address(): void
    {
        Mail::fake();

        (new MailService())->sendFaqMail('faq@example.com');

        Mail::assertQueued(NoReplyFAQMail::class, function (NoReplyFAQMail $mail) {
            return $mail->hasTo('faq@example.com');
        });
    }
}
