<?php

declare(strict_types=1);

namespace App\Application\DTO\Auth;

use JsonSerializable;

class VerifyEmailResponseDTO implements JsonSerializable
{
    public function __construct(
        private readonly array $tokenData,
        private readonly bool $wasAlreadyVerified,
    ) {
    }

    public function getTokenData(): array
    {
        return $this->tokenData;
    }

    public function wasAlreadyVerified(): bool
    {
        return $this->wasAlreadyVerified;
    }

    public function jsonSerialize(): array
    {
        return [
            'token_data' => $this->getTokenData(),
            'was_already_verified' => $this->wasAlreadyVerified(),
        ];
    }
}
