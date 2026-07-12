<?php

declare(strict_types=1);

namespace App\Application\DTO\Auth;

use JsonSerializable;

class LoginResponseDTO implements JsonSerializable
{
    public function __construct(
        public readonly string $accessToken,
        public readonly string $refreshToken,
        public readonly string $tokenType,
        public readonly int $expiresIn,
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'access_token' => $this->accessToken,
            'refresh_token' => $this->refreshToken,
            'token_type' => $this->tokenType,
            'expires_in' => $this->expiresIn,
        ];
    }
}
