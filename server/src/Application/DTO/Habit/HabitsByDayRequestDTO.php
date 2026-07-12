<?php

declare(strict_types=1);

namespace App\Application\DTO\Habit;

use Symfony\Component\Validator\Constraints as Assert;

class HabitsByDayRequestDTO
{
    public function __construct(
        #[Assert\NotBlank(message: 'A data é obrigatória.')]
        #[Assert\DateTime(format: 'Y-m-d', message: 'A data deve estar no formato YYYY-MM-DD.')]
        public readonly string $date,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            date: $data['date'] ?? '',
        );
    }

    public function toArray(): array
    {
        return [
            'date' => $this->date,
        ];
    }
}
