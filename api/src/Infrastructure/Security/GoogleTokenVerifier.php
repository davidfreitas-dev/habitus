<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Application\Exception\InvalidSocialTokenException;
use Google_Client;

class GoogleTokenVerifier
{
    public function __construct(private readonly string $webClientId)
    {
    }

    public function verify(string $idToken): array
    {
        $client = new Google_Client(['client_id' => $this->webClientId]);
        $payload = $client->verifyIdToken($idToken);

        if (!$payload) {
            throw new InvalidSocialTokenException('Falha ao verificar o token do Google.');
        }

        return [
            'providerId' => $payload['sub'],
            'email' => $payload['email'],
            'name' => $payload['name'] ?? null,
        ];
    }
}
