<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Enum\StatusEnum;
use App\Facades\Cfg;
use App\Mail\ReminderMail;
use App\Models\{Assignment, Batch, Channel, Clip, Video};
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Tests\DatabaseTestCase;

final class NotifyRemindersTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cfg::set('email_reminder', true, 'email', 'bool');
    }

    public function testQueuesReminderEmails(): void
    {
        Mail::fake();


        $batch = Batch::factory()->state(['type' => 'assign'])->create([
            'started_at' => now()->subDay(),
            'finished_at' => now()->subDay(),
        ]);

        $channel = Channel::factory()->create(['email' => 'test@example.test']);
        $video = Video::factory()->create(['original_name' => 'v1.mp4']);
        Clip::factory()->for($video)->create(['note' => 'nice clip']);

        Assignment::factory()
            ->for($batch, 'batch')
            ->for($channel, 'channel')
            ->for($video, 'video')
            ->create([
                'status' => StatusEnum::NOTIFIED->value,
                'expires_at' => now()->addDay()->setTime(12, 0),
            ]);

        $this->artisan('notify:reminders')
            ->assertExitCode(Command::SUCCESS);

        Mail::assertQueued(ReminderMail::class, function (ReminderMail $m) use ($channel, $video) {
            return $m->hasTo($channel->email)
                && $m->assignments->count() === 1
                && $m->assignments->first()->video->is($video)
                && $m->assignments->first()->video->clips->first()->note === 'nice clip';
        });
        $this->assertNotNull(Assignment::first()->notification_id);
    }

    public function testQueuesReminderEmailsWithCustomDays(): void
    {
        Mail::fake();

        $batch = Batch::factory()->state(['type' => 'assign'])->create([
            'started_at' => now()->subDay(),
            'finished_at' => now()->subDay(),
        ]);

        $channel = Channel::factory()->create(['email' => 'test@example.test']);
        $video = Video::factory()->create(['original_name' => 'v1.mp4']);
        Clip::factory()->for($video)->create(['note' => 'note']);

        Assignment::factory()
            ->for($batch, 'batch')
            ->for($channel, 'channel')
            ->for($video, 'video')
            ->create([
                'status' => StatusEnum::NOTIFIED->value,
                'expires_at' => now()->addDays(2)->setTime(12, 0),
            ]);

        $this->artisan('notify:reminders --days=2')
            ->assertExitCode(Command::SUCCESS);

        Mail::assertQueued(ReminderMail::class, fn(ReminderMail $m) => $m->hasTo($channel->email));
        $this->assertNotNull(Assignment::first()->notification_id);
    }

    public function testSkipsChannelsWithoutNotifiedAssignments(): void
    {
        Mail::fake();

        $batch = Batch::factory()->state(['type' => 'assign'])->create([
            'started_at' => now()->subDay(),
            'finished_at' => now()->subDay(),
        ]);

        $channel = Channel::factory()->create(['email' => 'test@example.test']);
        $video = Video::factory()->create();

        Assignment::factory()
            ->for($batch, 'batch')
            ->for($channel, 'channel')
            ->for($video, 'video')
            ->create([
                'status' => StatusEnum::PICKEDUP->value,
                'expires_at' => now()->addDay()->setTime(12, 0),
            ]);

        $this->artisan('notify:reminders')
            ->assertExitCode(Command::SUCCESS);

        Mail::assertNothingQueued();
    }
}
