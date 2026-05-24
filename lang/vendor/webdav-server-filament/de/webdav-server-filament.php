<?php

return [
    'resources' => [
        'accounts' => [
            'labels' => [
                'singular' => 'WebDAV-Konto',
                'plural' => 'WebDAV-Konten',
            ],
            'navigation' => [
                'label' => 'WebDAV-Konten',
            ],
            'fields' => [
                'username' => 'Benutzername',
                'display_name' => 'Anzeigename',
                'password' => 'Passwort',
                'password_confirmation' => 'Passwort bestätigen',
                'new_password' => 'Neues Passwort',
                'new_password_confirmation' => 'Neues Passwort bestätigen',
                'user' => 'Benutzer',
                'enabled' => 'Aktiviert',
                'meta' => 'Metadaten',
                'meta_key' => 'Schlüssel',
                'meta_value' => 'Wert',
                'created_at' => 'Erstellt',
                'webdav_url' => 'WebDAV-URL',
            ],
            'placeholders' => [
                'display_name' => 'Verwendet den Benutzernamen, wenn leer gelassen',
            ],
            'empty' => [
                'display_name' => '-',
            ],
            'filters' => [
                'status' => 'Status',
                'active' => 'Aktiv',
                'inactive' => 'Inaktiv',
            ],
            'actions' => [
                'generate_password' => 'Passwort generieren',
                'reset_password' => 'Passwort zurücksetzen',
                'enable_selected' => 'Ausgewählte aktivieren',
                'disable_selected' => 'Ausgewählte deaktivieren',
            ],
            'notifications' => [
                'password_reset' => 'Passwort erfolgreich zurückgesetzt',
                'webdav_url_copied' => 'WebDAV-URL kopiert',
            ],
            'pages' => [
                'list' => [
                    'description' => 'Hier verwaltest du deine WebDAV-Konten. Über WebDAV kannst du ZIP-Archive mit Videos hochladen, die anschließend automatisch als Massenupload verarbeitet werden.',
                ],
                'create' => [
                    'description' => 'Lege hier ein neues WebDAV-Konto an, um ZIP-Archive mit Videos per WebDAV-Client hochzuladen. Die Archive werden automatisch als Massenupload verarbeitet.',
                ],
            ],
        ],
    ],
    'notifications' => [
        'account_created' => [
            'subject' => 'Dein WebDAV-Konto wurde erstellt',
            'greeting' => 'Hallo,',
            'account' => 'WebDAV-Konto: :username',
            'user' => 'Verknüpfter Benutzer: :user',
            'created_at' => 'Erstellt am: :created_at',
            'password' => 'Passwort: :password',
            'security' => 'Bewahre dieses Passwort sicher auf. Falls du dieses Konto nicht erwartet hast, kontaktiere einen Administrator.',
        ],
        'password_reset' => [
            'subject' => 'Dein WebDAV-Passwort wurde zurückgesetzt',
            'greeting' => 'Hallo,',
            'account' => 'Das Passwort für dein WebDAV-Konto ":username" wurde zurückgesetzt.',
            'password' => 'Neues Passwort: :password',
            'security' => 'Bewahre dieses Passwort sicher auf. Falls du dieses Zurücksetzen nicht angefordert hast, kontaktiere einen Administrator.',
        ],
    ],
];
