<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\DTO\Habit\HabitResponseDTO;
use App\Domain\Exception\HabitNotFoundException;
use App\Domain\Repository\HabitRepositoryInterface;

class GetHabitDetailsUseCase
{
    public function __construct(
        private readonly HabitRepositoryInterface $habitRepository,
    ) {
    }

    /**
     * @throws HabitNotFoundException
     */
    public function execute(int $habitId, int $userId): HabitResponseDTO
    {
        $habit = $this->habitRepository->findById($habitId, $userId);

        if (!$habit || $habit->getUser()->getId() !== $userId) {
            throw new HabitNotFoundException('Hábito não encontrado.');
        }

        return HabitResponseDTO::fromEntity($habit);
    }
}
