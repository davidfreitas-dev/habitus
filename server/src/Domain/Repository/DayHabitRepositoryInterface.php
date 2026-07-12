<?php

declare(strict_types=1);

namespace App\Domain\Repository;

interface DayHabitRepositoryInterface
{
    /**
     * Toggles the completion status of a habit for a specific day.
     * If the habit is marked as completed for the day, it will be uncompleted, and vice-versa.
     *
     * @param int $habitId The ID of the habit.
     * @param int $dayId The ID of the day.
     * @param int $userId The ID of the user who owns the habit.
     */
    public function toggle(int $dayId, int $habitId, int $userId): bool;

    /**
     * Checks if a habit is completed for a specific day.
     *
     * @param int $habitId The ID of the habit.
     * @param int $dayId The ID of the day.
     * @param int $userId The ID of the user who owns the habit.
     *
     * @return bool True if the habit is completed for the day, false otherwise.
     */
    public function isCompleted(int $dayId, int $habitId, int $userId): bool;
}
