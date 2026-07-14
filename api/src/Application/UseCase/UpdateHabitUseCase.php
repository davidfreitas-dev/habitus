<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\DTO\Habit\HabitResponseDTO;
use App\Application\DTO\Habit\UpdateHabitRequestDTO;
use App\Application\Service\ValidationService;
use App\Domain\Exception\HabitAlreadyExistsException;
use App\Domain\Exception\HabitNotFoundException;
use App\Domain\Repository\HabitRepositoryInterface;
use Exception;
use PDO;

class UpdateHabitUseCase
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly ValidationService $validationService,
        private readonly HabitRepositoryInterface $habitRepository,
    ) {
    }

    /**
     * @throws HabitNotFoundException
     * @throws HabitAlreadyExistsException
     * @throws Exception
     */
    public function execute(UpdateHabitRequestDTO $dto, int $habitId, int $userId): HabitResponseDTO
    {
        $this->validationService->validate($dto);

        $habit = $this->habitRepository->findById($habitId, $userId);

        if (!$habit || $habit->getUser()->getId() !== $userId) {
            throw new HabitNotFoundException('Hábito não encontrado.');
        }

        if ($habit->getTitle() !== $dto->title) {
            $existingHabitWithTitle = $this->habitRepository->findByTitle($dto->title, $userId);
            if ($existingHabitWithTitle && $existingHabitWithTitle->getId() !== $habitId) {
                throw new HabitAlreadyExistsException('Já existe um hábito com este título.');
            }
        }

        $this->pdo->beginTransaction();

        try {
            $habit->setTitle($dto->title);
            $habit->setReminderTime($dto->reminderTime);

            $updatedHabit = $this->habitRepository->update($habit, $dto->weekDays);

            $this->pdo->commit();
        } catch (Exception $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }

        return HabitResponseDTO::fromEntity($updatedHabit);
    }
}
