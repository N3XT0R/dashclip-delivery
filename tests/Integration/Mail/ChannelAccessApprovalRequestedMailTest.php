<?php

declare(strict_types=1);

namespace Tests\Integration\Mail;

use App\Enum\TokenPurposeEnum;
use App\Mail\ChannelAccessApprovalRequestedMail;
use App\Models\Channel;
use App\Models\ChannelApplication;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ChannelAccessApprovalRequestedMailTest extends TestCase
{
    public function testMailIncludesApplicationDataAndApproveUrl(): void
    {
        Mail::fake();

        $application = ChannelApplication::factory()
            ->make([
                'id' => 1,
                'note' => 'Please approve the access request',
                'user_id' => 11,
                'channel_id' => 21,
            ]);

        $application->setRelation('user', User::factory()->make(['id' => $application->user_id]));
        $application->setRelation('channel', Channel::factory()->make(['id' => $application->channel_id]));

        $expireAt = Carbon::parse('2024-11-01 12:00:00');
        $plainToken = 'approval-token';

        Mail::to('approver@example.com')->send(
            new ChannelAccessApprovalRequestedMail($application, $plainToken, $expireAt)
        );

        Mail::assertQueued(ChannelAccessApprovalRequestedMail::class, function (
            ChannelAccessApprovalRequestedMail $mail
        ) use ($application, $expireAt, $plainToken) {
            $content = $mail->content();

            $this->assertSame('emails.channel.access_approval_requested', $content->view);
            $this->assertSame(__('mails.channel_access_request.subject'), $content->with['subject']);
            $this->assertSame($application, $content->with['application']);
            $this->assertSame($application->channel, $content->with['channel']);
            $this->assertSame($application->user, $content->with['user']);
            $this->assertTrue($expireAt->equalTo($content->with['expireAt']));
            $this->assertSame($application->note, $content->with['note']);
            $this->assertSame(
                route('tokens.update', [
                    'purpose' => TokenPurposeEnum::CHANNEL_ACCESS_APPROVAL->value,
                    'token' => $plainToken,
                ]),
                $content->with['approveUrl']
            );

            return true;
        });
    }

    public function testMailUsesEmptyNoteWhenNotProvided(): void
    {
        Mail::fake();

        $application = ChannelApplication::factory()
            ->make([
                'id' => 2,
                'note' => null,
                'user_id' => 12,
                'channel_id' => 22,
            ]);

        $application->setRelation('user', User::factory()->make(['id' => $application->user_id]));
        $application->setRelation('channel', Channel::factory()->make(['id' => $application->channel_id]));

        $expireAt = Carbon::parse('2024-12-15 08:30:00');
        $plainToken = 'missing-note-token';

        Mail::to('approver@example.com')->send(
            new ChannelAccessApprovalRequestedMail($application, $plainToken, $expireAt)
        );

        Mail::assertQueued(ChannelAccessApprovalRequestedMail::class, function (
            ChannelAccessApprovalRequestedMail $mail
        ) use ($application) {
            $content = $mail->content();

            $this->assertSame('', $content->with['note']);
            $this->assertSame($application->channel, $content->with['channel']);
            $this->assertSame($application->user, $content->with['user']);

            return true;
        });
    }
}
