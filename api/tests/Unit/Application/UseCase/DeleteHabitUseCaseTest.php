<?php

declare(strict_types=1);

namespace Tests\Unit\Application\UseCase;

use App\Application\UseCase\DeleteHabitUseCase;
use App\Domain\Entity\Habit;
use App\Domain\Entity\User;
use App\Domain\Exception\HabitNotFoundException;
use App\Domain\Repository\HabitRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class DeleteHabitUseCaseTest extends TestCase
{
    private HabitRepositoryInterface&MockObject $habitRepository;

    private DeleteHabitUseCase $deleteHabitUseCase;

    public function testShouldDeleteHabitSuccessfully(): void
    {
        $habitId = 1;
        $userId = 1;

        /** @var User&MockObject $user */
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($userId);

        /** @var Habit&MockObject $habit */
        $habit = $this->createMock(Habit::class);
        $habit->method('getId')->willReturn($habitId);
        $habit->method('getUser')->willReturn($user);

        $this->habitRepository->expects($this->once())->method('findById')->with($habitId, $userId)->willReturn($habit);
        $this->habitRepository->expects($this->once())->method('delete')->with($habitId, $userId)->willReturn(true);

        $this->deleteHabitUseCase->execute($habitId, $userId);
    }

    public function testShouldThrowHabitNotFoundExceptionIfHabitNotFound(): void
    {
        $this->expectException(HabitNotFoundException::class);
        $this->expectExceptionMessage('Hábito não encontrado.');

        $habitId = 1;
        $userId = 1;

        $this->habitRepository->expects($this->once())->method('findById')->with($habitId, $userId)->willReturn(null);
        $this->habitRepository->expects($this->never())->method('delete');

        $this->deleteHabitUseCase->execute($habitId, $userId);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->habitRepository = $this->createMock(HabitRepositoryInterface::class);

        $this->deleteHabitUseCase = new DeleteHabitUseCase(
            $this->habitRepository,
        );
    }
}
