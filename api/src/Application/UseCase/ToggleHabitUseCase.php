<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Exception\HabitNotFoundException;
use App\Domain\Exception\NotFoundException;
use App\Domain\Repository\DayHabitRepositoryInterface;
use App\Domain\Repository\DayRepositoryInterface;
use App\Domain\Repository\HabitRepositoryInterface;
use App\Infrastructure\Persistence\Decorator\CachingHabitRepository;
use DateTimeImmutable;
use Exception;
use PDO;

class ToggleHabitUseCase
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly HabitRepositoryInterface $habitRepository,
        private readonly DayRepositoryInterface $dayRepository,
        private readonly DayHabitRepositoryInterface $dayHabitRepository,
    ) {
    }

    /**
     * @throws HabitNotFoundException
     * @throws NotFoundException
     * @throws Exception
     */
    public function execute(int $habitId, int $userId, DateTimeImmutable $date): bool
    {
        $habit = $this->habitRepository->findById($habitId, $userId);

        if (!$habit || $habit->getUser()->getId() !== $userId) {
            throw new HabitNotFoundException('Hábito não encontrado ou não pertence ao usuário.');
        }

        $this->pdo->beginTransaction();

        try {
            $day = $this->dayRepository->findOneByDate($date);

            if (!$day instanceof \App\Domain\Entity\Day) {
                $day = $this->dayRepository->create($date);
            }

            $isCompleted = $this->dayHabitRepository->toggle($day->getId(), $habit->getId(), $userId);

            $this->pdo->commit();

            if ($this->habitRepository instanceof CachingHabitRepository) {
                $this->habitRepository->invalidateUserHabitsCache($userId);
            }

            return $isCompleted;
        } catch (Exception $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }
}
