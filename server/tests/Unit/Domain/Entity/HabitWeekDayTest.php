<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entity;

use App\Domain\Entity\HabitWeekDay;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Support\EntityTestHelper;

class HabitWeekDayTest extends TestCase
{
    use EntityTestHelper;

    public function testCreatesHabitWeekDayWithCorrectPropertiesAccessibleViaGetters(): void
    {
        $habitId = 1;
        $weekDay = 0; // Sunday
        $habitWeekDay = new HabitWeekDay($habitId, $weekDay);

        $this->assertInstanceOf(HabitWeekDay::class, $habitWeekDay);
        $this->assertNull($habitWeekDay->getId());
        $this->assertSame($habitId, $habitWeekDay->getHabitId());
        $this->assertSame($weekDay, $habitWeekDay->getWeekDay());
        $this->assertInstanceOf(DateTimeImmutable::class, $habitWeekDay->getCreatedAt());
        $this->assertInstanceOf(DateTimeImmutable::class, $habitWeekDay->getUpdatedAt());
    }

    public function testSetIdWorksWhenIdIsNull(): void
    {
        $habitWeekDay = new HabitWeekDay(1, 1);
        $this->assertNull($habitWeekDay->getId());

        $this->setEntityId($habitWeekDay, 10);
        $this->assertSame(10, $habitWeekDay->getId());
    }

    public function testSetWeekDayUpdatesWeekDayAndTimestamp(): void
    {
        $habitWeekDay = new HabitWeekDay(1, 0); // Initial weekday 0
        $initialUpdatedAt = $habitWeekDay->getUpdatedAt();

        $newWeekDay = 1; // Change to a different weekday
        $habitWeekDay->setWeekDay($newWeekDay);

        $this->assertSame($newWeekDay, $habitWeekDay->getWeekDay());
        $this->assertGreaterThan(
            $initialUpdatedAt,
            $habitWeekDay->getUpdatedAt(),
            'Updated timestamp should be greater than initial timestamp'
        );
    }

    /**
     * @dataProvider invalidWeekDayProvider
     */
    public function testSetWeekDayThrowsExceptionForInvalidValues(int $invalidWeekDay): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('O dia da semana deve ser entre 0 (Domingo) e 6 (Sábado).');

        $habitWeekDay = new HabitWeekDay(1, 0); // Valid initial
        $habitWeekDay->setWeekDay($invalidWeekDay);
    }

    /**
     * @dataProvider validWeekDayProvider
     */
    public function testConstructorThrowsExceptionForInvalidWeekDay(int $validWeekDay): void
    {
        // This test case is to ensure valid weekdays pass the constructor.
        // The exception is expected for invalid weekdays, covered by other tests.
        $this->expectNotToPerformAssertions(); // No exception should be thrown
        new HabitWeekDay(1, $validWeekDay);
    }

    /**
     * @dataProvider invalidWeekDayProvider
     */
    public function testConstructorThrowsExceptionForInvalidValues(int $invalidWeekDay): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('O dia da semana deve ser entre 0 (Domingo) e 6 (Sábado).');

        new HabitWeekDay(1, $invalidWeekDay);
    }

    public static function validWeekDayProvider(): array
    {
        return [
            'Sunday (0)' => [0],
            'Monday (1)' => [1],
            'Tuesday (2)' => [2],
            'Wednesday (3)' => [3],
            'Thursday (4)' => [4],
            'Friday (5)' => [5],
            'Saturday (6)' => [6],
        ];
    }

    public static function invalidWeekDayProvider(): array
    {
        return [
            'Negative value' => [-1],
            'Value greater than 6' => [7],
        ];
    }

    /**
     * @dataProvider serializationMethodsProvider
     */
    public function testSerializationReturnsCorrectArrayStructure(string $method): void
    {
        $habitId = 5;
        $habitWeekDayId = 10;
        $weekDay = 3; // Wednesday
        $createdAt = new DateTimeImmutable('-2 days');
        $updatedAt = new DateTimeImmutable('-1 day');
        
        $habitWeekDay = new HabitWeekDay(
            $habitId,
            $weekDay,
            $createdAt,
            $updatedAt
        );
        $this->setEntityId($habitWeekDay, $habitWeekDayId);

        $expected = [
            'id' => $habitWeekDayId,
            'habit_id' => $habitId,
            'week_day' => $weekDay,
            'created_at' => $createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $updatedAt->format('Y-m-d H:i:s'),
        ];

        $this->assertEquals($expected, $habitWeekDay->$method());
    }

    public static function serializationMethodsProvider(): array
    {
        return [
            'toArray method' => ['toArray'],
            'jsonSerialize method' => ['jsonSerialize'],
        ];
    }

    public function testFromArrayCreatesHabitWeekDayWithCorrectProperties(): void
    {
        $data = [
            'id' => 15,
            'habit_id' => 20,
            'week_day' => 4,
            'created_at' => '2023-01-01 12:00:00',
            'updated_at' => '2023-01-01 13:00:00',
        ];

        $habitWeekDay = HabitWeekDay::fromArray($data);

        $this->assertInstanceOf(HabitWeekDay::class, $habitWeekDay);
        $this->assertSame($data['id'], $habitWeekDay->getId());
        $this->assertSame($data['habit_id'], $habitWeekDay->getHabitId());
        $this->assertSame($data['week_day'], $habitWeekDay->getWeekDay());
        $this->assertSame(
            $data['created_at'],
            $habitWeekDay->getCreatedAt()->format('Y-m-d H:i:s')
        );
        $this->assertSame(
            $data['updated_at'],
            $habitWeekDay->getUpdatedAt()->format('Y-m-d H:i:s')
        );
    }
}
