<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use DateTimeImmutable;
use InvalidArgumentException;
use JsonSerializable;

class HabitReminder implements JsonSerializable
{
    private ?int $id = null;
    private int $weekDay;
    private string $reminderTime;

    public function __construct(
        private readonly int $habitId,
        int $weekDay,
        string $reminderTime,
        private readonly ?DateTimeImmutable $createdAt = new DateTimeImmutable(),
        private ?DateTimeImmutable $updatedAt = new DateTimeImmutable(),
    ) {
        $this->validateWeekDay($weekDay);
        $normalizedTime = $this->normalizeTime($reminderTime);

        $this->weekDay = $weekDay;
        $this->reminderTime = $normalizedTime;
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

    public function getReminderTime(): string
    {
        return $this->reminderTime;
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
            'time' => $this->reminderTime,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    private function validateWeekDay(int $weekDay): void
    {
        if ($weekDay < 0 || $weekDay > 6) {
            throw new InvalidArgumentException('O dia da semana deve ser entre 0 (Domingo) e 6 (Sábado).');
        }
    }

    private function normalizeTime(string $time): string
    {
        if (preg_match('/^([0-1]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $time) !== 1) {
            throw new InvalidArgumentException('O horário do lembrete deve estar no formato HH:mm.');
        }

        return substr($time, 0, 5);
    }
}
