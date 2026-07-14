<?php

declare(strict_types=1);

namespace App\Application\DTO\Habit;

use Symfony\Component\Validator\Constraints as Assert;

readonly class HabitStatsRequestDTO
{
    public function __construct(
        #[Assert\NotBlank(message: 'O período é obrigatório.')]
        #[Assert\Choice(
            choices: ['W', 'M', '3M', '6M', 'Y'],
            message: 'Período inválido. Escolha entre: W, M, 3M, 6M, Y.',
        )]
        public string $period = 'W',
        #[Assert\Date(message: 'A data deve estar no formato YYYY-MM-DD.')]
        public ?string $date = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            period: $data['period'] ?? 'W',
            date: $data['date'] ?? null,
        );
    }
}
