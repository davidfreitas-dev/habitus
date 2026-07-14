<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\DTO\Habit\CreateHabitRequestDTO;
use App\Application\DTO\Habit\HabitResponseDTO;
use App\Application\Service\ValidationService;
use App\Domain\Entity\Habit;
use App\Domain\Exception\HabitAlreadyExistsException;
use App\Domain\Exception\NotFoundException;
use App\Domain\Exception\ValidationException;
use App\Domain\Repository\HabitRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;
use DateTimeImmutable;
use Exception;
use PDO;

class CreateHabitUseCase
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly ValidationService $validationService,
        private readonly HabitRepositoryInterface $habitRepository,
        private readonly UserRepositoryInterface $userRepository,
    ) {
    }

    /**
     * @throws HabitAlreadyExistsException
     * @throws NotFoundException
     * @throws ValidationException
     * @throws Exception
     */
    public function execute(CreateHabitRequestDTO $dto, int $userId): HabitResponseDTO
    {
        $this->validationService->validate($dto);

        $user = $this->userRepository->findById($userId);
        if (!$user instanceof \App\Domain\Entity\User) {
            throw new NotFoundException('Usuário não encontrado.');
        }

        if ($this->habitRepository->findByTitle($dto->title, $user->getId()) instanceof \App\Domain\Entity\Habit) {
            throw new HabitAlreadyExistsException('Já existe um hábito com este título.');
        }

        $this->pdo->beginTransaction();

        try {
            $habit = new Habit(
                title: $dto->title,
                user: $user,
                reminderTime: $dto->reminderTime,
                createdAt: new DateTimeImmutable($dto->createdAt),
            );

            $createdHabit = $this->habitRepository->create($habit, $dto->weekDays);

            $this->pdo->commit();
        } catch (Exception $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }

        return HabitResponseDTO::fromEntity($createdHabit);
    }
}
