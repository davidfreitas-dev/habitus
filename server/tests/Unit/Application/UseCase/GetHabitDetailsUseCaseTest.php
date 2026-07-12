<?php

declare(strict_types=1);

namespace Tests\Unit\Application\UseCase;

use App\Application\DTO\Habit\HabitResponseDTO;
use App\Application\UseCase\GetHabitDetailsUseCase;
use App\Domain\Entity\Habit;
use App\Domain\Entity\HabitWeekDay;
use App\Domain\Entity\User;
use App\Domain\Exception\HabitNotFoundException;
use App\Domain\Repository\HabitRepositoryInterface;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class GetHabitDetailsUseCaseTest extends TestCase
{
    private HabitRepositoryInterface&MockObject $habitRepository;

    private GetHabitDetailsUseCase $getHabitDetailsUseCase;

    public function testShouldReturnHabitDetailsSuccessfully(): void
    {
        $habitId = 1;
        $userId = 1;

        /** @var User&MockObject $user */
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($userId);

        /** @var Habit&MockObject $habit */
        $habit = $this->createMock(Habit::class);
        $habit->method('getId')->willReturn($habitId);
        $habit->method('getTitle')->willReturn('Test Habit');
        $habit->method('getUser')->willReturn($user);
        $habit->method('getCreatedAt')->willReturn(new DateTimeImmutable());
        $habit->method('getUpdatedAt')->willReturn(new DateTimeImmutable());
        $habit->method('getHabitWeekDays')->willReturn(new ArrayCollection([
            new HabitWeekDay(habitId: $habitId, weekDay: 1),
            new HabitWeekDay(habitId: $habitId, weekDay: 3),
        ]));

        $this->habitRepository->expects($this->once())->method('findById')->with($habitId, $userId)->willReturn($habit);

        $response = $this->getHabitDetailsUseCase->execute($habitId, $userId);

        $this->assertInstanceOf(HabitResponseDTO::class, $response);
        $this->assertEquals($habitId, $response->id);
        $this->assertEquals('Test Habit', $response->title);
        $this->assertEquals($userId, $response->userId);
        $this->assertCount(2, $response->weekDays);
    }

    public function testShouldThrowHabitNotFoundExceptionIfHabitNotFound(): void
    {
        $this->expectException(HabitNotFoundException::class);
        $this->expectExceptionMessage('Hábito não encontrado.');

        $habitId = 1;
        $userId = 1;

        $this->habitRepository->expects($this->once())->method('findById')->with($habitId, $userId)->willReturn(null);

        $this->getHabitDetailsUseCase->execute($habitId, $userId);
    }

    public function testShouldThrowHabitNotFoundExceptionIfUserDoesNotOwnHabit(): void
    {
        $this->expectException(HabitNotFoundException::class);
        $this->expectExceptionMessage('Hábito não encontrado.');

        $habitId = 1;
        $userId = 1;
        $anotherUserId = 2;

        /** @var User&MockObject $user */
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($anotherUserId); // Habit owned by another user

        /** @var Habit&MockObject $habit */
        $habit = $this->createMock(Habit::class);
        $habit->method('getUser')->willReturn($user);

        $this->habitRepository->expects($this->once())->method('findById')->with($habitId, $userId)->willReturn($habit);

        $this->getHabitDetailsUseCase->execute($habitId, $userId);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->habitRepository = $this->createMock(HabitRepositoryInterface::class);

        $this->getHabitDetailsUseCase = new GetHabitDetailsUseCase(
            $this->habitRepository,
        );
    }
}
