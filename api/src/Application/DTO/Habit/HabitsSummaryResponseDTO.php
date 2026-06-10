<?php

declare(strict_types=1);

namespace App\Application\DTO\Habit;

use JsonSerializable;

class HabitsSummaryResponseDTO implements JsonSerializable
{
    public function __construct(
        public readonly array $items,
    ) {
    }

    public function jsonSerialize(): array
    {
        return array_map(
            fn (HabitSummaryItemDTO $item): array => $item->jsonSerialize(),
            $this->items,
        );
    }
}
