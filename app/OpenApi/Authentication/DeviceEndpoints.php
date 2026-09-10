<?php

declare(strict_types=1);

namespace App\OpenApi\Authentication;

use OpenApi\Attributes as OA;

/**
 * Documentation for the OAuth2 device-authorization-grant endpoints.
 */
final class DeviceEndpoints
{
    #[OA\Post(
        path: '/oauth/device/code',
        description: 'Starts the device grant. A device with no browser posts `client_id` and '
            . '`scope`; it gets back a `device_code`, a `user_code`, and a `verification_uri`. '
            . 'It then polls `/oauth/token` with '
            . '`grant_type=urn:ietf:params:oauth:grant-type:device_code` and the `device_code` '
            . 'until the user has approved.',
        summary: 'Request a device code',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/x-www-form-urlencoded',
                schema: new OA\Schema(
                    required: ['client_id'],
                    properties: [
                        new OA\Property(property: 'client_id', type: 'string'),
                        new OA\Property(property: 'scope', description: 'Space-separated scopes.', type: 'string'),
                    ],
                    type: 'object',
                ),
            ),
        ),
        tags: ['OAuth2'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Device authorization issued',
                content: new OA\JsonContent(ref: '#/components/schemas/DeviceCode'),
            ),
            new OA\Response(
                response: 400,
                description: 'Invalid client or scope',
                content: new OA\JsonContent(ref: '#/components/schemas/OAuthError'),
            ),
        ],
    )]
    public function code(): void
    {
    }

    #[OA\Get(
        path: '/oauth/device',
        description: 'The page where a user on another device enters the `user_code` shown by '
            . 'the device. Redirects to the consent screen. Requires an authenticated web '
            . 'session.',
        summary: 'Enter the user code',
        tags: ['OAuth2'],
        parameters: [
            new OA\Parameter(name: 'user_code', in: 'query', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'User-code entry / consent screen (HTML)'),
            new OA\Response(response: 401, description: 'No authenticated web session'),
        ],
    )]
    public function userCode(): void
    {
    }

    #[OA\Get(
        path: '/oauth/device/authorize',
        description: 'Consent screen for a validated device `user_code`. Requires an '
            . 'authenticated web session.',
        summary: 'Device consent screen',
        tags: ['OAuth2'],
        responses: [
            new OA\Response(response: 200, description: 'Consent screen (HTML)'),
            new OA\Response(response: 401, description: 'No authenticated web session'),
        ],
    )]
    public function authorize(): void
    {
    }

    #[OA\Post(
        path: '/oauth/device/authorize',
        description: 'Approves the device authorization for the current web session. The polling '
            . 'device then receives a token from `/oauth/token`. CSRF-protected.',
        summary: 'Approve the device authorization',
        tags: ['OAuth2'],
        responses: [
            new OA\Response(response: 200, description: 'Device approved'),
        ],
    )]
    public function approve(): void
    {
    }

    #[OA\Delete(
        path: '/oauth/device/authorize',
        description: 'Denies the device authorization. The polling device receives an '
            . '`access_denied` error from `/oauth/token`. CSRF-protected.',
        summary: 'Deny the device authorization',
        tags: ['OAuth2'],
        responses: [
            new OA\Response(response: 200, description: 'Device denied'),
        ],
    )]
    public function deny(): void
    {
    }
}
