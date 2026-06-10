<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use DateTimeImmutable;
use JsonSerializable;

class HabitWeekDay implements JsonSerializable
{
    private ?int $id = null;
    private int $weekDay;

    public function __construct(
        private readonly int $habitId,
        int $weekDay,
        private readonly ?DateTimeImmutable $createdAt = new DateTimeImmutable(),
        private ?DateTimeImmutable $updatedAt = new DateTimeImmutable(),
    ) {
        if ($weekDay < 0 || $weekDay > 6) {
            throw new \InvalidArgumentException('O dia da semana deve ser entre 0 (Domingo) e 6 (Sábado).');
        }
        $this->weekDay = $weekDay;
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

    public function getHabitId(): int
    {
        return $this->habitId;
    }

    public function getWeekDay(): int
    {
        return $this->weekDay;
    }

    public function setWeekDay(int $weekDay): void
    {
        if ($weekDay < 0 || $weekDay > 6) {
            throw new \InvalidArgumentException('O dia da semana deve ser entre 0 (Domingo) e 6 (Sábado).');
        }

        if ($this->weekDay !== $weekDay) {
            $this->weekDay = $weekDay;
            $this->touch();
        }
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
            'habit_id' => $this->habitId,
            'week_day' => $this->weekDay,
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
        $habitWeekDay = new self(
            $data['habit_id'],
            $data['week_day'],
            new DateTimeImmutable($data['created_at']),
            new DateTimeImmutable($data['updated_at']),
        );

        if (isset($data['id'])) {
            $habitWeekDay->setId($data['id']);
        }

        return $habitWeekDay;
    }

    private function touch(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}
