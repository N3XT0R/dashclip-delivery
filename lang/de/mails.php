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
    'channel_welcome_email' => [
        'subject' => 'Willkommen beim wöchentlichen Video-Versand',
        'headline' => 'Bitte bestätige den wöchentlichen Video-Versand',
        'greeting' => 'Hi :name,',
        'channel_registered' => 'Dein Kanal wurde in <strong>:app_name</strong> eingetragen, damit du regelmäßig neue Videos direkt zur Veröffentlichung bekommst. Bevor der Versand startet, musst du nur kurz deine Teilnahme bestätigen.',
        'weekly_opt_in' => 'Wenn du mit dem wöchentlichen Versand einverstanden bist, klick einfach hier:',
        'approve' => 'Teilnahme bestätigen',
        'after_confirmation' => 'Nach der Bestätigung bekommst du automatisch die neuen Videos im gewohnten Rhythmus. Wenn du das irgendwann nicht mehr möchtest, reicht eine kurze Mail an <a href="mailto::email">:email</a>.',
        'signature' => 'Viele Grüße<br>Dein :app_name-Team',
    ],
];
