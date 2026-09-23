<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use App\Enum\Guard\GuardEnum;
use App\Enum\PanelEnum;
use App\Enum\StatusEnum;
use App\Enum\Users\RoleEnum;
use App\Models\Assignment;
use App\Models\Channel;
use App\Models\User;
use App\Services\AssignmentService;
use App\Services\Auth\ImpersonationService;
use Filament\Facades\Filament;
use Tests\DatabaseTestCase;

final class ImpersonationTest extends DatabaseTestCase
{
    public function testAnAdminCanStartAndStopViewingTheApplicationAsSomeoneElse(): void
    {
        $admin = User::factory()->admin()->create();
        $operator = User::factory()->standard()->create();

        $this->actingAs($admin, GuardEnum::DEFAULT->value)
            ->post(route('impersonation.start', $operator))
            ->assertRedirect(Filament::getPanel(PanelEnum::STANDARD->value)->getUrl());

        self::assertTrue(auth(GuardEnum::STANDARD->value)->user()->is($operator));

        $this->post(route('impersonation.stop'))
            ->assertRedirect(Filament::getPanel(PanelEnum::ADMIN->value)->getUrl());

        self::assertNull(auth(GuardEnum::STANDARD->value)->user());
    }

    public function testSomeoneWhoIsNotSignedInToTheAdministrationIsSentToTheSignIn(): void
    {
        $operator = User::factory()->standard()->create();
        $target = User::factory()->standard()->create();

        $this->actingAs($operator, GuardEnum::STANDARD->value)
            ->post(route('impersonation.start', $target))
            ->assertRedirect();

        self::assertFalse(app(ImpersonationService::class)->isActive());
    }

    public function testAnAccountWithoutAdministratorRightsIsRefused(): void
    {
        $user = User::factory()->withRole(RoleEnum::REGULAR, GuardEnum::DEFAULT->value)->create();
        $target = User::factory()->standard()->create();

        $this->actingAs($user, GuardEnum::DEFAULT->value)
            ->post(route('impersonation.start', $target))
            ->assertForbidden();

        self::assertFalse(app(ImpersonationService::class)->isActive());
    }

    public function testTheUserAreaShowsWhoIsBeingViewed(): void
    {
        $admin = User::factory()->admin()->create();
        $operator = User::factory()->standard()->create(['name' => 'Road Rave Operator']);
        $this->actingAs($admin, GuardEnum::DEFAULT->value)->post(route('impersonation.start', $operator));

        $this->get(Filament::getPanel(PanelEnum::STANDARD->value)->getUrl())
            ->assertOk()
            ->assertSee(__('impersonation.banner', ['name' => 'Road Rave Operator']))
            ->assertSee(__('impersonation.stop'));
    }

    public function testWithoutAnImpersonationThereIsNoBanner(): void
    {
        $operator = User::factory()->standard()->create();

        $this->actingAs($operator, GuardEnum::STANDARD->value)
            ->get(Filament::getPanel(PanelEnum::STANDARD->value)->getUrl())
            ->assertOk()
            ->assertDontSee(__('impersonation.stop'));
    }

    public function testADownloadIsNotRecordedWhileViewingAsSomeoneElse(): void
    {
        $admin = User::factory()->admin()->create();
        $operator = User::factory()->standard()->create();
        $channel = Channel::factory()->create();
        $channel->channelUsers()->attach($operator->getKey(), ['is_user_verified' => true]);
        $assignment = Assignment::factory()->forChannel($channel)->create([
            'status' => StatusEnum::NOTIFIED->value,
            'expires_at' => now()->addWeek(),
        ]);
        $this->actingAs($admin, GuardEnum::DEFAULT->value)->post(route('impersonation.start', $operator));

        $recorded = app(AssignmentService::class)->markDownloaded($assignment, '203.0.113.9', 'Test');

        self::assertFalse($recorded);
        $this->assertDatabaseMissing('downloads', ['assignment_id' => $assignment->getKey()]);
        $this->assertDatabaseHas('assignments', [
            'id' => $assignment->getKey(),
            'status' => StatusEnum::NOTIFIED->value,
        ]);
    }

    public function testADownloadIsRecordedNormallyWithoutAnImpersonation(): void
    {
        $operator = User::factory()->standard()->create();
        $channel = Channel::factory()->create();
        $channel->channelUsers()->attach($operator->getKey(), ['is_user_verified' => true]);
        $assignment = Assignment::factory()->forChannel($channel)->create([
            'status' => StatusEnum::NOTIFIED->value,
            'expires_at' => now()->addWeek(),
        ]);
        $this->actingAs($operator, GuardEnum::STANDARD->value);

        self::assertTrue(app(AssignmentService::class)->markDownloaded($assignment, '203.0.113.9', 'Test'));

        $this->assertDatabaseHas('downloads', ['assignment_id' => $assignment->getKey()]);
        $this->assertDatabaseHas('assignments', [
            'id' => $assignment->getKey(),
            'status' => StatusEnum::PICKEDUP->value,
        ]);
    }
}
