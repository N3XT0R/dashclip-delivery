<?php

declare(strict_types=1);

use N3XT0R\LaravelWebdavServer\Models\WebDavAccountModel;

return [
    'route_prefix' => 'webdav',
    'base_uri' => '/webdav/',
    'browser_listing' => false,
    'logging' => [
        'driver' => null,
        'level' => 'info',
    ],

    'storage' => [
        'default_space' => 'default',
        'spaces' => [
            'default' => [
                'disk' => 'local',
                'root' => 'webdav',
                'prefix' => '/',
            ],
        ],
    ],

    'auth' => [
        'account_model' => WebDavAccountModel::class,
        'user_model' => null,

        'username_column' => 'username',
        'password_column' => 'password_encrypted',
        'enabled_column' => 'enabled',

        'user_id_column' => 'user_id',
        'display_name_column' => 'display_name',
    ],
];
