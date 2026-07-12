<?php

declare(strict_types=1);

namespace Tests\Unit\Application\UseCase;

use App\Application\UseCase\ToggleHabitUseCase;
use App\Domain\Entity\Day;
use App\Domain\Entity\Habit;
use App\Domain\Entity\User;
use App\Domain\Exception\HabitNotFoundException;
use App\Domain\Repository\DayHabitRepositoryInterface;
use App\Domain\Repository\DayRepositoryInterface;
use App\Domain\Repository\HabitRepositoryInterface;
use App\Infrastructure\Persistence\Decorator\CachingHabitRepository;
use DateTimeImmutable;
use Exception;
use PDO;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class ToggleHabitUseCaseTest extends TestCase
{
    private PDO&MockObject $pdo;

    private HabitRepositoryInterface&MockObject $habitRepository;

    private DayRepositoryInterface&MockObject $dayRepository;

    private DayHabitRepositoryInterface&MockObject $dayHabitRepository;

    private ToggleHabitUseCase $toggleHabitUseCase;

    public function testShouldToggleHabitSuccessfullyWhenDayExists(): void
    {
        $habitId = 1;
        $userId = 1;
        $date = new DateTimeImmutable('2024-02-08');
        $dayId = 10;

        /** @var User&MockObject $user */
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($userId);

        /** @var Habit&MockObject $habit */
        $habit = $this->createMock(Habit::class);
        $habit->method('getId')->willReturn($habitId);
        $habit->method('getUser')->willReturn($user);

        /** @var Day&MockObject $day */
        $day = $this->createMock(Day::class);
        $day->method('getId')->willReturn($dayId);

        $this->habitRepository->expects($this->once())->method('findById')->with($habitId, $userId)->willReturn($habit);
        $this->pdo->expects($this->once())->method('beginTransaction');
        $this->dayRepository->expects($this->once())->method('findOneByDate')->with($date)->willReturn($day);
        $this->dayRepository->expects($this->never())->method('create');
        $this->dayHabitRepository->expects($this->once())->method('toggle')->with($dayId, $habitId, $userId)->willReturn(true);
        $this->pdo->expects($this->once())->method('commit');
        $this->pdo->expects($this->never())->method('rollBack');

        $result = $this->toggleHabitUseCase->execute($habitId, $userId, $date);

        $this->assertTrue($result);
    }

    public function testShouldToggleHabitSuccessfullyWhenDayDoesNotExist(): void
    {
        $habitId = 1;
        $userId = 1;
        $date = new DateTimeImmutable('2024-02-08');
        $newDayId = 11;

        /** @var User&MockObject $user */
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($userId);

        /** @var Habit&MockObject $habit */
        $habit = $this->createMock(Habit::class);
        $habit->method('getId')->willReturn($habitId);
        $habit->method('getUser')->willReturn($user);

        /** @var Day&MockObject $newDay */
        $newDay = $this->createMock(Day::class);
        $newDay->method('getId')->willReturn($newDayId);

        $this->habitRepository->expects($this->once())->method('findById')->with($habitId, $userId)->willReturn($habit);
        $this->pdo->expects($this->once())->method('beginTransaction');
        $this->dayRepository->expects($this->once())->method('findOneByDate')->with($date)->willReturn(null);
        $this->dayRepository->expects($this->once())->method('create')->with($date)->willReturn($newDay);
        $this->dayHabitRepository->expects($this->once())->method('toggle')->with($newDayId, $habitId, $userId)->willReturn(false);
        $this->pdo->expects($this->once())->method('commit');
        $this->pdo->expects($this->never())->method('rollBack');

        $result = $this->toggleHabitUseCase->execute($habitId, $userId, $date);

        $this->assertFalse($result);
    }

    public function testShouldThrowHabitNotFoundExceptionIfHabitNotFound(): void
    {
        $this->expectException(HabitNotFoundException::class);
        $this->expectExceptionMessage('Hábito não encontrado ou não pertence ao usuário.');

        $habitId = 1;
        $userId = 1;
        $date = new DateTimeImmutable('2024-02-08');

        $this->habitRepository->expects($this->once())->method('findById')->with($habitId, $userId)->willReturn(null);
        $this->pdo->expects($this->never())->method('beginTransaction');
        $this->dayRepository->expects($this->never())->method('findOneByDate');
        $this->dayRepository->expects($this->never())->method('create');
        $this->dayHabitRepository->expects($this->never())->method('toggle');

        $this->toggleHabitUseCase->execute($habitId, $userId, $date);
    }

    public function testShouldThrowHabitNotFoundExceptionIfUserDoesNotOwnHabit(): void
    {
        $this->expectException(HabitNotFoundException::class);
        $this->expectExceptionMessage('Hábito não encontrado ou não pertence ao usuário.');

        $habitId = 1;
        $userId = 1;
        $anotherUserId = 2;
        $date = new DateTimeImmutable('2024-02-08');

        /** @var User&MockObject $user */
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($anotherUserId); // Habit owned by another user

        /** @var Habit&MockObject $habit */
        $habit = $this->createMock(Habit::class);
        $habit->method('getId')->willReturn($habitId);
        $habit->method('getUser')->willReturn($user);

        $this->habitRepository->expects($this->once())->method('findById')->with($habitId, $userId)->willReturn($habit);
        $this->pdo->expects($this->never())->method('beginTransaction');
        $this->dayRepository->expects($this->never())->method('findOneByDate');
        $this->dayRepository->expects($this->never())->method('create');
        $this->dayHabitRepository->expects($this->never())->method('toggle');

        $this->toggleHabitUseCase->execute($habitId, $userId, $date);
    }

    public function testShouldRollbackTransactionOnDatabaseError(): void
    {
        $this->expectException(Exception::class);

        $habitId = 1;
        $userId = 1;
        $date = new DateTimeImmutable('2024-02-08');

        /** @var User&MockObject $user */
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($userId);

        /** @var Habit&MockObject $habit */
        $habit = $this->createMock(Habit::class);
        $habit->method('getId')->willReturn($habitId);
        $habit->method('getUser')->willReturn($user);

        /** @var Day&MockObject $day */
        $day = $this->createMock(Day::class);
        $day->method('getId')->willReturn(10);

        $this->habitRepository->expects($this->once())->method('findById')->with($habitId, $userId)->willReturn($habit);
        $this->pdo->expects($this->once())->method('beginTransaction');
        $this->dayRepository->expects($this->once())->method('findOneByDate')->with($date)->willReturn($day);
        $this->dayHabitRepository->expects($this->once())->method('toggle')->willThrowException(new Exception('Database error'));
        $this->pdo->expects($this->once())->method('rollBack');
        $this->pdo->expects($this->never())->method('commit');

        $this->toggleHabitUseCase->execute($habitId, $userId, $date);
    }

    public function testShouldInvalidateCacheWhenHabitIsToggledWithCachingRepository(): void
    {
        $habitId = 1;
        $userId = 1;
        $date = new DateTimeImmutable('2024-02-08');
        $dayId = 10;

        /** @var User&MockObject $user */
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($userId);

        /** @var Habit&MockObject $habit */
        $habit = $this->createMock(Habit::class);
        $habit->method('getId')->willReturn($habitId);
        $habit->method('getUser')->willReturn($user);

        /** @var Day&MockObject $day */
        $day = $this->createMock(Day::class);
        $day->method('getId')->willReturn($dayId);

        $cachingHabitRepository = $this->createMock(CachingHabitRepository::class);
        $cachingHabitRepository->expects($this->once())->method('findById')->with($habitId, $userId)->willReturn($habit);
        $cachingHabitRepository->expects($this->once())->method('invalidateUserHabitsCache')->with($userId);

        $useCase = new ToggleHabitUseCase(
            $this->pdo,
            $cachingHabitRepository,
            $this->dayRepository,
            $this->dayHabitRepository
        );

        $this->pdo->expects($this->once())->method('beginTransaction');
        $this->dayRepository->expects($this->once())->method('findOneByDate')->with($date)->willReturn($day);
        $this->dayRepository->expects($this->never())->method('create');
        $this->dayHabitRepository->expects($this->once())->method('toggle')->with($dayId, $habitId, $userId)->willReturn(true);
        $this->pdo->expects($this->once())->method('commit');
        $this->pdo->expects($this->never())->method('rollBack');

        $result = $useCase->execute($habitId, $userId, $date);

        $this->assertTrue($result);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdo = $this->createMock(PDO::class);
        $this->habitRepository = $this->createMock(HabitRepositoryInterface::class);
        $this->dayRepository = $this->createMock(DayRepositoryInterface::class);
        $this->dayHabitRepository = $this->createMock(DayHabitRepositoryInterface::class);

        $this->toggleHabitUseCase = new ToggleHabitUseCase(
            $this->pdo,
            $this->habitRepository,
            $this->dayRepository,
            $this->dayHabitRepository,
        );
    }
}