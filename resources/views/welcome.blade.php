@extends('layouts.app')
@section('indexable', '1')
@section('title', __('public.page_title'))
@section('description', __('public.description'))
@section('full_width', '1')

@section('content')
    <section class="relative isolate overflow-hidden bg-ink text-white" aria-labelledby="hero-title">
        <picture class="absolute inset-0 -z-20">
            <source type="image/webp" srcset="{{ asset('images/marketing/hero-640.webp') }} 640w, {{ asset('images/marketing/hero-1280.webp') }} 1280w, {{ asset('images/marketing/hero-1920.webp') }} 1920w" sizes="100vw">
            <img src="{{ asset('images/marketing/hero.jpg') }}" width="1280" height="720" alt="" loading="eager" fetchpriority="high" class="h-full w-full object-cover object-center">
        </picture>
        <div class="absolute inset-0 -z-10 bg-linear-to-r from-ink/95 via-ink/75 to-ink/30"></div>
        <div class="public-width py-20 sm:py-24 lg:py-32">
            <p class="mb-6 text-sm font-semibold tracking-widest text-orange-300 uppercase">{{ __('public.hero_eyebrow') }}</p>
            <h1 id="hero-title" class="max-w-3xl text-4xl leading-[1.08] font-extrabold tracking-tight sm:text-5xl lg:text-6xl">{{ __('public.headline') }}<span class="mt-2 block text-orange-400">{{ __('public.headline_accent') }}</span></h1>
            <p class="mt-7 max-w-xl text-lg leading-8 text-slate-200">{{ __('public.hero_body') }}</p>
            <div class="mt-9 flex flex-col gap-4 sm:flex-row">
                <x-public.button :href="route('filament.standard.auth.register')"><x-heroicon-o-arrow-up-tray class="size-5" aria-hidden="true" />{{ __('public.upload') }}<x-heroicon-o-arrow-right class="size-5" aria-hidden="true" /></x-public.button>
                <x-public.button href="#ablauf" variant="secondary">{{ __('public.process') }}</x-public.button>
            </div>
            <p class="mt-7 text-sm text-slate-300">{{ __('public.already_member') }} <a class="font-medium text-white underline underline-offset-4" href="{{ route('filament.standard.auth.login') }}">{{ __('public.login_now') }}</a></p>
        </div>
    </section>

    <section class="public-width py-10" aria-labelledby="benefits-title">
        <h2 id="benefits-title" class="sr-only">{{ __('public.benefits_heading') }}</h2>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-public.feature-card :title="__('public.one_upload')" icon="heroicon-o-arrow-up-tray">{{ __('public.one_upload_body') }}</x-public.feature-card>
            <x-public.feature-card :title="__('public.matching_channels')" icon="heroicon-o-video-camera">{{ __('public.matching_channels_body') }}</x-public.feature-card>
            <x-public.feature-card :title="__('public.download')" icon="heroicon-o-arrow-down-tray">{{ __('public.download_body') }}</x-public.feature-card>
            <x-public.feature-card :title="__('public.overview')" icon="heroicon-o-clipboard-document-check">{{ __('public.overview_body') }}</x-public.feature-card>
        </div>
    </section>

    <section id="ablauf" class="public-section public-width">
        <x-public.section-heading :eyebrow="__('public.process')" :title="__('public.process_heading')">{{ __('public.process_intro') }}</x-public.section-heading>
        <ol class="grid gap-8 md:grid-cols-3">
            @foreach ([[__('public.register'), __('public.step_register')], [__('public.upload'), __('public.step_upload')], [__('public.step_distribute'), __('public.step_distribute_body')]] as [$title, $description])
                <li class="border-t border-border pt-6">
                    <span class="text-sm font-bold text-orange-700 dark:text-orange-400">0{{ $loop->iteration }}</span>
                    <h3 class="mt-4 mb-3 text-xl font-semibold">{{ $title }}</h3>
                    <p class="text-muted">{{ $description }}</p>
                </li>
            @endforeach
        </ol>
        <div class="mt-14 rounded-2xl border border-border bg-panel p-6 sm:p-10" aria-labelledby="guide-title">
            <h2 id="guide-title" class="text-2xl font-bold sm:text-3xl">{{ __('public.guide_heading') }}</h2>
            <p class="mt-4 max-w-3xl text-muted">{{ __('public.guide_intro') }}</p>
            <ol class="mt-8 grid grid-cols-1 gap-8 md:grid-cols-2">
                @foreach (__('public.guide_steps') as [$heading, $explanation])
                    <li class="min-w-0 border-t border-border pt-5">
                        <p class="mb-2 text-sm font-bold text-orange-700 dark:text-orange-400">{{ $loop->iteration }} / 6</p>
                        <h3 class="text-lg font-semibold">{{ $heading }}</h3>
                        <p class="mt-3 text-muted">{{ $explanation }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    <section id="kanaele" class="border-y border-border bg-panel">
        <div class="public-section public-width grid gap-12 lg:grid-cols-2 lg:gap-20">
            <div>
                <x-public.section-heading :eyebrow="__('public.submitters')" :title="__('public.submitter_heading')">{{ __('public.submitter_body') }}</x-public.section-heading>
                <x-public.button :href="route('filament.standard.auth.register')">{{ __('public.submitter_register') }}</x-public.button>
            </div>
            <div class="rounded-2xl border border-border bg-bg p-7 sm:p-10">
                <x-heroicon-o-video-camera class="mb-6 size-8 text-orange-700 dark:text-orange-400" aria-hidden="true" />
                <h2 class="text-2xl font-bold">{{ __('public.for_channels') }}</h2>
                <p class="mt-5 text-muted">{{ __('public.channel_body') }}</p>
                <p class="mt-4 text-muted">{{ __('public.publication') }}</p>
                <a href="#angebote" class="mt-7 inline-flex min-h-11 items-center gap-3 font-semibold">{{ __('public.about_offers') }}<x-heroicon-o-arrow-right class="size-5" aria-hidden="true" /></a>
            </div>
        </div>
    </section>

    <section id="angebote" class="public-section public-width">
        <x-public.section-heading :eyebrow="__('public.offers_eyebrow')" :title="__('public.offers_heading')">{{ __('public.offers_intro') }}</x-public.section-heading>
        <div class="grid gap-6 md:grid-cols-3">
            <x-public.feature-card :title="__('public.preview')" icon="heroicon-o-play-circle">{{ __('public.preview_body') }}</x-public.feature-card>
            <x-public.feature-card :title="__('public.zip')" icon="heroicon-o-folder-arrow-down">{{ __('public.zip_body') }}</x-public.feature-card>
            <x-public.feature-card :title="__('public.return')" icon="heroicon-o-arrow-uturn-left">{{ __('public.return_body') }}</x-public.feature-card>
        </div>
    </section>

    <section id="hilfe" class="border-t border-border bg-panel">
        <div class="public-section public-width grid gap-8 lg:grid-cols-2 lg:gap-20">
            <x-public.section-heading :eyebrow="__('public.help')" :title="__('public.help_heading')">{{ __('public.help_intro') }}</x-public.section-heading>
            <div class="divide-y divide-border border-y border-border">
                @foreach ([
                    __('public.faq_upload') => __('public.faq_upload_answer'),
                    __('public.faq_publication') => __('public.faq_publication_answer'),
                    __('public.faq_expiry') => __('public.faq_expiry_answer'),
                    __('public.faq_return') => __('public.faq_return_answer'),
                    __('public.faq_where') => __('public.faq_where_answer'),
                ] as $question => $answer)
                    <details class="py-5">
                        <summary class="pr-3 font-semibold">{{ $question }}</summary>
                        <p class="mt-4 text-muted">{{ $answer }}</p>
                    </details>
                @endforeach
            </div>
        </div>
    </section>

    <x-blog-homepage />
    <section class="bg-ink text-white">
        <div class="public-width flex flex-col items-start justify-between gap-8 py-16 lg:flex-row lg:items-center">
            <div><h2 class="text-3xl font-bold tracking-tight">{{ __('public.closing_heading') }}</h2><p class="mt-4 text-slate-300">{{ __('public.closing_body') }}</p></div>
            <div class="flex w-full flex-col gap-3 sm:w-auto sm:flex-row">
                <x-public.button :href="route('filament.standard.auth.register')">{{ __('public.register_now') }}</x-public.button>
                <x-public.button :href="route('filament.standard.auth.login')" variant="secondary">{{ __('public.login') }}</x-public.button>
            </div>
        </div>
    </section>
@endsection
