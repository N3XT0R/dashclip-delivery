<?php

declare(strict_types=1);

namespace Tests\Integration\Filament\Standard\Clusters\ChannelWorkspace\Resources;

use App\Auth\Abilities\AccessChannelPageAbility;
use App\Auth\Abilities\Contracts\AbilityContract;
use App\Enum\Guard\GuardEnum;
use App\Enum\PanelEnum;
use App\Enum\Users\RoleEnum;
use App\Filament\Standard\Clusters\ChannelWorkspace\Resources\ChannelResource;
use App\Models\Channel;
use App\Models\Team;
use App\Models\User;
use App\Repository\TeamRepository;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Tests\DatabaseTestCase;

final class ChannelSelfServiceSettingsTest extends DatabaseTestCase
{
    private User $user;

    private Channel $channel;

    private Team $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->channel = Channel::factory()->create();
        $this->allowChannelAccessAbility();
        $this->user = User::factory()
            ->withOwnTeam()
            ->withRole(RoleEnum::CHANNEL_OPERATOR, GuardEnum::STANDARD->value)
            ->haveAccessToChannel($this->channel)
            ->create();

        $this->tenant = app(TeamRepository::class)->getDefaultTeamForUser($this->user);

        Filament::setCurrentPanel(PanelEnum::STANDARD->value);
        Filament::setTenant($this->tenant, true);
        Filament::auth()->login($this->user);
        $this->actingAs($this->user, GuardEnum::STANDARD->value);
    }

    public function testTheOperatorFormOffersHomepageVisibilityAndLogo(): void
    {
        $components = $this->formComponentsByName();

        self::assertArrayHasKey('show_on_homepage', $components);
        self::assertInstanceOf(Toggle::class, $components['show_on_homepage']);

        self::assertArrayHasKey('logo_path', $components);
        self::assertInstanceOf(FileUpload::class, $components['logo_path']);
    }

    public function testTheLogoUploadIsRestrictedToSmallImagesOnThePublicDisk(): void
    {
        /** @var FileUpload $logo */
        $logo = $this->formComponentsByName()['logo_path'];

        self::assertSame('public', $logo->getDiskName());
        self::assertContains('image/webp', $logo->getAcceptedFileTypes());
        self::assertContains('image/png', $logo->getAcceptedFileTypes());
        self::assertNotContains('image/svg+xml', $logo->getAcceptedFileTypes());
        self::assertLessThanOrEqual(512, $logo->getMaxSize());
    }

    public function testAnOperatorChangesTheVisibilityOfTheirOwnChannelOnly(): void
    {
        $foreign = Channel::factory()->create(['show_on_homepage' => true]);

        self::assertTrue(ChannelResource::canEdit($this->channel));
        self::assertFalse(ChannelResource::canEdit($foreign));

        $ids = ChannelResource::getEloquentQuery()->pluck('id')->all();
        self::assertContains($this->channel->getKey(), $ids);
        self::assertNotContains($foreign->getKey(), $ids);
    }

    public function testTheVisibilityAndLogoChangesAreRecordedInTheActivityLog(): void
    {
        $this->channel->update([
            'show_on_homepage' => false,
            'logo_path' => 'channel-logos/new.webp',
        ]);

        $changes = $this->channel->activities()->latest('id')->first()?->changes['attributes'] ?? [];

        self::assertArrayHasKey('show_on_homepage', $changes);
        self::assertArrayHasKey('logo_path', $changes);
    }

    /**
     * @return array<string, \Filament\Schemas\Components\Component>
     */
    private function formComponentsByName(): array
    {
        $schema = ChannelResource::form(Schema::make());

        $byName = [];
        foreach ($schema->getComponents() as $component) {
            if (method_exists($component, 'getName')) {
                $byName[$component->getName()] = $component;
            }
        }

        return $byName;
    }

    private function allowChannelAccessAbility(): void
    {
        $this->app->bind(AbilityContract::class, AccessChannelPageAbility::class);
    }
}
