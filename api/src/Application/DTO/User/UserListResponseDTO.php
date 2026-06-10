<?php

declare(strict_types=1);

namespace App\Application\DTO\User;

use JsonSerializable;

class UserListResponseDTO implements JsonSerializable
{
    /**
     * @param UserResponseDTO[] $users
     */
    public function __construct(
        public readonly array $users,
        public readonly int $total,
        public readonly int $limit,
        public readonly int $offset,
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'users' => \array_map(static fn (UserResponseDTO $dto): array => $dto->jsonSerialize(), $this->users),
            'total' => $this->total,
            'limit' => $this->limit,
            'offset' => $this->offset,
        ];
    }
}
