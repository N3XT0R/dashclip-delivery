<?php

declare(strict_types=1);

return [
    'channel_activation' => [
        'title' => 'Teilnahme bestätigt',
        'subtitle' => 'Kanalbestätigung',
        'headline' => 'Teilnahme erfolgreich bestätigt',

        'thanks' => 'Vielen Dank, :name!',

        'description' =>
            'Der Zugriff auf den Kanal wurde erfolgreich freigegeben.',

        'availability_notice' =>
            'Ab sofort steht der Kanal den berechtigten Personen gemäß den erteilten Berechtigungen zur Verfügung.',

        'revoke_notice' =>
            'Die Freigabe kann jederzeit über das Benutzerkonto oder durch berechtigte Personen widerrufen werden.',

        'back' => 'Zur Startseite',
    ],
    'channel_access' => [
        'title' => 'Zugriff bestätigt',
        'subtitle' => 'Kanalzugriff',
        'headline' => 'Zugriff erfolgreich bestätigt',

        'thanks' => 'Vielen Dank!',

        'description' =>
            'Die Zugriffsanfrage für den Kanal :channel wurde erfolgreich freigegeben.',

        'access_granted' =>
            'Die freigegebene Person kann den Kanal ab sofort gemäß den erteilten Berechtigungen nutzen.',

        'revoke_notice' =>
            'Der Zugriff kann jederzeit durch den Kanalbetreiber oder berechtigte Teammitglieder widerrufen werden.',

        'back' => 'Zur Startseite',
    ],
    'channel_reception_reactivation' => [
        'confirm' => [
            'title'    => 'Empfang reaktivieren',
            'headline' => 'Video-Empfang reaktivieren',
            'body'     => 'Möchtest du den wöchentlichen Video-Empfang für den Kanal <strong>:channel</strong> wieder aktivieren?',
            'cta'      => 'Ja, Empfang reaktivieren',
        ],
        'success' => [
            'title'    => 'Empfang reaktiviert',
            'headline' => 'Video-Empfang erfolgreich reaktiviert',
            'body'     => 'Der wöchentliche Video-Empfang für den Kanal :channel wurde erfolgreich wieder aktiviert.',
            'back'     => 'Zur Startseite',
        ],
    ],
];

