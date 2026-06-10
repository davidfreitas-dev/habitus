<?php

declare(strict_types=1);

namespace App\Application\DTO\User;

use JsonSerializable;

class UserResponseDTO implements JsonSerializable
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,
        public readonly string $roleName,
        public readonly int $roleId,
        public readonly bool $isActive,
        public readonly bool $isVerified,
        public readonly ?string $phone = null,
        public readonly ?string $cpfcnpj = null,
        public readonly ?string $avatarUrl = null,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role_name' => $this->roleName,
            'role_id' => $this->roleId,
            'is_active' => $this->isActive,
            'is_verified' => $this->isVerified,
            'phone' => $this->phone,
            'cpfcnpj' => $this->cpfcnpj,
            'avatar_url' => $this->avatarUrl,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
