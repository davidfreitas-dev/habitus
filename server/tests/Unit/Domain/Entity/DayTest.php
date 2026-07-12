<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entity;

use App\Domain\Entity\Day;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Support\EntityTestHelper;

class DayTest extends TestCase
{
    use EntityTestHelper;

    public function testCreatesDayWithCorrectPropertiesAccessibleViaGetters(): void
    {
        $date = new DateTimeImmutable('2024-02-04');
        $day = new Day($date);

        $this->assertInstanceOf(Day::class, $day);
        $this->assertNull($day->getId());
        $this->assertSame($date->format('Y-m-d'), $day->getDate()->format('Y-m-d'));
        $this->assertInstanceOf(DateTimeImmutable::class, $day->getCreatedAt());
        $this->assertInstanceOf(DateTimeImmutable::class, $day->getUpdatedAt());
    }

    public function testSetIdWorksWhenIdIsNull(): void
    {
        $day = new Day(new DateTimeImmutable());
        $this->assertNull($day->getId());

        $this->setEntityId($day, 10);
        $this->assertSame(10, $day->getId());
    }

    public function testSetDayUpdatesDateAndTimestamp(): void
    {
        $day = new Day(new DateTimeImmutable('2024-01-01'));
        $initialUpdatedAt = $day->getUpdatedAt();

        $newDate = new DateTimeImmutable('2024-01-02');
        $day->setDay($newDate);

        $this->assertSame($newDate->format('Y-m-d'), $day->getDate()->format('Y-m-d'));
        $this->assertGreaterThan(
            $initialUpdatedAt,
            $day->getUpdatedAt(),
            'Updated timestamp should be greater than initial timestamp'
        );
    }

    public function testSetDayDoesNotUpdateIfDateIsSame(): void
    {
        $date = new DateTimeImmutable('2024-01-01');
        $day = new Day($date);
        $initialUpdatedAt = $day->getUpdatedAt();

        // Call setDay with the same date
        $day->setDay(new DateTimeImmutable('2024-01-01'));

        $this->assertSame($date->format('Y-m-d'), $day->getDate()->format('Y-m-d'));
        $this->assertEquals(
            $initialUpdatedAt,
            $day->getUpdatedAt(),
            'Updated timestamp should be the same if date did not change'
        );
    }

    /**
     * @dataProvider serializationMethodsProvider
     */
    public function testSerializationReturnsCorrectArrayStructure(string $method): void
    {
        $dayId = 5;
        $date = new DateTimeImmutable('2024-02-04');
        $createdAt = new DateTimeImmutable('-2 days');
        $updatedAt = new DateTimeImmutable('-1 day');
        
        $day = new Day($date, $createdAt, $updatedAt);
        $this->setEntityId($day, $dayId);

        $expected = [
            'id' => $dayId,
            'date' => $date->format('Y-m-d'),
            'created_at' => $createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $updatedAt->format('Y-m-d H:i:s'),
        ];

        $this->assertEquals($expected, $day->$method());
    }

    public static function serializationMethodsProvider(): array
    {
        return [
            'toArray method' => ['toArray'],
            'jsonSerialize method' => ['jsonSerialize'],
        ];
    }

    public function testFromArrayCreatesDayWithCorrectProperties(): void
    {
        $data = [
            'id' => 15,
            'date' => '2024-02-04',
            'created_at' => '2023-01-01 12:00:00',
            'updated_at' => '2023-01-01 13:00:00',
        ];

        $day = Day::fromArray($data);

        $this->assertInstanceOf(Day::class, $day);
        $this->assertSame($data['id'], $day->getId());
        $this->assertSame($data['date'], $day->getDate()->format('Y-m-d'));
        $this->assertSame(
            $data['created_at'],
            $day->getCreatedAt()->format('Y-m-d H:i:s')
        );
        $this->assertSame(
            $data['updated_at'],
            $day->getUpdatedAt()->format('Y-m-d H:i:s')
        );
    }
}
