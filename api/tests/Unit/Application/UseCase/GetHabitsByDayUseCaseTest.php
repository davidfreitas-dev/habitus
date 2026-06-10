<?php

declare(strict_types=1);

namespace Tests\Unit\Application\UseCase;

use App\Application\DTO\Habit\HabitsByDayRequestDTO;
use App\Application\DTO\Habit\HabitsByDayResponseDTO;
use App\Application\Service\ValidationService;
use App\Application\UseCase\GetHabitsByDayUseCase;
use App\Domain\Entity\Habit;
use App\Domain\Entity\HabitWeekDay;
use App\Domain\Entity\User;
use App\Domain\Repository\DayRepositoryInterface;
use App\Domain\Repository\HabitRepositoryInterface;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class GetHabitsByDayUseCaseTest extends TestCase
{
    private ValidationService&MockObject $validationService;

    private HabitRepositoryInterface&MockObject $habitRepository;

    private DayRepositoryInterface&MockObject $dayRepository;

    private GetHabitsByDayUseCase $getHabitsByDayUseCase;

    public function testShouldReturnHabitsByDaySuccessfully(): void
    {
        $userId = 1;
        $dateString = '2024-02-07';
        $dto = new HabitsByDayRequestDTO($dateString);
        $date = new DateTimeImmutable($dateString);

        /** @var User&MockObject $user */
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($userId);

        // Mock possible habits
        /** @var Habit&MockObject $possibleHabit1 */
        $possibleHabit1 = $this->createMock(Habit::class);
        $possibleHabit1->method('getId')->willReturn(1);
        $possibleHabit1->method('getTitle')->willReturn('Possible Habit 1');
        $possibleHabit1->method('getUser')->willReturn($user);
        $possibleHabit1->method('getCreatedAt')->willReturn(new DateTimeImmutable());
        $possibleHabit1->method('getUpdatedAt')->willReturn(new DateTimeImmutable());
        $possibleHabit1->method('getHabitWeekDays')->willReturn(new ArrayCollection([
            new HabitWeekDay(habitId: 1, weekDay: (int)$date->format('w')),
        ]));

        /** @var Habit&MockObject $possibleHabit2 */
        $possibleHabit2 = $this->createMock(Habit::class);
        $possibleHabit2->method('getId')->willReturn(2);
        $possibleHabit2->method('getTitle')->willReturn('Possible Habit 2');
        $possibleHabit2->method('getUser')->willReturn($user);
        $possibleHabit2->method('getCreatedAt')->willReturn(new DateTimeImmutable());
        $possibleHabit2->method('getUpdatedAt')->willReturn(new DateTimeImmutable());
        $possibleHabit2->method('getHabitWeekDays')->willReturn(new ArrayCollection([
            new HabitWeekDay(habitId: 2, weekDay: (int)$date->format('w')),
        ]));

        $possibleHabits = [$possibleHabit1, $possibleHabit2];

        // Mock completed habits
        /** @var Habit&MockObject $completedHabit1 */
        $completedHabit1 = $this->createMock(Habit::class);
        $completedHabit1->method('getId')->willReturn(1); // Same ID as a possible habit
        $completedHabit1->method('getTitle')->willReturn('Possible Habit 1');
        $completedHabit1->method('getUser')->willReturn($user);
        $completedHabit1->method('getCreatedAt')->willReturn(new DateTimeImmutable());
        $completedHabit1->method('getUpdatedAt')->willReturn(new DateTimeImmutable());
        $completedHabit1->method('getHabitWeekDays')->willReturn(new ArrayCollection([
            new HabitWeekDay(habitId: 1, weekDay: (int)$date->format('w')),
        ]));

        $completedHabits = [$completedHabit1];

        $this->validationService->expects($this->once())->method('validate')->with($dto);
        $this->habitRepository->expects($this->once())->method('findPossibleHabits')->with($date, $userId)->willReturn($possibleHabits);
        $this->habitRepository->expects($this->once())->method('findCompletedHabits')->with($date, $userId)->willReturn($completedHabits);

        $response = $this->getHabitsByDayUseCase->execute($dto, $userId);

        $this->assertInstanceOf(HabitsByDayResponseDTO::class, $response);
        $this->assertCount(2, $response->possibleHabits);
        $this->assertCount(1, $response->completedHabits);

        $this->assertEquals(1, $response->possibleHabits[0]->id);
        $this->assertEquals('Possible Habit 1', $response->possibleHabits[0]->title);
        $this->assertEquals($userId, $response->possibleHabits[0]->userId);

        $this->assertEquals(2, $response->possibleHabits[1]->id);
        $this->assertEquals('Possible Habit 2', $response->possibleHabits[1]->title);
        $this->assertEquals($userId, $response->possibleHabits[1]->userId);

        $this->assertEquals(1, $response->completedHabits[0]->id);
        $this->assertEquals('Possible Habit 1', $response->completedHabits[0]->title);
        $this->assertEquals($userId, $response->completedHabits[0]->userId);
    }

    public function testShouldReturnEmptyListsIfNoHabitsFound(): void
    {
        $userId = 1;
        $dateString = '2024-02-07';
        $dto = new HabitsByDayRequestDTO($dateString);
        $date = new DateTimeImmutable($dateString);

        $this->validationService->expects($this->once())->method('validate')->with($dto);
        $this->habitRepository->expects($this->once())->method('findPossibleHabits')->with($date, $userId)->willReturn([]);
        $this->habitRepository->expects($this->once())->method('findCompletedHabits')->with($date, $userId)->willReturn([]);

        $response = $this->getHabitsByDayUseCase->execute($dto, $userId);

        $this->assertInstanceOf(HabitsByDayResponseDTO::class, $response);
        $this->assertEmpty($response->possibleHabits);
        $this->assertEmpty($response->completedHabits);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->validationService = $this->createMock(ValidationService::class);
        $this->habitRepository = $this->createMock(HabitRepositoryInterface::class);
        $this->dayRepository = $this->createMock(DayRepositoryInterface::class);

        $this->getHabitsByDayUseCase = new GetHabitsByDayUseCase(
            $this->validationService,
            $this->habitRepository,
            $this->dayRepository,
        );
    }
}
