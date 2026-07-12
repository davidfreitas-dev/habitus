<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Day;
use DateTimeImmutable;

interface DayRepositoryInterface
{
    /**
     * Finds a Day entity by date.
     *
     * @param DateTimeImmutable $date The date to find the Day for.
     *
     * @return Day|null The found Day entity or null if not found.
     */
    public function findOneByDate(DateTimeImmutable $date): ?Day;

    /**
     * Creates a new Day record.
     *
     * @param DateTimeImmutable $date The date for the Day entity to create.
     *
     * @return Day The created Day entity.
     */
    public function create(DateTimeImmutable $date): Day;

    /**
     * Finds the IDs of habits completed on a specific date for a given user.
     *
     * @param int $userId The ID of the user.
     * @param DateTimeImmutable $date The date to check for completed habits.
     *
     * @return int[] An array of habit IDs that were completed on the given date.
     */
    public function findCompletedHabitIdsByDate(int $userId, DateTimeImmutable $date): array;
}
