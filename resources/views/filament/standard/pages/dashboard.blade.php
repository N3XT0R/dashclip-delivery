<x-filament-panels::page>
    <div class="dc-dashboard">
        <section class="dc-hero" aria-labelledby="dashboard-welcome">
            <img src="{{ asset('images/marketing/hero-1920.webp') }}" alt="" class="dc-hero-image" fetchpriority="high">
            <div class="dc-hero-copy">
                <p class="dc-eyebrow">{{ __('dashboard.greeting', ['name' => $user->name]) }}</p>
                <h1 id="dashboard-welcome">{{ __('dashboard.welcome') }} <span>{{ __('dashboard.back') }}</span></h1>
                <p>{{ __('dashboard.intro') }}</p>
            </div>
            <p class="dc-hero-motto">{{ __('dashboard.motto') }}</p>
        </section>

        <div class="dc-stats">
            @foreach ($stats as $stat)
                <div class="dc-stat">
                    <span class="dc-stat-icon"><x-filament::icon :icon="$stat['icon']" /></span>
                    <div>
                        <p class="dc-stat-label">{{ __('dashboard.'.$stat['label']) }}</p>
                        <p class="dc-stat-value">{{ $stat['count'] === null ? '–' : Number::format($stat['count'], locale: app()->getLocale()) }}</p>
                        <p class="dc-muted">{{ __('dashboard.'.$stat['description']) }}</p>
                    </div>
                    @if ($stat['url'])
                        <a class="dc-stat-link" href="{{ $stat['url'] }}" aria-label="{{ __('dashboard.'.$stat['label']) }}"><x-heroicon-o-chevron-right /></a>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="dc-dashboard-columns">
            <section class="dc-card" aria-labelledby="recent-videos">
                <div class="dc-card-heading">
                    <h2 id="recent-videos">{{ __('dashboard.recent_videos') }}</h2>
                    @if ($videosUrl)
                        <a href="{{ $videosUrl }}">{{ __('dashboard.view_all') }} <x-heroicon-o-arrow-right /></a>
                    @endif
                </div>
                <div class="dc-video-list">
                    @forelse ($videos as $video)
                        <div class="dc-video-row">
                            <div class="dc-video-preview">
                                @if ($video['preview'])
                                    <video preload="metadata" muted playsinline aria-label="{{ $video['name'] }}" src="{{ $video['preview'] }}#t=0.1"></video>
                                @else
                                    <x-heroicon-o-video-camera aria-hidden="true" />
                                @endif
                                @if ($video['duration'])
                                    <span>{{ sprintf('%02d:%02d', intdiv((int) $video['duration'], 60), (int) $video['duration'] % 60) }}</span>
                                @endif
                            </div>
                            <div class="dc-video-info">
                                @if ($video['url'])
                                    <a href="{{ $video['url'] }}">{{ $video['name'] }}</a>
                                @else
                                    <p>{{ $video['name'] }}</p>
                                @endif
                                <p class="dc-muted">{{ $video['date'] }} @if($video['size']) · {{ $video['size'] }} @endif</p>
                            </div>
                            @if ($video['url'])
                                <a class="dc-video-open" href="{{ $video['url'] }}" aria-label="{{ __('dashboard.open_video', ['name' => $video['name']]) }}"><x-heroicon-o-arrow-up-right /></a>
                            @endif
                        </div>
                    @empty
                        <div class="dc-empty">
                            <span class="dc-stat-icon"><x-heroicon-o-video-camera /></span>
                            <h3>{{ __('dashboard.'.($canViewVideos ? 'empty_videos' : 'no_video_access')) }}</h3>
                            <p class="dc-muted">{{ __('dashboard.'.($canViewVideos ? 'empty_hint' : 'no_access_hint')) }}</p>
                        </div>
                    @endforelse
                </div>
            </section>

            <div class="dc-dashboard-aside">
                <section class="dc-card" aria-labelledby="quick-actions">
                    <div class="dc-card-heading"><h2 id="quick-actions"><x-heroicon-o-bolt class="dc-orange" /> {{ __('dashboard.quick_actions') }}</h2></div>
                    <div class="dc-actions">
                        @foreach ($actions as $action)
                            <a href="{{ $action['url'] }}"><x-filament::icon :icon="$action['icon']" /><span>{{ __('dashboard.'.$action['label']) }}</span><x-heroicon-o-chevron-right /></a>
                        @endforeach
                        @if (empty($actions))
                            <a href="{{ route('home') }}#ablauf"><x-heroicon-o-question-mark-circle /><span>{{ __('dashboard.guide') }}</span><x-heroicon-o-chevron-right /></a>
                        @endif
                    </div>
                </section>
                <section class="dc-card" aria-labelledby="next-steps">
                    <div class="dc-card-heading"><h2 id="next-steps"><x-heroicon-o-list-bullet /> {{ __('dashboard.next_steps') }}</h2></div>
                    <div class="dc-steps">
                        @forelse ($steps as $step)
                            <a href="{{ $step['url'] }}" class="dc-step">
                                <span @class(['dc-step-check', 'dc-step-done' => $step['done']])>
                                    @if ($step['done'])<x-heroicon-m-check /><span class="sr-only">{{ __('dashboard.completed') }}</span>@endif
                                </span>
                                <span><strong>{{ __('dashboard.'.$step['label']) }}</strong><span class="dc-muted">{{ __('dashboard.'.$step['description']) }}</span></span>
                                <x-heroicon-o-chevron-right />
                            </a>
                        @empty
                            <p class="dc-muted">{{ __('dashboard.no_access_hint') }}</p>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>
    </div>
    @if (collect($this->getWidgets())->contains(fn (string $widget): bool => $widget::canView()))
        {{ $this->content }}
    @endif
</x-filament-panels::page>
