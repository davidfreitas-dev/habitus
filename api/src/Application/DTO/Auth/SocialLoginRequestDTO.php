<?php

declare(strict_types=1);

namespace App\Application\DTO\Auth;

class SocialLoginRequestDTO
{
    public function __construct(
        public readonly string $provider,
        public readonly string $idToken,
    ) {
    }
}
