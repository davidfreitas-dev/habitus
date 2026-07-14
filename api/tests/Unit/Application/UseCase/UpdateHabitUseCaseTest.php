<?php

declare(strict_types=1);

namespace Tests\Unit\Application\UseCase;

use App\Application\DTO\Habit\HabitResponseDTO;
use App\Application\DTO\Habit\UpdateHabitRequestDTO;
use App\Application\Service\ValidationService;
use App\Application\UseCase\UpdateHabitUseCase;
use App\Domain\Entity\Habit;
use App\Domain\Entity\HabitWeekDay;
use App\Domain\Entity\User;
use App\Domain\Exception\HabitAlreadyExistsException;
use App\Domain\Exception\HabitNotFoundException;
use App\Domain\Exception\ValidationException;
use App\Domain\Repository\HabitRepositoryInterface;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Exception;
use PDO;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class UpdateHabitUseCaseTest extends TestCase
{
    private PDO&MockObject $pdo;

    private ValidationService&MockObject $validationService;

    private HabitRepositoryInterface&MockObject $habitRepository;

    private UpdateHabitUseCase $updateHabitUseCase;

    public function testShouldUpdateHabitSuccessfully(): void
    {
        $habitId = 1;
        $userId = 1;
        $dto = new UpdateHabitRequestDTO('Updated Habit', [0, 1], '12:00');

        /** @var User&MockObject $user */
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($userId);

        /** @var Habit&MockObject $existingHabit */
        $existingHabit = $this->createMock(Habit::class);
        $existingHabit->method('getId')->willReturn($habitId);
        $existingHabit->method('getTitle')->willReturn('Original Habit');
        $existingHabit->method('getUser')->willReturn($user);
        $existingHabit->method('getCreatedAt')->willReturn(new DateTimeImmutable());
        $existingHabit->method('getUpdatedAt')->willReturn(new DateTimeImmutable());
        $existingHabit->method('getHabitWeekDays')->willReturn(new ArrayCollection());
        $existingHabit->method('getReminderTime')->willReturn('10:00');


        /** @var Habit&MockObject $updatedHabit */
        $updatedHabit = $this->createMock(Habit::class);
        $updatedHabit->method('getId')->willReturn($habitId);
        $updatedHabit->method('getTitle')->willReturn($dto->title);
        $updatedHabit->method('getUser')->willReturn($user);
        $updatedHabit->method('getCreatedAt')->willReturn(new DateTimeImmutable());
        $updatedHabit->method('getUpdatedAt')->willReturn(new DateTimeImmutable());
        $updatedHabit->method('getHabitWeekDays')->willReturn(new ArrayCollection([
            new HabitWeekDay(habitId: $habitId, weekDay: 0),
            new HabitWeekDay(habitId: $habitId, weekDay: 1),
        ]));
        $updatedHabit->method('getReminderTime')->willReturn($dto->reminderTime);

        $this->validationService->expects($this->once())->method('validate')->with($dto);
        $this->habitRepository->expects($this->once())->method('findById')->with($habitId, $userId)->willReturn($existingHabit);
        $this->habitRepository->expects($this->once())->method('findByTitle')->with($dto->title, $userId)->willReturn(null); // No other habit with this title

        $this->pdo->expects($this->once())->method('beginTransaction');
        $this->habitRepository->expects($this->once())->method('update')->with($existingHabit, $dto->weekDays)->willReturn($updatedHabit);
        $this->pdo->expects($this->once())->method('commit');
        $this->pdo->expects($this->never())->method('rollBack');

        $response = $this->updateHabitUseCase->execute($dto, $habitId, $userId);

        $this->assertInstanceOf(HabitResponseDTO::class, $response);
        $this->assertEquals($habitId, $response->id);
        $this->assertEquals($dto->title, $response->title);
        $this->assertEquals($userId, $response->userId);
        $this->assertCount(2, $response->weekDays);
        $this->assertEquals($dto->reminderTime, $response->reminderTime);
    }

    public function testShouldUpdateHabitWithSameTitleSuccessfully(): void
    {
        $habitId = 1;
        $userId = 1;
        $dto = new UpdateHabitRequestDTO('Original Habit', [0, 1], null);

        /** @var User&MockObject $user */
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($userId);

        /** @var Habit&MockObject $existingHabit */
        $existingHabit = $this->createMock(Habit::class);
        $existingHabit->method('getId')->willReturn($habitId);
        $existingHabit->method('getTitle')->willReturn('Original Habit');
        $existingHabit->method('getUser')->willReturn($user);
        $existingHabit->method('getCreatedAt')->willReturn(new DateTimeImmutable());
        $existingHabit->method('getUpdatedAt')->willReturn(new DateTimeImmutable());
        $existingHabit->method('getHabitWeekDays')->willReturn(new ArrayCollection());
        $existingHabit->method('getReminderTime')->willReturn('10:00');

        /** @var Habit&MockObject $updatedHabit */
        $updatedHabit = $this->createMock(Habit::class);
        $updatedHabit->method('getId')->willReturn($habitId);
        $updatedHabit->method('getTitle')->willReturn($dto->title);
        $updatedHabit->method('getUser')->willReturn($user);
        $updatedHabit->method('getCreatedAt')->willReturn(new DateTimeImmutable());
        $updatedHabit->method('getUpdatedAt')->willReturn(new DateTimeImmutable());
        $updatedHabit->method('getHabitWeekDays')->willReturn(new ArrayCollection([
            new HabitWeekDay(habitId: $habitId, weekDay: 0),
            new HabitWeekDay(habitId: $habitId, weekDay: 1),
        ]));
        $updatedHabit->method('getReminderTime')->willReturn($dto->reminderTime);

        $this->validationService->expects($this->once())->method('validate')->with($dto);
        $this->habitRepository->expects($this->once())->method('findById')->with($habitId, $userId)->willReturn($existingHabit);
        // findByTitle should not be called if title is the same, but current logic calls it and expects it to return the same habit
        $this->habitRepository->expects($this->never())->method('findByTitle');

        $this->pdo->expects($this->once())->method('beginTransaction');
        $this->habitRepository->expects($this->once())->method('update')->with($existingHabit, $dto->weekDays)->willReturn($updatedHabit);
        $this->pdo->expects($this->once())->method('commit');
        $this->pdo->expects($this->never())->method('rollBack');

        $response = $this->updateHabitUseCase->execute($dto, $habitId, $userId);

        $this->assertInstanceOf(HabitResponseDTO::class, $response);
        $this->assertEquals($habitId, $response->id);
        $this->assertEquals($dto->title, $response->title);
        $this->assertEquals($userId, $response->userId);
        $this->assertCount(2, $response->weekDays);
        $this->assertEquals($dto->reminderTime, $response->reminderTime);
    }

    public function testShouldThrowHabitNotFoundExceptionIfHabitNotFound(): void
    {
        $this->expectException(HabitNotFoundException::class);
        $this->expectExceptionMessage('Hábito não encontrado.');

        $habitId = 1;
        $userId = 1;
        $dto = new UpdateHabitRequestDTO('Any Title', [0], null);

        $this->validationService->expects($this->once())->method('validate')->with($dto);
        $this->habitRepository->expects($this->once())->method('findById')->with($habitId, $userId)->willReturn(null);
        $this->habitRepository->expects($this->never())->method('findByTitle');
        $this->pdo->expects($this->never())->method('beginTransaction');

        $this->updateHabitUseCase->execute($dto, $habitId, $userId);
    }

    public function testShouldThrowHabitNotFoundExceptionIfUserDoesNotOwnHabit(): void
    {
        $this->expectException(HabitNotFoundException::class);
        $this->expectExceptionMessage('Hábito não encontrado.');

        $habitId = 1;
        $userId = 1;
        $anotherUserId = 2;
        $dto = new UpdateHabitRequestDTO('Any Title', [0], null);

        /** @var User&MockObject $user */
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($anotherUserId); // Habit owned by another user

        /** @var Habit&MockObject $existingHabit */
        $existingHabit = $this->createMock(Habit::class);
        $existingHabit->method('getUser')->willReturn($user);

        $this->validationService->expects($this->once())->method('validate')->with($dto);
        $this->habitRepository->expects($this->once())->method('findById')->with($habitId, $userId)->willReturn($existingHabit);
        $this->habitRepository->expects($this->never())->method('findByTitle');
        $this->pdo->expects($this->never())->method('beginTransaction');

        $this->updateHabitUseCase->execute($dto, $habitId, $userId);
    }

    public function testShouldThrowHabitAlreadyExistsExceptionIfTitleNotUnique(): void
    {
        $this->expectException(HabitAlreadyExistsException::class);
        $this->expectExceptionMessage('Já existe um hábito com este título.');

        $habitId = 1;
        $userId = 1;
        $dto = new UpdateHabitRequestDTO('Existing Habit', [0], null);

        /** @var User&MockObject $user */
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($userId);

        /** @var Habit&MockObject $existingHabit */
        $existingHabit = $this->createMock(Habit::class);
        $existingHabit->method('getId')->willReturn($habitId);
        $existingHabit->method('getTitle')->willReturn('Original Habit');
        $existingHabit->method('getUser')->willReturn($user);

        /** @var Habit&MockObject $otherHabitWithSameTitle */
        $otherHabitWithSameTitle = $this->createMock(Habit::class);
        $otherHabitWithSameTitle->method('getId')->willReturn(99); // Different ID
        $otherHabitWithSameTitle->method('getTitle')->willReturn('Existing Habit');
        $otherHabitWithSameTitle->method('getUser')->willReturn($user);

        $this->validationService->expects($this->once())->method('validate')->with($dto);
        $this->habitRepository->expects($this->once())->method('findById')->with($habitId, $userId)->willReturn($existingHabit);
        $this->habitRepository->expects($this->once())->method('findByTitle')->with($dto->title, $userId)->willReturn($otherHabitWithSameTitle);
        $this->pdo->expects($this->never())->method('beginTransaction');

        $this->updateHabitUseCase->execute($dto, $habitId, $userId);
    }

    public function testShouldThrowValidationExceptionIfDTOValidationFails(): void
    {
        $this->expectException(ValidationException::class);

        $habitId = 1;
        $userId = 1;
        $dto = new UpdateHabitRequestDTO('', [], null); // Invalid DTO

        $this->validationService->expects($this->once())->method('validate')->with($dto)->willThrowException(new ValidationException('Validation failed.'));
        $this->habitRepository->expects($this->never())->method('findById');
        $this->pdo->expects($this->never())->method('beginTransaction');

        $this->updateHabitUseCase->execute($dto, $habitId, $userId);
    }

    public function testShouldRollbackTransactionOnDatabaseError(): void
    {
        $this->expectException(Exception::class);

        $habitId = 1;
        $userId = 1;
        $dto = new UpdateHabitRequestDTO('Updated Habit', [0], null);

        /** @var User&MockObject $user */
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($userId);

        /** @var Habit&MockObject $existingHabit */
        $existingHabit = $this->createMock(Habit::class);
        $existingHabit->method('getId')->willReturn($habitId);
        $existingHabit->method('getTitle')->willReturn('Original Habit');
        $existingHabit->method('getUser')->willReturn($user);
        $existingHabit->method('getCreatedAt')->willReturn(new DateTimeImmutable());
        $existingHabit->method('getUpdatedAt')->willReturn(new DateTimeImmutable());
        $existingHabit->method('getHabitWeekDays')->willReturn(new ArrayCollection());

        $this->validationService->expects($this->once())->method('validate')->with($dto);
        $this->habitRepository->expects($this->once())->method('findById')->with($habitId, $userId)->willReturn($existingHabit);
        $this->habitRepository->expects($this->once())->method('findByTitle')->with($dto->title, $userId)->willReturn(null);

        $this->pdo->expects($this->once())->method('beginTransaction');
        $this->habitRepository->expects($this->once())->method('update')->willThrowException(new Exception('Database error'));
        $this->pdo->expects($this->once())->method('rollBack');
        $this->pdo->expects($this->never())->method('commit');

        $this->updateHabitUseCase->execute($dto, $habitId, $userId);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = $this->createMock(PDO::class);
        $this->validationService = $this->createMock(ValidationService::class);
        $this->habitRepository = $this->createMock(HabitRepositoryInterface::class);

        $this->updateHabitUseCase = new UpdateHabitUseCase(
            $this->pdo,
            $this->validationService,
            $this->habitRepository,
        );
    }
}
