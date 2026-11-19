<?php

declare(strict_types=1);

namespace App\Application\DTO\Habit;

use App\Domain\Entity\Habit;
use App\Domain\Entity\HabitReminder;
use App\Domain\Entity\HabitWeekDay;
use JsonSerializable;

class HabitResponseDTO implements JsonSerializable
{
    /**
     * @param array<int, array{week_day: int, time: string}> $reminders
     */
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly array $weekDays,
        public readonly int $userId,
        public readonly array $reminders,
        public readonly string $createdAt,
        public readonly string $updatedAt,
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'week_days' => $this->weekDays,
            'user_id' => $this->userId,
            'reminders' => $this->reminders,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }

    public static function fromEntity(Habit $habit): self
    {
        $weekDays = $habit->getHabitWeekDays()->map(fn (HabitWeekDay $hw): int => $hw->getWeekDay())->toArray();
        $reminders = $habit->getHabitReminders()->map(
            fn (HabitReminder $hr): array => [
                'week_day' => $hr->getWeekDay(),
                'time' => $hr->getReminderTime(),
            ],
        )->toArray();

        return new self(
            id: $habit->getId(),
            title: $habit->getTitle(),
            weekDays: array_values($weekDays),
            userId: $habit->getUser()->getId(),
            reminders: array_values($reminders),
            createdAt: $habit->getCreatedAt()->format('Y-m-d H:i:s'),
            updatedAt: $habit->getUpdatedAt()->format('Y-m-d H:i:s'),
        );
    }
}
