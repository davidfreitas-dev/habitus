<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\DTO\Habit\HabitsSummaryResponseDTO;
use App\Application\DTO\Habit\HabitSummaryItemDTO;
use App\Domain\Repository\HabitRepositoryInterface;
use DateTimeImmutable;

class GetHabitsSummaryUseCase
{
    public function __construct(
        private readonly HabitRepositoryInterface $habitRepository,
    ) {
    }

    /**
     * @param int $userId
     * @param DateTimeImmutable|null $date
     * @return HabitsSummaryResponseDTO
     */
    public function execute(int $userId, ?DateTimeImmutable $date = null): HabitsSummaryResponseDTO
    {
        $summaryData = $this->habitRepository->getHabitsSummary($userId, $date);

        $items = array_map(
            fn (array $item): \App\Application\DTO\Habit\HabitSummaryItemDTO => new HabitSummaryItemDTO(
                date: $item['date'],
                completed: (int) $item['completed'],
                total: (int) $item['total'],
            ),
            $summaryData,
        );

        return new HabitsSummaryResponseDTO($items);
    }
}
