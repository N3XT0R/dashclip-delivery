<?php

declare(strict_types=1);

return [
    'channel_application' => [
        'title' => 'Zugang zu Kanalvideos beantragen',
        'navigation_label' => 'Zugang beantragen',
        'navigation_group' => __('nav.channel_owner'),
        'table' => [
            'record_title' => 'Historie der Kanalzugangs-Anfragen',
            'columns' => [
                'channel' => 'Kanal',
                'status' => 'Status',
                'reject_reason' => 'Ablehnungsgrund',
                'submitted_at' => 'Eingereicht am',
                'updated_at' => 'Aktualisiert am',
                'channel_unknown' => 'Neuer Kanal',
            ],
            'actions' => [
                'view' => [
                    'label' => 'Details anzeigen',
                    'modal_heading' => 'Details zur Kanalzugangs-Anfrage',
                ],
            ],
        ],
        'form' => [
            'about_title' => 'Vorteile der freiwilligen Registrierung',
            'about_intro' => 'Mit einer kostenlosen Registrierung als Kanalbetreiber erhältst du zusätzliche Sicherheit und Kontrolle für deine Kanalangebote – ohne dass sich am bisherigen Ablauf etwas ändert.',
            'about_benefit_security_title' => 'Mehr Sicherheit',
            'about_benefit_security' => 'Nur registrierte und eingeloggte Kanalbetreiber können exklusive Angebotslinks nutzen. So sind deine Inhalte noch besser vor fremden Zugriffen geschützt.',
            'about_benefit_control_title' => 'Volle Kontrolle',
            'about_benefit_control' => 'Dein Benutzerkonto ist direkt mit deinem Kanal verknüpft. Du kannst alle Angebote einfach und sicher verwalten.',
            'about_benefit_portal_title' => 'Alles auf einen Blick',
            'about_benefit_portal' => 'Alle dir zugeteilten Videos findest du nicht nur in deinen E-Mail-Angeboten, sondern auch jederzeit übersichtlich in deinem persönlichen Portal. Dort kannst du alle Clips verwalten, herunterladen oder den aktuellen Status einsehen.',
            'about_benefit_remain_title' => 'Freiwillig & Flexibel',
            'about_benefit_remain' => 'Die Registrierung ist freiwillig und deine bisherigen Zugriffswege per E-Mail-Link bleiben weiterhin vollständig verfügbar.',
            'about_footer' => 'Die Freischaltung erfolgt nach kurzer Prüfung. Anschließend hast du Zugriff auf alle aktuellen und zukünftigen Angebote in deinem Kanal.',
            'request_other_channel' => 'Mein Kanal ist nicht in der Liste',
            'channel_label' => 'Vorhandenen Kanal auswählen',
            'new_channel_section_label' => 'Angaben für neuen Kanal',
            'new_channel_name_label' => 'Kanalname',
            'new_channel_name_placeholder' => 'Vollständigen Namen des gewünschten Kanals eingeben',
            'new_channel_creator_name_label' => 'Name des Betreibers',
            'new_channel_creator_name_placeholder' => 'Name der verantwortlichen Person oder Organisation',
            'new_channel_email_label' => 'Kontakt-E-Mail',
            'new_channel_email_placeholder' => 'E-Mail-Adresse für den Kanal eintragen',
            'new_channel_youtube_name_label' => 'YouTube-Kanal (optional)',
            'new_channel_youtube_name_placeholder' => 'YouTube-Kanal-Name (optional)',
            'note_label' => 'Begründung',
            'reject_reason_label' => 'Ablehnungsgrund',
            'note_placeholder' => 'Geben Sie hier einen kurzen Grund an, warum Sie Zugang zu den Videos dieses Kanals benötigen.',
            'submit' => 'Bewerbung absenden',
            'status_title' => 'Antrag bereits gestellt für :channel',
            'status_message' => 'Du hast bereits eine Bewerbung für diesen Kanal gestellt. Status: :status',
            'status_note' => 'Bitte warte, bis die Bewerbung bearbeitet wurde. Wir kontaktieren dich sobald wie möglich.',
            'submitted_at' => 'Eingereicht am:',
            'choose_channel' => 'Kanal auswählen',
        ],
        'status' => [
            'pending' => 'In Bearbeitung',
            'approved' => 'Genehmigt',
            'rejected' => 'Abgelehnt',
        ],
        'messages' => [
            'success' => [
                'application_submitted' => 'Anfrage eingereicht!',
            ],
            'error' => [
                'already_applied' => 'Du hast für diesen Kanal bereits eine Anfrage eingereicht.',
                'no_channels' => 'Es sind keine Kanäle für eine Anfrage verfügbar.',
            ],
        ],
    ],
    'admin_channel_application' => [
        'navigation_label' => 'Kanalzugangs-Anfragen',
        'navigation_group' => __('nav.channels'),
        'table' => [
            'columns' => [
                'applicant' => 'Bewerber',
                'channel' => 'Kanal',
                'status' => 'Status',
                'submitted_at' => 'Eingereicht am',
                'updated_at' => 'Aktualisiert am',
            ],
        ],
        'form' => [
            'fields' => [
                'user_email' => 'Benutzer-E-Mail',
                'note' => 'Notiz',
                'reason' => 'Begründung',
                'new_channel' => 'Neuer Kanal',
                'new_channel_name_label' => 'Kanalname',
                'new_channel_creator_name_label' => 'Name des Betreibers',
                'new_channel_email_label' => 'Kontakt-E-Mail',
                'new_channel_youtube_name_label' => 'YouTube-Kanal (optional)',
            ],
            'sections' => [
                'existing_channel' => 'Vorhandener Kanal',
                'new_channel' => 'Neuer Kanal',
            ],
        ],
        'status' => [
            'pending' => 'In Bearbeitung',
            'approved' => 'Genehmigt',
            'rejected' => 'Abgelehnt',
        ],
    ],
    'relation_manager' => [
        \App\Filament\Resources\UserResource\Pages\EditUser::class => [
            'title' => 'Zugewiesene Kanäle',
        ]
    ],
    'user_revoke_channel_access' => [
        'label' => 'Kanalzugriff entziehen',
        'success_notification_title' => 'Kanalzugriff wurde entzogen',
    ],
];
