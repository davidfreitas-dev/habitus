<?php

declare(strict_types=1);

namespace Tests\Unit\Application\UseCase;

use App\Application\DTO\Habit\CreateHabitRequestDTO;
use App\Application\DTO\Habit\HabitResponseDTO;
use App\Application\Service\ValidationService;
use App\Application\UseCase\CreateHabitUseCase;
use App\Domain\Entity\Habit;
use App\Domain\Entity\User;
use App\Domain\Exception\HabitAlreadyExistsException;
use App\Domain\Exception\NotFoundException;
use App\Domain\Exception\ValidationException;
use App\Domain\Repository\HabitRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\Entity\HabitWeekDay;
use Doctrine\Common\Collections\ArrayCollection;
use Exception;
use PDO;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class CreateHabitUseCaseTest extends TestCase
{
    private PDO&MockObject $pdo;

    private ValidationService&MockObject $validationService;

    private HabitRepositoryInterface&MockObject $habitRepository;

    private UserRepositoryInterface&MockObject $userRepository;

    private CreateHabitUseCase $createHabitUseCase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = $this->createMock(PDO::class);
        $this->validationService = $this->createMock(ValidationService::class);
        $this->habitRepository = $this->createMock(HabitRepositoryInterface::class);
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);

        $this->createHabitUseCase = new CreateHabitUseCase(
            $this->pdo,
            $this->validationService,
            $this->habitRepository,
            $this->userRepository
        );
    }

    public function testShouldCreateHabitSuccessfully(): void
    {
        $userId = 1;
        $dto = new CreateHabitRequestDTO(
            'Read a book',
            [0, 1, 2, 3, 4, 5, 6],
            '10:30',
            new \DateTimeImmutable()->format('Y-m-d H:i:s')
        );

        /** @var User&MockObject $user */
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($userId);

        /** @var Habit&MockObject $habit */
        $habit = $this->createMock(Habit::class);
        $habit->method('getTitle')->willReturn($dto->title);
        $habit->method('getUser')->willReturn($user);
        $habit->method('getReminderTime')->willReturn($dto->reminderTime);

        $mockHabitWeekDays = [];
        foreach ($dto->weekDays as $weekDay) {
            /** @var HabitWeekDay&MockObject $habitWeekDayMock */
            $habitWeekDayMock = $this->createMock(HabitWeekDay::class);
            $habitWeekDayMock->method('getWeekDay')->willReturn($weekDay);
            $mockHabitWeekDays[] = $habitWeekDayMock;
        }

        $habit->method('getHabitWeekDays')->willReturn(new ArrayCollection($mockHabitWeekDays));

        /** @var Habit&MockObject $createdHabit */
        $createdHabit = $this->createMock(Habit::class);
        $createdHabit->method('getId')->willReturn(1);
        $createdHabit->method('getTitle')->willReturn($dto->title);
        $createdHabit->method('getUser')->willReturn($user);
        $createdHabit->method('getHabitWeekDays')->willReturn(new ArrayCollection($mockHabitWeekDays));
        $createdHabit->method('getReminderTime')->willReturn($dto->reminderTime);

        $this->validationService->expects($this->once())->method('validate')->with($dto);
        $this->userRepository->expects($this->once())->method('findById')->with($userId)->willReturn($user);
        $this->habitRepository->expects($this->once())->method('findByTitle')->with($dto->title, $userId)->willReturn(null);

        $this->pdo->expects($this->once())->method('beginTransaction');
        $this->habitRepository->expects($this->once())->method('create')->willReturn($createdHabit);
        $this->pdo->expects($this->once())->method('commit');
        $this->pdo->expects($this->never())->method('rollBack');

        $response = $this->createHabitUseCase->execute($dto, $userId);

        $this->assertInstanceOf(HabitResponseDTO::class, $response);

        $this->assertNotNull($response->id);
        $this->assertIsInt($response->id);
        $this->assertSame(1, $response->id);
        $this->assertEquals($createdHabit->getTitle(), $response->title);
        $this->assertEquals($createdHabit->getUser()->getId(), $response->userId);
        $this->assertEquals($createdHabit->getReminderTime(), $response->reminderTime);
    }

    public function testShouldThrowNotFoundExceptionIfUserNotFound(): void
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Usuário não encontrado.');

        $userId = 1;
        $dto = new CreateHabitRequestDTO(
            'Read a book',
            [0],
            null,
            new \DateTimeImmutable()->format('Y-m-d H:i:s')
        );

        $this->validationService->expects($this->once())->method('validate')->with($dto);
        $this->userRepository->expects($this->once())->method('findById')->with($userId)->willReturn(null);
        $this->habitRepository->expects($this->never())->method('findByTitle');
        $this->pdo->expects($this->never())->method('beginTransaction');

        $this->createHabitUseCase->execute($dto, $userId);
    }

    public function testShouldThrowHabitAlreadyExistsExceptionIfTitleNotUnique(): void
    {
        $this->expectException(HabitAlreadyExistsException::class);
        $this->expectExceptionMessage('Já existe um hábito com este título.');

        $userId = 1;
        $dto = new CreateHabitRequestDTO(
            'Read a book',
            [0],
            null,
            new \DateTimeImmutable()->format('Y-m-d H:i:s')
        );

        /** @var User&MockObject $user */
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($userId);

        /** @var Habit&MockObject $existingHabit */
        $existingHabit = $this->createMock(Habit::class);

        $this->validationService->expects($this->once())->method('validate')->with($dto);
        $this->userRepository->expects($this->once())->method('findById')->with($userId)->willReturn($user);
        $this->habitRepository->expects($this->once())->method('findByTitle')->with($dto->title, $userId)->willReturn($existingHabit);
        $this->pdo->expects($this->never())->method('beginTransaction');

        $this->createHabitUseCase->execute($dto, $userId);
    }

    public function testShouldThrowValidationExceptionIfDTOValidationFails(): void
    {
        $this->expectException(ValidationException::class);

        $userId = 1;
        $dto = new CreateHabitRequestDTO('', [], null, ''); // Invalid DTO

        $this->validationService->expects($this->once())->method('validate')->with($dto)->willThrowException(new ValidationException('Validation failed.'));
        $this->userRepository->expects($this->never())->method('findById');
        $this->habitRepository->expects($this->never())->method('findByTitle');
        $this->pdo->expects($this->never())->method('beginTransaction');

        $this->createHabitUseCase->execute($dto, $userId);
    }

    public function testShouldRollbackTransactionOnDatabaseError(): void
    {
        $this->expectException(Exception::class);

        $userId = 1;
        $dto = new CreateHabitRequestDTO(
            'Read a book',
            [0],
            null,
            new \DateTimeImmutable()->format('Y-m-d H:i:s')
        );

        /** @var User&MockObject $user */
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($userId);

        $this->validationService->expects($this->once())->method('validate')->with($dto);
        $this->userRepository->expects($this->once())->method('findById')->with($userId)->willReturn($user);
        $this->habitRepository->expects($this->once())->method('findByTitle')->with($dto->title, $userId)->willReturn(null);

        $this->pdo->expects($this->once())->method('beginTransaction');
        $this->habitRepository->expects($this->once())->method('create')->willThrowException(new Exception('Database error'));
        $this->pdo->expects($this->once())->method('rollBack');
        $this->pdo->expects($this->never())->method('commit');

        $this->createHabitUseCase->execute($dto, $userId);
    }
}
