<?php

declare(strict_types=1);

namespace App\Application\DTO\Common;

use JsonSerializable;

class PersonResponseDTO implements JsonSerializable
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,
        public readonly ?string $phone,
        public readonly ?string $cpfcnpj,
        public readonly ?string $avatarUrl,
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'cpfcnpj' => $this->cpfcnpj,
            'avatar_url' => $this->avatarUrl,
        ];
    }
}
