<?php

use Filament\Support\Icons\Heroicon;

return [

    /*
    |--------------------------------------------------------------------------
    | Navigation Groups
    |--------------------------------------------------------------------------
    |
    | This values controls the navigation group name used by Filament
    | for all Passport-related resources.
    |
    */

    'navigation' => [
        'client_resource' => [
            'group' => 'filament-passport-ui::passport-ui.navigation.group',
            'icon' => Heroicon::OutlinedKey,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Self-Service Scope Allow-List
    |--------------------------------------------------------------------------
    |
    | Which OAuth scopes a Standard-panel user may put on their own client.
    | RoleBoundScopeResolver derives this from the API routes themselves: every
    | /api/v1 route declares the scope it needs and the Standard-guard
    | permission it needs, so the allow-list follows the user's role and can
    | never drift from what the endpoints enforce.
    |
    */

    'self_service_scope_resolver' => \App\Services\OAuth\RoleBoundScopeResolver::class,
];
