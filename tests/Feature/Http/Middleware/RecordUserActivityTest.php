<?php

namespace Tests\Feature\Http\Middleware;

use App\Models\User;
use App\Repository\UserRepository;
use App\Http\Middleware\RecordUserActivity;
use Livewire\Livewire;
use Tests\DatabaseTestCase;

class RecordUserActivityTest extends DatabaseTestCase
{
    public function testExistingSessionRecordsActivityWithoutChangingLoginAndSuppressesReminder(): void
    {
        $this->travelTo(now()->startOfSecond());
        $user = User::factory()->standard()->create([
            'last_login_at' => now()->subYear(),
            'last_login_reminder_sent_at' => now()->subDays(8),
        ]);
        $lastLogin = $user->last_login_at->copy();
        $this->actingAs($user, 'standard')
            ->get(route('filament.standard.auth.profile'))->assertOk();

        $this->assertTrue($user->fresh()->last_activity_at->equalTo(now()));
        $this->assertTrue($user->fresh()->last_login_at->equalTo($lastLogin));
        $this->assertNull($user->fresh()->last_login_reminder_sent_at);
        $this->assertFalse(app(UserRepository::class)->getUsersEligibleForInactivityReminder()->contains($user));
        $this->assertContains(RecordUserActivity::class, Livewire::getPersistentMiddleware());

        $firstActivity = $user->fresh()->last_activity_at;
        $this->travel(4)->minutes();
        $this->get(route('filament.standard.auth.profile'))->assertOk();
        $this->assertTrue($user->fresh()->last_activity_at->equalTo($firstActivity));

        $this->travel(1)->minutes();
        $this->get(route('filament.standard.auth.profile'))->assertOk();
        $this->assertTrue($user->fresh()->last_activity_at->equalTo(now()));

        $this->travel(8)->days();
        $this->assertTrue(app(UserRepository::class)->getUsersEligibleForInactivityReminder()->contains($user));
    }

    public function testGuestRequestDoesNotRecordActivity(): void
    {
        $user = User::factory()->standard()->create();
        $this->get(route('filament.standard.auth.login'))->assertOk();
        $this->assertNull($user->fresh()->last_activity_at);
    }

    public function testAdminSessionRecordsActivity(): void
    {
        $this->travelTo(now()->startOfSecond());
        $user = User::factory()->admin()->create();
        $this->actingAs($user, 'web')->get(route('filament.admin.auth.profile'))->assertOk();
        $this->assertTrue($user->fresh()->last_activity_at->equalTo(now()));
    }
}
