<?php

declare(strict_types=1);

namespace App\Application\DTO\Habit;

use JsonSerializable;

class HabitsByDayResponseDTO implements JsonSerializable
{
    /**
     * @param HabitResponseDTO[] $possibleHabits
     * @param HabitResponseDTO[] $completedHabits
     */
    public function __construct(
        public readonly array $possibleHabits,
        public readonly array $completedHabits,
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'possible_habits' => $this->possibleHabits,
            'completed_habits' => $this->completedHabits,
        ];
    }
}
