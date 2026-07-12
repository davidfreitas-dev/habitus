<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Habit;
use DateTimeImmutable;

interface HabitRepositoryInterface
{
    /**
     * Creates a new habit record.
     *
     * @param Habit $habit The habit entity to create.
     * @param array $weekDays An array of weekdays associated with the habit.
     *
     * @return Habit The created habit entity.
     */
    public function create(Habit $habit, array $weekDays): Habit;

    /**
     * Finds a habit by its ID and user ID.
     *
     * @param int $id The ID of the habit.
     * @param int $userId The ID of the user who owns the habit.
     *
     * @return Habit|null The found habit entity or null if not found.
     */
    public function findById(int $id, int $userId): ?Habit;

    /**
     * Finds a habit by its title and user ID.
     *
     * @param string $title The title of the habit.
     * @param int $userId The ID of the user who owns the habit.
     *
     * @return Habit|null The found habit entity or null if not found.
     */
    public function findByTitle(string $title, int $userId): ?Habit;

    /**
     * Updates an existing habit record.
     *
     * @param Habit $habit The habit entity with updated data.
     * @param array $weekDays An array of updated weekdays associated with the habit.
     *
     * @return Habit The updated habit entity.
     */
    public function update(Habit $habit, array $weekDays): Habit;

    /**
     * Deletes a habit record by its ID and user ID.
     *
     * @param int $id The ID of the habit to delete.
     * @param int $userId The ID of the user who owns the habit.
     *
     * @return bool True on success, false otherwise.
     */
    public function delete(int $id, int $userId): bool;

    /**
     * Finds possible habits for a given date and user.
     *
     * @param DateTimeImmutable $date The date to find possible habits for.
     * @param int $userId The ID of the user.
     *
     * @return Habit[] An array of possible habit entities.
     */
    public function findPossibleHabits(DateTimeImmutable $date, int $userId): array;

    /**
     * Finds completed habits for a given date and user.
     *
     * @param DateTimeImmutable $date The date to find completed habits for.
     * @param int $userId The ID of the user.
     *
     * @return Habit[] An array of completed habit entities.
     */
    public function findCompletedHabits(DateTimeImmutable $date, int $userId): array;

    /**
     * Retrieves a summary of habits, including completed and total habits, for a given user and date.
     *
     * @param int $userId The ID of the user.
     * @param DateTimeImmutable|null $date The reference date ("today").
     *
     * @return array An array of habit summary data (date, completed count, total count).
     */
    public function getHabitsSummary(int $userId, ?DateTimeImmutable $date = null): array;

    /**
     * Finds all habits for a given user.
     *
     * @param int $userId The ID of the user.
     *
     * @return Habit[] An array of habit entities.
     */
    public function findAllByUserId(int $userId): array;
}
