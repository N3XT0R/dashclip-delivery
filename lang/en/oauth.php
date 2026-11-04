<?php

declare(strict_types=1);

return [
    'consent' => [
        'title' => 'Allow access',
        'headline' => 'Allow access to your account?',
        'intro' => '“:client” would like to access your account on your behalf.',
        'signed_in_as' => 'Signed in as :name',
        'permissions' => 'The application will be allowed to:',
        'no_permissions' => 'The application does not ask for any particular permissions.',
        'notice' => 'Only allow access if you trust the application.',
        'approve' => 'Allow',
        'deny' => 'Deny',
    ],
    'device' => [
        'title' => 'Connect a device',
        'headline' => 'Connect a device',
        'intro' => 'Enter the code shown by your device or application.',
        'code_label' => 'Device code',
        'code_placeholder' => 'ABCD-EFGH',
        'continue' => 'Continue',
        'invalid_code' => 'This code is invalid or has expired. Please check it and try again.',
        'approved' => 'The device is connected. You can return to the application.',
        'denied' => 'You denied access. The device was not connected.',
        'consent_hint' => 'You are connecting a device to your account. Check that the code matches the one on the device.',
    ],
];
