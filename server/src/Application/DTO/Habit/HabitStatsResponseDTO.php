<?php

declare(strict_types=1);

namespace App\Application\DTO\Habit;

use JsonSerializable;

readonly class HabitStatsResponseDTO implements JsonSerializable
{
    /**
     * @param HabitStatsWeekDayDTO[] $dailyStats
     */
    public function __construct(
        public array $dailyStats,
        public int $currentStreak,
        public int $longestStreak,
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'daily_stats' => $this->dailyStats,
            'current_streak' => $this->currentStreak,
            'longest_streak' => $this->longestStreak,
        ];
    }
}
