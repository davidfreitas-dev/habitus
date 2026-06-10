<?php

declare(strict_types=1);

namespace App\Application\DTO\Auth;

use JsonSerializable;

class PasswordResetResponseDTO implements JsonSerializable
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $userId,
        public readonly string $code,
        public readonly string $expiresAt,
        public readonly ?string $usedAt,
        public readonly string $ipAddress,
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'code' => $this->code,
            'expires_at' => $this->expiresAt,
            'used_at' => $this->usedAt,
            'ip_address' => $this->ipAddress,
        ];
    }
}
