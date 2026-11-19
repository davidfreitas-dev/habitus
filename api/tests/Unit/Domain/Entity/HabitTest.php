<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entity;

use App\Domain\Entity\Habit;
use App\Domain\Entity\HabitReminder;
use App\Domain\Repository\UserRepositoryInterface;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Support\EntityTestHelper;

class HabitTest extends TestCase
{
    use EntityTestHelper;

    public function testCreatesHabitWithCorrectPropertiesAccessibleViaGetters(): void
    {
        $userId = 1;
        $habitId = 1;
        $user = $this->createUser(['id' => $userId]);
        $habit = $this->createHabit(['user' => $user, 'id' => $habitId]);

        $this->assertInstanceOf(Habit::class, $habit);
        $this->assertSame($habitId, $habit->getId());
        $this->assertSame($user, $habit->getUser());
        $this->assertSame(self::DEFAULT_HABIT_TITLE, $habit->getTitle());
        $this->assertCount(0, $habit->getHabitReminders());
        $this->assertInstanceOf(DateTimeImmutable::class, $habit->getCreatedAt());
        $this->assertInstanceOf(DateTimeImmutable::class, $habit->getUpdatedAt());
    }

    public function testSetTitleUpdatesTitleAndUpdatesTimestamp(): void
    {
        $habit = $this->createHabit();
        $initialUpdatedAt = $habit->getUpdatedAt();
        
        $newTitle = 'Read an hour';
        $habit->setTitle($newTitle);

        $this->assertSame($newTitle, $habit->getTitle());
        $this->assertGreaterThan(
            $initialUpdatedAt,
            $habit->getUpdatedAt(),
            'Updated timestamp should be greater than initial timestamp'
        );
    }

    public function testAddHabitReminderAddsReminderAndUpdatesTimestamp(): void
    {
        $habit = $this->createHabit(['id' => 1]);
        $initialUpdatedAt = $habit->getUpdatedAt();

        $reminder = new HabitReminder(habitId: 1, weekDay: 1, reminderTime: '12:00');
        $habit->addHabitReminder($reminder);

        $this->assertCount(1, $habit->getHabitReminders());
        $this->assertSame('12:00', $habit->getHabitReminders()->first()->getReminderTime());
        $this->assertGreaterThan(
            $initialUpdatedAt,
            $habit->getUpdatedAt(),
            'Updated timestamp should be greater than initial timestamp'
        );
    }

    /**
     * @dataProvider serializationMethodsProvider
     */
    public function testSerializationReturnsCorrectArrayStructure(string $method): void
    {
        $userId = 99;
        $habitId = 5;
        $habitTitle = 'Drink water';
        $createdAt = new DateTimeImmutable('-1 day');
        $updatedAt = new DateTimeImmutable();

        $user = $this->createUser(['id' => $userId]);
        $habit = $this->createHabit([
            'id' => $habitId,
            'user' => $user,
            'title' => $habitTitle,
            'createdAt' => $createdAt,
            'updatedAt' => $updatedAt,
        ]);

        $expected = [
            'id' => $habitId,
            'user_id' => $userId,
            'title' => $habitTitle,
            'created_at' => $createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $updatedAt->format('Y-m-d H:i:s'),
        ];

        $this->assertEquals($expected, $habit->$method());
    }

    public static function serializationMethodsProvider(): array
    {
        return [
            'toArray method' => ['toArray'],
            'jsonSerialize method' => ['jsonSerialize'],
        ];
    }

    public function testFromArrayCreatesHabitWithCorrectProperties(): void
    {
        $userId = 10;
        $habitId = 2;
        $habitTitle = 'Exercise';
        $createdAtString = '2023-01-01 10:00:00';
        $updatedAtString = '2023-01-01 10:00:00';

        $user = $this->createUser(['id' => $userId]);
        $userRepositoryMock = $this->createMock(UserRepositoryInterface::class);
        $userRepositoryMock
            ->method('findById')
            ->with($userId)
            ->willReturn($user);

        $data = [
            'id' => $habitId,
            'user_id' => $userId,
            'title' => $habitTitle,
            'created_at' => $createdAtString,
            'updated_at' => $updatedAtString,
        ];

        $habit = Habit::fromArray($data, $userRepositoryMock);

        $this->assertInstanceOf(Habit::class, $habit);
        $this->assertHabitMatchesData($habit, $data);
    }

    private function assertHabitMatchesData(Habit $habit, array $data): void
    {
        $this->assertSame($data['id'], $habit->getId(), 'Habit ID should match');
        $this->assertSame($data['user_id'], $habit->getUser()->getId(), 'User ID should match');
        $this->assertSame($data['title'], $habit->getTitle(), 'Title should match');
        $this->assertSame(
            $data['created_at'],
            $habit->getCreatedAt()->format('Y-m-d H:i:s'),
            'Created timestamp should match'
        );
        $this->assertSame(
            $data['updated_at'],
            $habit->getUpdatedAt()->format('Y-m-d H:i:s'),
            'Updated timestamp should match'
        );
    }
}