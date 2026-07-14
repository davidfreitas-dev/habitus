<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use DateTimeImmutable;
use JsonSerializable;

class DayHabit implements JsonSerializable
{
    private ?int $id = null;

    public function __construct(private readonly int $dayId, private readonly int $habitId, private readonly ?DateTimeImmutable $createdAt = new DateTimeImmutable(), private ?DateTimeImmutable $updatedAt = new DateTimeImmutable())
    {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        if ($this->id !== null) {
            return;
        }

        $this->id = $id;
    }

    public function getDayId(): int
    {
        return $this->dayId;
    }

    public function getHabitId(): int
    {
        return $this->habitId;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'day_id' => $this->dayId,
            'habit_id' => $this->habitId,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public static function fromArray(array $data): self
    {
        $dayHabit = new self(
            $data['day_id'],
            $data['habit_id'],
            new DateTimeImmutable($data['created_at']),
            new DateTimeImmutable($data['updated_at']),
        );

        if (isset($data['id'])) {
            $dayHabit->setId($data['id']);
        }

        return $dayHabit;
    }

    private function touch(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}
