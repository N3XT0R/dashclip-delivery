<?php

declare(strict_types=1);

namespace Tests\Integration\Repository;

use App\Enum\Team\TeamSettingEnum;
use App\Models\Team;
use App\Models\TeamSetting;
use App\Models\User;
use App\Repository\TeamSettingRepository;
use Tests\DatabaseTestCase;

/**
 * A team keeps its own settings; until it chooses, what the setting itself declares applies.
 */
final class TeamSettingRepositoryTest extends DatabaseTestCase
{
    private TeamSettingRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = app(TeamSettingRepository::class);
    }

    public function testAnUnsetSettingAnswersWithItsDefault(): void
    {
        self::assertFalse($this->repository->get($this->team(), TeamSettingEnum::CENSOR_LICENSE_PLATES));
    }

    public function testAStoredSettingComesBackInItsOwnType(): void
    {
        $team = $this->team();

        $this->repository->set($team, TeamSettingEnum::CENSOR_LICENSE_PLATES, true);

        self::assertTrue($this->repository->get($team, TeamSettingEnum::CENSOR_LICENSE_PLATES));
        $this->assertDatabaseHas('team_settings', [
            'team_id' => $team->getKey(),
            'key' => TeamSettingEnum::CENSOR_LICENSE_PLATES->value,
            'type' => 'bool',
        ]);
    }

    public function testChoosingAgainReplacesTheValueInsteadOfAddingOne(): void
    {
        $team = $this->team();

        $this->repository->set($team, TeamSettingEnum::CENSOR_FACES, true);
        $this->repository->set($team, TeamSettingEnum::CENSOR_FACES, false);

        self::assertFalse($this->repository->get($team, TeamSettingEnum::CENSOR_FACES));
        self::assertSame(1, TeamSetting::query()->where('team_id', $team->getKey())->count());
    }

    public function testEveryTeamKeepsItsOwnChoice(): void
    {
        $chose = $this->team();
        $other = $this->team();
        $this->repository->set($chose, TeamSettingEnum::CENSOR_LICENSE_PLATES, true);

        self::assertTrue($this->repository->get($chose, TeamSettingEnum::CENSOR_LICENSE_PLATES));
        self::assertFalse($this->repository->get($other, TeamSettingEnum::CENSOR_LICENSE_PLATES));
    }

    public function testAllListsEverySettingIncludingTheUntouchedOnes(): void
    {
        $team = $this->team();
        $this->repository->set($team, TeamSettingEnum::CENSOR_FACES, true);

        $all = $this->repository->all($team);

        self::assertTrue($all[TeamSettingEnum::CENSOR_FACES->value]);
        self::assertFalse($all[TeamSettingEnum::CENSOR_LICENSE_PLATES->value]);
    }

    public function testSettingsGoAwayWithTheirTeam(): void
    {
        $team = $this->team();
        $this->repository->set($team, TeamSettingEnum::CENSOR_LICENSE_PLATES, true);

        $team->delete();

        $this->assertDatabaseMissing('team_settings', ['team_id' => $team->getKey()]);
    }

    private function team(): Team
    {
        return Team::factory()->forUser(User::factory()->create())->create();
    }
}
