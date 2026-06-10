<?php

declare(strict_types=1);

namespace App\Application\DTO\User;

use Symfony\Component\Validator\Constraints as Assert;

readonly class ListUsersRequestDTO
{
    public function __construct(
        #[Assert\Type('integer')]
        #[Assert\Range(notInRangeMessage: 'O limite deve estar entre {{ min }} e {{ max }}.', min: 1, max: 100)]
        public int $limit = 20,
        #[Assert\Type('integer')]
        #[Assert\GreaterThanOrEqual(value: 0, message: 'O offset não pode ser negativo.')]
        public int $offset = 0,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            limit: isset($data['limit']) ? (int)$data['limit'] : 20,
            offset: isset($data['offset']) ? (int)$data['offset'] : 0,
        );
    }
}
