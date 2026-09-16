<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Standard\Pages;

use App\Enum\Guard\GuardEnum;
use App\Filament\Standard\Pages\Dashboard;
use App\Models\Assignment;
use App\Models\Download;
use App\Models\Team;
use App\Models\User;
use App\Models\Video;
use App\Repository\TeamRepository;
use DOMDocument;
use DOMXPath;
use Filament\Facades\Filament;
use Spatie\Permission\Models\Role;
use Tests\DatabaseTestCase;

final class DashboardTest extends DatabaseTestCase
{
    public function testDashboardShowsOnlyOwnRecentTeamVideosAndRecentDownloads(): void
    {
        $user = $this->login();
        $team = Filament::getTenant();
        $videos = Video::factory()->count(6)->for($team, 'team')->withClips(1, $user)
            ->sequence(fn ($sequence) => ['original_name' => 'own-video-'.$sequence->index.'.mp4', 'created_at' => now()->subDays($sequence->index)])
            ->create();
        $otherTeamVideo = Video::factory()->withClips(1, $user)->create(['original_name' => 'other-team-secret.mp4']);
        $foreignVideo = Video::factory()->for($team, 'team')->withClips(1)->create(['original_name' => 'other-user-secret.mp4']);
        Download::factory()->forAssignment(Assignment::factory()->for($videos->first())->create())->create();
        Download::factory()->forAssignment(Assignment::factory()->for($videos->first())->create())->at(now()->subDays(31))->create();
        Download::factory()->forAssignment(Assignment::factory()->for($otherTeamVideo)->create())->create();
        Download::factory()->forAssignment(Assignment::factory()->for($foreignVideo)->create())->create();

        $response = $this->get(Dashboard::getUrl())->assertOk()
            ->assertSee('own-video-0.mp4')->assertSee('own-video-4.mp4')
            ->assertDontSee('own-video-5.mp4')->assertDontSee('other-team-secret.mp4')->assertDontSee('other-user-secret.mp4');
        $document = new DOMDocument();
        @$document->loadHTML($response->getContent());
        $values = (new DOMXPath($document))->query('//p[@class="dc-stat-value"]');
        $this->assertSame('6', trim($values->item(0)->textContent));
        $this->assertSame('1', trim($values->item(1)->textContent));
        $logoLinks = (new DOMXPath($document))->query('//a[.//*[contains(concat(" ", normalize-space(@class), " "), " dc-brand ")]]');
        $this->assertGreaterThan(0, $logoLinks->length);
        foreach ($logoLinks as $link) {
            $this->assertSame(Dashboard::getUrl(), $link->getAttribute('href'));
        }
    }

    public function testDashboardProvidesAnEnglishEmptyStateAndEscapesTheUserName(): void
    {
        $user = $this->login();
        $user->forceFill(['locale' => 'en', 'name' => '<script>alert(1)</script>', 'onboarding_completed' => false])->save();

        $this->get(Dashboard::getUrl())->assertOk()
            ->assertSee('Your footage starts here')
            ->assertDontSee('Willkommen im Panel')
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function testDashboardDoesNotExposeVideoDataWithoutPermission(): void
    {
        $user = $this->login();
        $user->syncRoles([Role::findOrCreate('dashboard-reader', GuardEnum::STANDARD->value)]);
        Video::factory()->for(Filament::getTenant(), 'team')->withClips(1, $user)->create(['original_name' => 'restricted-video.mp4']);

        $this->get(Dashboard::getUrl())->assertOk()
            ->assertDontSee('restricted-video.mp4')
            ->assertSee(__('dashboard.no_video_access'));
    }

    public function testDashboardRejectsAnotherUsersTeam(): void
    {
        $this->login();
        $otherTeam = Team::factory()->create();

        $this->get(Dashboard::getUrl(tenant: $otherTeam))->assertNotFound();
    }

    public function testLoginPageLogoLinksToTheHomepageWithoutATenant(): void
    {
        Filament::setCurrentPanel('standard');
        Filament::setTenant(null);

        $response = $this->get(route('filament.standard.auth.login'))->assertOk();
        $this->assertSame(route('home'), Filament::getHomeUrl());
        $response->assertSee('images/marketing/logo.webp');
    }

    private function login(): User
    {
        $user = User::factory()->withOwnTeam()->admin(GuardEnum::STANDARD)->create(['onboarding_completed' => true]);
        $team = app(TeamRepository::class)->getDefaultTeamForUser($user);
        Filament::setCurrentPanel('standard');
        Filament::setTenant($team, true);
        $this->actingAs($user, GuardEnum::STANDARD->value);

        return $user;
    }
}
