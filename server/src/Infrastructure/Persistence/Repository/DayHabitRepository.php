<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repository;

use App\Domain\Repository\DayHabitRepositoryInterface;
use PDO;

class DayHabitRepository implements DayHabitRepositoryInterface
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function toggle(int $dayId, int $habitId, int $userId): bool
    {
        // Check if the habit belongs to the user
        $habitCheckStmt = $this->pdo->prepare('SELECT user_id FROM habits WHERE id = :habit_id');
        $habitCheckStmt->execute(['habit_id' => $habitId]);

        $ownerUserId = $habitCheckStmt->fetchColumn();

        if ((int)$ownerUserId !== $userId) {
            return false;
        }

        // Check if the entry already exists
        $existingEntryStmt = $this->pdo->prepare('
            SELECT id FROM day_habits
            WHERE day_id = :day_id AND habit_id = :habit_id
        ');
        $existingEntryStmt->execute([
            'day_id' => $dayId,
            'habit_id' => $habitId,
        ]);
        $dayHabitId = $existingEntryStmt->fetchColumn();

        if ($dayHabitId) {
            // Delete (un-complete)
            $deleteStmt = $this->pdo->prepare('DELETE FROM day_habits WHERE id = :id');
            $deleteStmt->execute(['id' => $dayHabitId]);
            return false;
        } else {
            // Insert (complete)
            $insertStmt = $this->pdo->prepare('INSERT INTO day_habits (day_id, habit_id) VALUES (:day_id, :habit_id)');
            $insertStmt->execute([
                'day_id' => $dayId,
                'habit_id' => $habitId,
            ]);
            return true;
        }
    }

    public function isCompleted(int $dayId, int $habitId, int $userId): bool
    {
        // First, check if the habit belongs to the user
        $habitCheckStmt = $this->pdo->prepare('SELECT user_id FROM habits WHERE id = :habit_id');
        $habitCheckStmt->execute(['habit_id' => $habitId]);

        $ownerUserId = $habitCheckStmt->fetchColumn();

        if ((int)$ownerUserId !== $userId) {
            return false;
        }

        $stmt = $this->pdo->prepare('
            SELECT 1 FROM day_habits
            WHERE day_id = :day_id AND habit_id = :habit_id
        ');
        $stmt->execute([
            'day_id' => $dayId,
            'habit_id' => $habitId,
        ]);
        return (bool) $stmt->fetchColumn();
    }
}
