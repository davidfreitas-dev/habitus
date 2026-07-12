<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\DTO\Habit\HabitResponseDTO;
use App\Domain\Repository\HabitRepositoryInterface;

class GetAllHabitsUseCase
{
    public function __construct(
        private readonly HabitRepositoryInterface $habitRepository,
    ) {
    }

    /**
     * @return HabitResponseDTO[]
     */
    public function execute(int $userId): array
    {
        $habits = $this->habitRepository->findAllByUserId($userId);

        return array_map(
            HabitResponseDTO::fromEntity(...),
            $habits,
        );
    }
}
