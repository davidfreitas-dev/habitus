<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use DateTimeImmutable;

interface HabitStatsRepositoryInterface
{
    /**
     * @return array<int, array{week_day: int, completed: int, total: int}>
     */
    public function getWeekStats(int $userId, DateTimeImmutable $startDate, DateTimeImmutable $endDate): array;

    /**
     * @return array<int, array{week_day: int, completed: int, total: int}>
     */
    public function getAggregatedStats(int $userId, DateTimeImmutable $startDate, DateTimeImmutable $endDate): array;

    /**
     * @param int $userId
     * @param DateTimeImmutable|null $date The reference date ("today").
     * @return array{current_streak: int, longest_streak: int}
     */
    public function getStreaks(int $userId, ?DateTimeImmutable $date = null): array;
}
