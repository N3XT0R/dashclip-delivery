<?php

declare(strict_types=1);

namespace Tests\Integration\Filament\Admin\Resources;

use App\Constants\Config\CensorConfigEntry;
use App\Filament\Admin\Resources\Configs\Pages\ListConfigs;
use App\Models\Config\Category;
use App\Models\User;
use App\Services\Censor\VideoCensorInterface;
use App\Services\Contracts\ConfigServiceInterface;
use App\ValueObjects\CensorResult;
use Livewire\Livewire;
use Tests\DatabaseTestCase;

/**
 * The settings of the blurring say plainly when the server cannot act on them.
 */
final class CensorSetupWarningTest extends DatabaseTestCase
{
    private string $censorTab;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->admin()->create());
        $this->censorTab = (string)Category::query()
            ->firstOrCreate(
                ['slug' => CensorConfigEntry::CATEGORY],
                ['name' => 'Kennzeichen-Verpixelung', 'is_visible' => true],
            )
            ->getAttribute('name');
    }

    public function testTheSettingsWarnWhereTheToolingIsMissing(): void
    {
        $this->app->instance(VideoCensorInterface::class, new NotInstalledCensor());
        app(ConfigServiceInterface::class)
            ->set(CensorConfigEntry::ENABLED, true, CensorConfigEntry::CATEGORY, 'bool');

        Livewire::test(ListConfigs::class)
            ->set('activeTab', $this->censorTab)
            ->assertSee(__('configs.censor.not_installed'));
    }

    public function testTheSettingsSayWhenTheBlurringIsSwitchedOff(): void
    {
        $this->app->instance(VideoCensorInterface::class, new NotInstalledCensor());
        app(ConfigServiceInterface::class)
            ->set(CensorConfigEntry::ENABLED, false, CensorConfigEntry::CATEGORY, 'bool');

        Livewire::test(ListConfigs::class)
            ->set('activeTab', $this->censorTab)
            ->assertSee(__('configs.censor.switched_off'));
    }

    public function testTheSettingsSayNothingWhereTheToolingIsThere(): void
    {
        $this->app->instance(VideoCensorInterface::class, new InstalledCensor());

        Livewire::test(ListConfigs::class)
            ->set('activeTab', $this->censorTab)
            ->assertDontSee(__('configs.censor.not_installed'));
    }

    public function testOtherSettingsCarryNoSuchWarning(): void
    {
        $this->app->instance(VideoCensorInterface::class, new NotInstalledCensor());
        $other = Category::query()->where('slug', '!=', CensorConfigEntry::CATEGORY)->value('name');

        Livewire::test(ListConfigs::class)
            ->set('activeTab', (string)$other)
            ->assertDontSee(__('configs.censor.not_installed'));
    }
}

final class NotInstalledCensor implements VideoCensorInterface
{
    public function isAvailable(): bool
    {
        return false;
    }

    public function censor(string $sourcePath, string $targetPath): CensorResult
    {
        return new CensorResult($targetPath);
    }
}

final class InstalledCensor implements VideoCensorInterface
{
    public function isAvailable(): bool
    {
        return true;
    }

    public function censor(string $sourcePath, string $targetPath): CensorResult
    {
        return new CensorResult($targetPath);
    }
}
