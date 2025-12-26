<?php

declare(strict_types=1);

return [
    'channel_access_request' => [
        'subject' => 'Zugriff auf Kanal freigeben',

        'headline' => 'Zugriffsanfrage für einen Kanal',

        'greeting' => 'Hallo :name,',

        'intro' =>
            'für den folgenden Kanal wurde eine Zugriffsanfrage gestellt:',

        'instruction' =>
            'Wenn du den Zugriff freigeben möchtest, bestätige dies bitte über den folgenden Button.',

        'approve' => 'Zugriff freigeben',

        'outro' =>
            'Nach der Bestätigung erhält die angefragte Person Zugriff gemäß den erteilten Berechtigungen.',

        'revoke_hint' =>
            'Der Zugriff kann später jederzeit durch berechtigte Personen widerrufen werden.',

        'signature' => 'Viele Grüße<br>Dein :app-Team',
    ],
];
