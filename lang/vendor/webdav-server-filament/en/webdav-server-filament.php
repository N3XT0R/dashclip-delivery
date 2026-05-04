<?php

return [
    'resources' => [
        'accounts' => [
            'labels' => [
                'singular' => 'WebDAV account',
                'plural' => 'WebDAV accounts',
            ],
            'navigation' => [
                'label' => 'WebDAV accounts',
            ],
            'fields' => [
                'username' => 'Username',
                'display_name' => 'Display name',
                'password' => 'Password',
                'password_confirmation' => 'Confirm password',
                'new_password' => 'New password',
                'new_password_confirmation' => 'Confirm new password',
                'user' => 'User',
                'enabled' => 'Enabled',
                'meta' => 'Meta',
                'meta_key' => 'Key',
                'meta_value' => 'Value',
                'created_at' => 'Created',
                'webdav_url' => 'WebDAV URL',
            ],
            'placeholders' => [
                'display_name' => 'Falls back to username when left empty',
            ],
            'empty' => [
                'display_name' => '-',
            ],
            'filters' => [
                'status' => 'Status',
                'active' => 'Active',
                'inactive' => 'Inactive',
            ],
            'actions' => [
                'generate_password' => 'Generate password',
                'reset_password' => 'Reset password',
                'enable_selected' => 'Enable selected',
                'disable_selected' => 'Disable selected',
            ],
            'notifications' => [
                'password_reset' => 'Password reset successfully',
                'webdav_url_copied' => 'WebDAV URL copied',
            ],
        ],
    ],
    'notifications' => [
        'account_created' => [
            'subject' => 'Your WebDAV account was created',
            'greeting' => 'Hello,',
            'account' => 'WebDAV account: :username',
            'user' => 'Linked user: :user',
            'created_at' => 'Created at: :created_at',
            'password' => 'Password: :password',
            'security' => 'Store this password securely. If you did not expect this account, contact an administrator.',
        ],
        'password_reset' => [
            'subject' => 'Your WebDAV password was reset',
            'greeting' => 'Hello,',
            'account' => 'The password for your WebDAV account ":username" was reset.',
            'password' => 'New password: :password',
            'security' => 'Store this password securely. If you did not request this reset, contact an administrator.',
        ],
    ],
];
