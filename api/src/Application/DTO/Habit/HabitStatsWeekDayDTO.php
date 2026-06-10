<?php

declare(strict_types=1);

namespace App\Application\DTO\Habit;

use JsonSerializable;

readonly class HabitStatsWeekDayDTO implements JsonSerializable
{
    public function __construct(
        public int $weekDay,
        public string $label,
        public ?float $percentage,
        public int $completed,
        public int $total,
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'week_day' => $this->weekDay,
            'label' => $this->label,
            'percentage' => $this->percentage,
            'completed' => $this->completed,
            'total' => $this->total,
        ];
    }
}
