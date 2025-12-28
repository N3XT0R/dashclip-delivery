<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Resources\ChannelApplicationResource\Pages;

use App\Enum\Channel\ApplicationEnum;
use App\Filament\Resources\ChannelApplicationResource\Pages\ListChannelApplications;
use App\Models\Channel;
use App\Models\ChannelApplication;
use App\Models\User;
use Livewire\Livewire;
use Tests\DatabaseTestCase;

final class ListChannelApplicationsTest extends DatabaseTestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->actingAs($this->admin);
    }

    public function testListShowsPendingApplicationsByDefault(): void
    {
        $channel = Channel::factory()->create(['name' => 'Filament Channel']);
        $pending = ChannelApplication::factory()
            ->forExistingChannel($channel)
            ->withStatus(ApplicationEnum::PENDING)
            ->create();

        $approved = ChannelApplication::factory()
            ->forExistingChannel($channel)
            ->withStatus(ApplicationEnum::APPROVED)
            ->create();

        Livewire::test(ListChannelApplications::class)
            ->assertStatus(200)
            ->assertCanSeeTableRecords([$pending])
            ->assertCanNotSeeTableRecords([$approved]);
    }

    public function testListDisplaysColumnData(): void
    {
        $applicant = User::factory()->create(['name' => 'Applicant A']);
        $channel = Channel::factory()->create(['name' => 'Primary Channel']);

        $application = ChannelApplication::factory()
            ->for($applicant)
            ->forExistingChannel($channel)
            ->withStatus(ApplicationEnum::PENDING)
            ->create();

        Livewire::test(ListChannelApplications::class)
            ->assertStatus(200)
            ->assertCanSeeTableRecords([$application]);
    }
}
