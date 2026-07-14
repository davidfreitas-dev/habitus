<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entity;

use App\Domain\Entity\DayHabit;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Support\EntityTestHelper;

class DayHabitTest extends TestCase
{
    use EntityTestHelper;

    public function testCreatesDayHabitWithCorrectPropertiesAccessibleViaGetters(): void
    {
        $dayId = 1;
        $habitId = 2;
        $dayHabit = new DayHabit($dayId, $habitId);

        $this->assertInstanceOf(DayHabit::class, $dayHabit);
        $this->assertNull($dayHabit->getId());
        $this->assertSame($dayId, $dayHabit->getDayId());
        $this->assertSame($habitId, $dayHabit->getHabitId());
        $this->assertInstanceOf(DateTimeImmutable::class, $dayHabit->getCreatedAt());
        $this->assertInstanceOf(DateTimeImmutable::class, $dayHabit->getUpdatedAt());
    }

    public function testSetIdWorksWhenIdIsNull(): void
    {
        $dayHabit = new DayHabit(1, 2);
        $this->assertNull($dayHabit->getId());

        $this->setEntityId($dayHabit, 10);
        $this->assertSame(10, $dayHabit->getId());
    }

    /**
     * @dataProvider serializationMethodsProvider
     */
    public function testSerializationReturnsCorrectArrayStructure(string $method): void
    {
        $dayHabitId = 5;
        $dayId = 1;
        $habitId = 2;
        $createdAt = new DateTimeImmutable('-2 days');
        $updatedAt = new DateTimeImmutable('-1 day');
        
        $dayHabit = new DayHabit($dayId, $habitId, $createdAt, $updatedAt);
        $this->setEntityId($dayHabit, $dayHabitId);

        $expected = [
            'id' => $dayHabitId,
            'day_id' => $dayId,
            'habit_id' => $habitId,
            'created_at' => $createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $updatedAt->format('Y-m-d H:i:s'),
        ];

        $this->assertEquals($expected, $dayHabit->$method());
    }

    public static function serializationMethodsProvider(): array
    {
        return [
            'toArray method' => ['toArray'],
            'jsonSerialize method' => ['jsonSerialize'],
        ];
    }

    public function testFromArrayCreatesDayHabitWithCorrectProperties(): void
    {
        $data = [
            'id' => 15,
            'day_id' => 10,
            'habit_id' => 20,
            'created_at' => '2023-01-01 12:00:00',
            'updated_at' => '2023-01-01 13:00:00',
        ];

        $dayHabit = DayHabit::fromArray($data);

        $this->assertInstanceOf(DayHabit::class, $dayHabit);
        $this->assertSame($data['id'], $dayHabit->getId());
        $this->assertSame($data['day_id'], $dayHabit->getDayId());
        $this->assertSame($data['habit_id'], $dayHabit->getHabitId());
        $this->assertSame(
            $data['created_at'],
            $dayHabit->getCreatedAt()->format('Y-m-d H:i:s')
        );
        $this->assertSame(
            $data['updated_at'],
            $dayHabit->getUpdatedAt()->format('Y-m-d H:i:s')
        );
    }
}
