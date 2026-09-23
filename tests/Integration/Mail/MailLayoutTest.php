<?php

declare(strict_types=1);

namespace Tests\Integration\Mail;

use App\Mail\UserWelcomeMail;
use App\Models\Batch;
use App\Models\Channel;
use App\Models\ChannelApplication;
use App\Models\User;
use Illuminate\Mail\Markdown;
use Illuminate\Notifications\Messages\MailMessage;
use Tests\DatabaseTestCase;

final class MailLayoutTest extends DatabaseTestCase
{
    public function testAllTransactionalViewsRenderOneDocumentAndPreserveActionLinks(): void
    {
        $user = User::factory()->make(['name' => '<script>unsafe</script>']);
        $channel = Channel::factory()->make();
        $application = new ChannelApplication();
        $application->setRelation('user', $user);
        $data = [
            'subject' => 'Mail layout preview', 'user' => $user, 'channel' => $channel,
            'application' => $application, 'batch' => Batch::factory()->make(),
            'fromBackend' => false, 'plainPassword' => null, 'filename' => 'clip.mp4',
            'date' => now(), 'lastLoginAt' => now(), 'expireAt' => now()->addDay(),
            'expiresAt' => now()->addDay(), 'note' => '', 'isChannelOperator' => false,
            'assignments' => collect(), 'approveUrl' => 'https://example.test/approve?token=abc&signature=def',
            'offerUrl' => 'https://example.test/offer', 'unusedUrl' => 'https://example.test/unused',
            'loginUrl' => 'https://example.test/login', 'reactivateUrl' => 'https://example.test/reactivate',
        ];

        foreach ([
            'channel-welcome', 'new-offer', 'no_reply_faq', 'upload-duplicated',
            'user-inactivity-reminder', 'user-upload-proceed', 'user-welcome',
            'channel.access_approval_requested', 'channel.access_approved', 'channel.video_reception_paused',
        ] as $view) {
            $html = view('emails.'.$view, $data)->render();
            $this->assertSame(1, substr_count($html, '<!DOCTYPE html>'), $view);
            $this->assertSame(1, substr_count($html, '<html '), $view);
            $this->assertStringContainsString('#0c1924', $html, $view);
            $this->assertStringContainsString(route('datenschutz'), $html, $view);
            $this->assertStringNotContainsString('<script>unsafe</script>', $html, $view);
        }

        $html = view('emails.channel-welcome', $data)->render();
        $this->assertStringContainsString('https://example.test/approve?token=abc&amp;signature=def', $html);
    }

    public function testWelcomeMailableEmbedsLogoAndEscapesRecipientName(): void
    {
        $mail = new UserWelcomeMail(User::factory()->make(['name' => '<Preview>']));
        $html = $mail->render();
        $this->assertStringContainsString('&lt;Preview&gt;', $html);
        $this->assertStringContainsString('data:image/png;base64,', $html);
        $this->assertStringContainsString('Jetzt anmelden', $html);
    }

    public function testMarkdownNotificationsShareBrandingAndRetainTextFallback(): void
    {
        $notification = (new MailMessage())->greeting('Hello Preview')
            ->line('Your account is ready.')
            ->action('Open account', 'https://example.test/account?token=abc&signature=def');
        $markdown = app(Markdown::class);
        $html = (string) $markdown->render('notifications::email', $notification->data());
        $text = (string) $markdown->renderText('notifications::email', $notification->data());

        $this->assertSame(1, substr_count($html, '<html '));
        $this->assertStringContainsString('#f97316', $html);
        $this->assertStringContainsString('Hello Preview', $html);
        $this->assertStringContainsString('Open account', $text);
        $this->assertStringContainsString('https://example.test/account?token=abc&signature=def', $text);
    }
}
