<?php

declare(strict_types=1);

return [
    'categories' => [
        'news' => ['icon' => 'news', 'translations' => [
            'de' => ['name' => 'Neuigkeiten', 'slug' => 'neuigkeiten', 'description' => 'Neue Funktionen und Entwicklungen rund um DashClip Delivery.'],
            'en' => ['name' => 'News', 'slug' => 'news', 'description' => 'New features and developments at DashClip Delivery.'],
        ]],
        'dashcam-knowledge' => ['icon' => 'camera', 'translations' => [
            'de' => ['name' => 'Dashcam-Wissen', 'slug' => 'dashcam-wissen', 'description' => 'Verständliche Grundlagen rund um Dashcams und ihre Aufnahmen.'],
            'en' => ['name' => 'Dashcam knowledge', 'slug' => 'dashcam-knowledge', 'description' => 'Accessible explanations of dashcams and their recordings.'],
        ]],
        'submission-tips' => ['icon' => 'video', 'translations' => [
            'de' => ['name' => 'Tipps zum Einsenden', 'slug' => 'tipps-zum-einsenden', 'description' => 'Orientierung vom ersten Upload bis zur Auswahl der passenden Kanäle.'],
            'en' => ['name' => 'Submission tips', 'slug' => 'submission-tips', 'description' => 'Guidance from your first upload to choosing the right channels.'],
        ]],
    ],
    'tags' => [
        'dashclip-delivery' => [
            'de' => ['name' => 'DashClip Delivery', 'slug' => 'dashclip-delivery'],
            'en' => ['name' => 'DashClip Delivery', 'slug' => 'dashclip-delivery'],
        ],
        'updates' => [
            'de' => ['name' => 'Updates', 'slug' => 'updates'],
            'en' => ['name' => 'Updates', 'slug' => 'updates'],
        ],
        'getting-started' => [
            'de' => ['name' => 'Erste Schritte', 'slug' => 'erste-schritte'],
            'en' => ['name' => 'Getting started', 'slug' => 'getting-started'],
        ],
        'dashcams' => [
            'de' => ['name' => 'Dashcams', 'slug' => 'dashcams'],
            'en' => ['name' => 'Dashcams', 'slug' => 'dashcams'],
        ],
        'clip-submission' => [
            'de' => ['name' => 'Clips einsenden', 'slug' => 'clips-einsenden'],
            'en' => ['name' => 'Submitting clips', 'slug' => 'submitting-clips'],
        ],
    ],
    'article' => [
        'de' => [
            'slug' => 'willkommen-im-dashclip-blog',
            'title' => 'Der DashClip-Blog ist da: Wissen und Updates für deine Clips',
            'excerpt' => 'DashClip Delivery bekommt einen eigenen Blog. Hier erfährst du, welche Themen dich erwarten, wie du passende Beiträge findest und wie der Blog dir beim Einstieg in die Plattform hilft.',
            'meta_title' => 'Willkommen im DashClip-Blog | DashClip Delivery',
            'meta_description' => 'Der neue DashClip-Blog: Neuigkeiten zur Plattform, verständliches Dashcam-Wissen und Tipps zum Einsenden. Entdecke Kategorien, Themen und den RSS-Feed.',
            'content' => file_get_contents(__DIR__.'/blog-welcome.de.md'),
        ],
        'en' => [
            'slug' => 'welcome-to-the-dashclip-blog',
            'title' => 'Introducing the DashClip blog: knowledge and updates for your clips',
            'excerpt' => 'DashClip Delivery now has its own blog. Find out which topics belong here, how to discover relevant articles and how the blog helps you get started with the platform.',
            'meta_title' => 'Welcome to the DashClip blog | DashClip Delivery',
            'meta_description' => 'Introducing the DashClip blog: platform news, accessible dashcam knowledge and submission tips. Explore categories, topics and the RSS feed.',
            'content' => file_get_contents(__DIR__.'/blog-welcome.en.md'),
        ],
    ],
];
