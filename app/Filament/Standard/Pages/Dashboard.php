<?php

declare(strict_types=1);

namespace App\Filament\Standard\Pages;

use App\Filament\Standard\Widgets\StagingLinkWidget;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Widgets\Widget;
use App\Application\Channel\GetCurrentChannel;
use App\Application\Clips\GetPreviewUrl;
use App\Filament\Standard\Resources\ChannelTeamResource;
use App\Filament\Standard\Resources\VideoResource;
use App\Models\Team;
use App\Models\User;
use App\Models\Video;
use App\Repository\AssignmentRepository;
use App\Repository\DashboardRepository;
use Filament\Facades\Filament;
use N3XT0R\FilamentPassportUi\Resources\ClientResource;
use N3XT0R\LaravelWebdavServerFilament\Resources\UserWebDavAccountResource;

class Dashboard extends BaseDashboard
{
    protected string $view = 'filament.standard.pages.dashboard';

    public function getHeading(): string
    {
        return '';
    }

    /**
     * Prepare authorized dashboard links and data for the current user and tenant.
     *
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        /** @var User $user */
        $user = Filament::auth()->user();
        /** @var Team $team */
        $team = Filament::getTenant();
        $repository = app(DashboardRepository::class);
        $canViewVideos = VideoResource::canViewAny();
        $videos = $canViewVideos ? $repository->recentVideos($user, $team) : collect();
        $channelCount = ChannelTeamResource::canViewAny() ? $repository->activeChannelCount($team) : null;
        $hasWebDav = $repository->hasWebDavAccount($user);
        $channel = MyOffers::canAccess() ? app(GetCurrentChannel::class)->handle() : null;

        $stats = [
            ['label' => 'videos', 'count' => $canViewVideos ? $repository->videos($user, $team)->count() : null, 'description' => 'in_team', 'icon' => 'heroicon-o-video-camera', 'url' => $canViewVideos ? VideoResource::getUrl() : null],
            ['label' => 'downloads', 'count' => $repository->recentDownloadCount($user, $team), 'description' => 'last_30_days', 'icon' => 'heroicon-o-arrow-down-tray', 'url' => DownloadHistory::getUrl()],
            ['label' => 'channels', 'count' => $channelCount, 'description' => 'active', 'icon' => 'heroicon-o-tv', 'url' => ChannelTeamResource::canViewAny() ? ChannelTeamResource::getUrl() : null],
            ['label' => 'offers', 'count' => $channel ? app(AssignmentRepository::class)->getAvailableOffersCountForChannel($channel) : null, 'description' => 'available_offers', 'icon' => 'heroicon-o-tag', 'url' => $channel ? MyOffers::getUrl() : null],
        ];
        $actions = [];
        $steps = [];
        if (UserWebDavAccountResource::shouldRegisterNavigation() && UserWebDavAccountResource::canViewAny()) {
            $url = UserWebDavAccountResource::getUrl();
            $actions[] = ['label' => 'webdav_action', 'icon' => 'heroicon-o-key', 'url' => $url];
            $steps[] = ['label' => 'webdav_step', 'description' => 'webdav_hint', 'done' => $hasWebDav, 'url' => $url];
        }
        if (ChannelTeamResource::canViewAny()) {
            $url = ChannelTeamResource::getUrl();
            $actions[] = ['label' => 'channel_action', 'icon' => 'heroicon-o-tv', 'url' => $url];
            $steps[] = ['label' => 'channel_step', 'description' => 'channel_hint', 'done' => $channelCount > 0, 'url' => $url];
        }
        if (ClientResource::canViewAny()) {
            $actions[] = ['label' => 'api_action', 'icon' => 'heroicon-o-code-bracket', 'url' => ClientResource::getUrl()];
        }
        if ($channel) {
            $actions[] = ['label' => 'offers_action', 'icon' => 'heroicon-o-tag', 'url' => MyOffers::getUrl()];
        } elseif (ChannelApplication::canAccess()) {
            $actions[] = ['label' => 'apply_action', 'icon' => 'heroicon-o-pencil-square', 'url' => ChannelApplication::getUrl()];
        }
        if ($canViewVideos) {
            $steps[] = ['label' => 'video_step', 'description' => 'video_hint', 'done' => $videos->isNotEmpty(), 'url' => VideoResource::getUrl()];
        }

        return compact('user', 'stats', 'actions', 'steps', 'canViewVideos') + [
            'videos' => $videos->map(fn (Video $video): array => [
                'name' => $video->original_name,
                'date' => $video->created_at->translatedFormat('j. M Y'),
                'size' => $video->human_readable_size,
                'duration' => $video->clips->first()?->duration,
                'preview' => app(GetPreviewUrl::class)->handle($video->clips->first()),
                'url' => VideoResource::canView($video) ? VideoResource::getUrl('view', ['record' => $video]) : null,
            ]),
            'videosUrl' => $canViewVideos ? VideoResource::getUrl() : null,
        ];
    }

    /** @return array<class-string<Widget>> Supplemental staging widgets. */
    public function getWidgets(): array
    {
        $widgets = [];

        if (app()->environment('production', 'local')) {
            $widgets[] = StagingLinkWidget::class;
        }

        return $widgets;
    }
}
