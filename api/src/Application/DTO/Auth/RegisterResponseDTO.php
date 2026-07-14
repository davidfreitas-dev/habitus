<?php

declare(strict_types=1);

namespace App\Application\DTO\Auth;

use JsonSerializable;

class RegisterResponseDTO implements JsonSerializable
{
    public function __construct(
        public readonly int $userId,
        public readonly string $userName,
        public readonly string $userEmail,
        public readonly string $userRoleName,
        public readonly string $accessToken,
        public readonly string $refreshToken,
        public readonly string $tokenType,
        public readonly int $expiresIn,
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'user_id' => $this->userId,
            'user_name' => $this->userName,
            'user_email' => $this->userEmail,
            'user_role_name' => $this->userRoleName,
            'access_token' => $this->accessToken,
            'refresh_token' => $this->refreshToken,
            'token_type' => $this->tokenType,
            'expires_in' => $this->expiresIn,
        ];
    }
}
