<?php

declare(strict_types=1);

return [
    'common' => [
        'expires_at' => 'Dieser Link ist bis zum :date gültig.',
        'unknown_user' => 'Unbekannter Nutzer',
        'channel' => 'Kanal:',
    ],
    'channel_access_request' => [
        'subject' => 'Zugriff auf Kanal freigeben',

        'headline' => 'Zugriffsanfrage für einen Kanal',

        'greeting' => 'Hallo :name,',

        'intro' =>
            'für den folgenden Kanal wurde eine Zugriffsanfrage gestellt:',
        'requested_by' => 'Zugriffsanfrage gestellt von:',

        'instruction' =>
            'Wenn du den Zugriff freigeben möchtest, bestätige dies bitte über den folgenden Button.',

        'approve' => 'Zugriff freigeben',

        'outro' =>
            'Nach der Bestätigung erhält die angefragte Person Zugriff gemäß den erteilten Berechtigungen.',

        'revoke_hint' =>
            'Der Zugriff kann später jederzeit durch berechtigte Personen widerrufen werden.',

        'signature' => 'Viele Grüße<br>Dein :app-Team',
        'note_label' => 'Nachricht des Antragstellers:',
    ],
    'channel_access_approved' => [
        'subject' => 'Zugriff auf Kanal freigegeben',

        'headline' => 'Zugriff freigegeben',

        'greeting' => 'Hallo :name,',

        'intro' =>
            'deine Anfrage für den folgenden Kanal wurde genehmigt:',

        'access_notice' =>
            'Du kannst den Kanal ab sofort gemäß den erteilten Berechtigungen nutzen.',

        'signature' => 'Viele Grüße<br>Dein :app-Team',
    ],
];
