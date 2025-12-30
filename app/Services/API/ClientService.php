<?php

declare(strict_types=1);

namespace App\Services\API;

use App\Models\User;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;

readonly class ClientService
{
    public function __construct(private ClientRepository $clientRepository)
    {
    }

    public function createPersonalAccessClientForUser(string $name, User $user): Client
    {
        $client = $this->clientRepository->createPersonalAccessGrantClient(
            $name,
        );

        $client->owner = $user;
        $client->save();
        return $client;
    }
}
