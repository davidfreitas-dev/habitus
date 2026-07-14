<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Exception\HabitNotFoundException;
use App\Domain\Repository\HabitRepositoryInterface;

class DeleteHabitUseCase
{
    public function __construct(
        private readonly HabitRepositoryInterface $habitRepository,
    ) {
    }

    /**
     * @throws HabitNotFoundException
     */
    public function execute(int $habitId, int $userId): void
    {
        $habit = $this->habitRepository->findById($habitId, $userId);

        if (!$habit instanceof \App\Domain\Entity\Habit) {
            throw new HabitNotFoundException('Hábito não encontrado.');
        }

        $this->habitRepository->delete($habitId, $userId);
    }
}
