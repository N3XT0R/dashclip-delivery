<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Passport Guard
    |--------------------------------------------------------------------------
    |
    | Guard used to sign users in on the OAuth consent pages. API users are the
    | users of the user area, so consent runs through its sign-in; API
    | permissions are checked against the same area.
    |
    */

    'guard' => 'standard',

    'middleware' => [],

    'private_key' => env('PASSPORT_PRIVATE_KEY'),

    'public_key' => env('PASSPORT_PUBLIC_KEY'),

    'connection' => env('PASSPORT_CONNECTION'),

];
