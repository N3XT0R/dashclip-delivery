<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Resources\ChannelApplicationResource\Pages;

use App\Enum\Channel\ApplicationEnum;
use App\Filament\Resources\ChannelApplicationResource\Pages\EditChannelApplication;
use App\Models\ChannelApplication;
use App\Models\User;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Livewire\Livewire;
use Tests\DatabaseTestCase;

final class EditChannelApplicationTest extends DatabaseTestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->actingAs($this->admin);
    }

    public function testEditFormLoadsRecordAndUpdatesMeta(): void
    {
        $application = ChannelApplication::factory()
            ->withMeta([
                'new_channel' => [
                    'name' => 'Original Name',
                    'creator_name' => 'Creator',
                    'email' => 'original@example.com',
                    'youtube_name' => 'channel-handle',
                ],
                'reject_reason' => 'Old reason',
            ])
            ->create([
                'note' => 'Original note',
            ]);

        Livewire::test(EditChannelApplication::class, ['record' => $application->getKey()])
            ->assertStatus(200)
            ->assertFormSet([
                'user_id' => $application->user_id,
                'status' => ApplicationEnum::PENDING->value,
                'meta' => [
                    'new_channel' => [
                        'name' => 'Original Name',
                        'creator_name' => 'Creator',
                        'email' => 'original@example.com',
                        'youtube_name' => 'channel-handle',
                    ],
                    'reject_reason' => 'Old reason',
                    'tos_accepted' => false,
                    'tos_accepted_at' => null,
                ],
                'note' => 'Original note',
            ])
            ->fillForm([
                'meta.reject_reason' => 'Updated reason',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $application->refresh();

        $meta = $application->meta->toArray();
        $this->assertSame('Original Name', $meta['new_channel']['name']);
        $this->assertSame('Creator', $meta['new_channel']['creator_name']);
        $this->assertSame('original@example.com', $meta['new_channel']['email']);
        $this->assertSame('channel-handle', $meta['new_channel']['youtube_name']);
        $this->assertSame('Updated reason', $meta['reject_reason']);
        $this->assertSame('Original note', $application->note);
    }

    public function testAfterSaveApprovesApplicationWhenStatusIsApproved(): void
    {
        $application = ChannelApplication::factory()->create([
            'status' => ApplicationEnum::PENDING->value,
        ]);

        $approve = new class($application, $this->admin) {
            public bool $called = false;

            public function __construct(
                private ChannelApplication $expectedRecord,
                private User $expectedUser,
            ) {
            }

            public function handle(ChannelApplication $record, User $user): void
            {
                $this->called = $record->is($this->expectedRecord) && $user->is($this->expectedUser);
            }
        };

        $this->app->instance(\App\Application\Channel\Application\ApproveChannelApplication::class, $approve);

        Livewire::test(EditChannelApplication::class, ['record' => $application->getKey()])
            ->fillForm([
                'status' => ApplicationEnum::APPROVED->value,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue($approve->called);
    }

    public function testAfterSaveSilentlyHandlesApprovalErrors(): void
    {
        $application = ChannelApplication::factory()->create([
            'status' => ApplicationEnum::PENDING->value,
        ]);

        NotificationFacade::fake();

        $approve = new class() {
            public bool $called = false;

            public function handle(ChannelApplication $record, ?User $user = null): void
            {
                $this->called = true;
                throw new \RuntimeException('Something went wrong');
            }
        };

        $this->app->instance(\App\Application\Channel\Application\ApproveChannelApplication::class, $approve);

        Livewire::test(EditChannelApplication::class, ['record' => $application->getKey()])
            ->fillForm([
                'status' => ApplicationEnum::APPROVED->value,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue(true, 'Approval exception was handled without breaking the page.');
        $this->assertTrue($approve->called);
    }

    public function testAfterSaveDoesNothingWhenNotApproved(): void
    {
        $application = ChannelApplication::factory()->create([
            'status' => ApplicationEnum::PENDING->value,
        ]);

        $approve = new class() {
            public bool $called = false;

            public function handle(ChannelApplication $record, ?User $user = null): void
            {
                $this->called = true;
            }
        };

        $this->app->instance(\App\Application\Channel\Application\ApproveChannelApplication::class, $approve);

        Livewire::test(EditChannelApplication::class, ['record' => $application->getKey()])
            ->fillForm([
                'status' => ApplicationEnum::REJECTED->value,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertFalse($approve->called);
    }
}
