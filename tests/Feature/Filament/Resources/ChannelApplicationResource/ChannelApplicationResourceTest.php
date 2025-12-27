<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Resources\ChannelApplicationResource;

use App\Enum\Channel\ApplicationEnum;
use App\Filament\Resources\ChannelApplicationResource;
use App\Filament\Resources\ChannelApplicationResource\Pages\EditChannelApplication;
use App\Filament\Resources\ChannelApplicationResource\Pages\ListChannelApplications;
use App\Models\Channel;
use App\Models\ChannelApplication;
use App\Models\User;
use Filament\Resources\Pages\PageRegistration;
use Livewire\Livewire;
use Tests\DatabaseTestCase;

final class ChannelApplicationResourceTest extends DatabaseTestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->actingAs($this->admin);
    }

    public function testResourceCannotCreateRecords(): void
    {
        $this->assertFalse(ChannelApplicationResource::canCreate());
    }

    public function testResourceRegistersExpectedPages(): void
    {
        $pages = ChannelApplicationResource::getPages();

        $this->assertSame(['index', 'edit'], array_keys($pages));
        $this->assertContainsOnlyInstancesOf(PageRegistration::class, $pages);
        $this->assertSame(ListChannelApplications::class, $pages['index']->getPage());
        $this->assertSame(EditChannelApplication::class, $pages['edit']->getPage());
    }

    public function testTableShowsApplicationsWithApplicantAndStatus(): void
    {
        $applicant = User::factory()->create(['name' => 'Applicant User']);
        $channel = Channel::factory()->create(['name' => 'Existing Channel']);

        $pending = ChannelApplication::factory()
            ->for($applicant)
            ->forExistingChannel($channel)
            ->withStatus(ApplicationEnum::PENDING)
            ->create();

        $approved = ChannelApplication::factory()
            ->for($applicant)
            ->forExistingChannel($channel)
            ->withStatus(ApplicationEnum::APPROVED)
            ->create();

        Livewire::test(ListChannelApplications::class)
            ->assertStatus(200)
            ->assertCanSeeTableRecords([$pending])
            ->assertCanNotSeeTableRecords([$approved])
            ->assertTableColumnStateSet('user.name', $applicant->name, record: $pending)
            ->assertTableColumnStateSet('channel.name', $channel->name, record: $pending)
            ->assertTableColumnStateSet('status', ApplicationEnum::PENDING->value, record: $pending)
            ->assertTableColumnExists('created_at', record: $pending)
            ->assertTableColumnExists('updated_at', record: $pending);
    }
}
