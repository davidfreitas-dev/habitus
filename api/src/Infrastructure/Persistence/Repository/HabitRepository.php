<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repository;

use App\Domain\Entity\Habit;
use App\Domain\Entity\HabitWeekDay;
use App\Domain\Repository\HabitRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;
use DateTimeImmutable;
use PDO;
use RuntimeException;

class HabitRepository implements HabitRepositoryInterface
{
    private const string DATE_FORMAT = 'Y-m-d H:i:s';

    private const string DATE_ONLY_FORMAT = 'Y-m-d';

    private const string WEEK_DAY_FORMAT = 'w';

    public function __construct(
        private readonly PDO $pdo,
        private readonly UserRepositoryInterface $userRepository,
    ) {
    }

    public function findById(int $id, int $userId): ?Habit
    {
        $sql = 'SELECT * FROM habits WHERE id = :id AND user_id = :user_id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id, 'user_id' => $userId]);

        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ? $this->hydrate($data) : null;
    }

    public function findByTitle(string $title, int $userId): ?Habit
    {
        $sql = 'SELECT * FROM habits WHERE title = :title AND user_id = :user_id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['title' => $title, 'user_id' => $userId]);

        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ? $this->hydrate($data) : null;
    }

    public function create(Habit $habit, array $weekDays): Habit
    {
        $sql = 'INSERT INTO habits (user_id, title, reminder_time, created_at, updated_at) 
                VALUES (:user_id, :title, :reminder_time, :created_at, :updated_at)';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'user_id' => $habit->getUser()->getId(),
            'title' => $habit->getTitle(),
            'reminder_time' => $habit->getReminderTime(),
            'created_at' => $this->formatDateTime($habit->getCreatedAt()),
            'updated_at' => $this->formatDateTime($habit->getUpdatedAt()),
        ]);

        $habitId = (int) $this->pdo->lastInsertId();
        $habit->setId($habitId);

        $this->syncWeekDays($habit, $weekDays);

        // Re-fetch the habit to ensure its weekDays collection is populated
        $fullyHydratedHabit = $this->findById($habitId, $habit->getUser()->getId());
        if (!$fullyHydratedHabit instanceof \App\Domain\Entity\Habit) {
            throw new RuntimeException("Falha ao buscar novamente o hábito recém-criado.");
        }

        return $fullyHydratedHabit;
    }

    public function update(Habit $habit, array $weekDays): Habit
    {
        $sql = 'UPDATE habits 
                SET title = :title, 
                    reminder_time = :reminder_time, 
                    updated_at = :updated_at 
                WHERE id = :id';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id' => $habit->getId(),
            'title' => $habit->getTitle(),
            'reminder_time' => $habit->getReminderTime(),
            'updated_at' => $this->formatDateTime($habit->getUpdatedAt()),
        ]);

        $this->syncWeekDays($habit, $weekDays);

        // Re-fetch the habit to ensure its weekDays collection and updated timestamp are populated
        $fullyHydratedHabit = $this->findById($habit->getId(), $habit->getUser()->getId());
        if (!$fullyHydratedHabit instanceof \App\Domain\Entity\Habit) {
            throw new RuntimeException("Falha ao buscar novamente o hábito recém-atualizado.");
        }

        return $fullyHydratedHabit;
    }

    public function delete(int $id, int $userId): bool
    {
        $sql = 'DELETE FROM habits WHERE id = :id AND user_id = :user_id';
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute(['id' => $id, 'user_id' => $userId]);
    }

    public function findPossibleHabits(DateTimeImmutable $date, int $userId): array
    {
        // PHP 'w' format: Sunday=0, Monday=1, ..., Saturday=6
        $weekDay = (int) $date->format(self::WEEK_DAY_FORMAT);

        $sql = "
            SELECT h.*
            FROM habits h
            INNER JOIN habit_week_days hwd ON h.id = hwd.habit_id
            WHERE h.user_id = :user_id
                AND hwd.week_day = :week_day
                AND DATE(h.created_at) <= :date
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'user_id' => $userId,
            'week_day' => $weekDay,
            'date' => $date->format(self::DATE_ONLY_FORMAT),
        ]);

        return $this->hydrateMultiple($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function findCompletedHabits(DateTimeImmutable $date, int $userId): array
    {
        $sql = "
            SELECT h.*
            FROM habits h
            INNER JOIN day_habits dh ON h.id = dh.habit_id
            INNER JOIN days d ON dh.day_id = d.id
            WHERE h.user_id = :user_id
              AND d.date = :date
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'user_id' => $userId,
            'date' => $date->format(self::DATE_ONLY_FORMAT),
        ]);

        return $this->hydrateMultiple($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function getHabitsSummary(int $userId, ?DateTimeImmutable $date = null): array
    {
        $currentDate = ($date ?? new DateTimeImmutable())->format(self::DATE_ONLY_FORMAT);
        $startOfYear = new DateTimeImmutable('first day of January this year')->format(self::DATE_ONLY_FORMAT);

        $sql = "
            WITH RECURSIVE date_range AS (
                SELECT :start_of_year AS date
                UNION ALL
                SELECT DATE_ADD(date, INTERVAL 1 DAY)
                FROM date_range
                WHERE date < :current_date
            )
            SELECT
                dr.date,
                COALESCE(
                    (
                        SELECT COUNT(*)
                        FROM day_habits DH
                        JOIN habits H1 ON DH.habit_id = H1.id
                        JOIN days D ON DH.day_id = D.id
                        WHERE D.date = dr.date
                        AND H1.user_id = :userId1
                    ), 0
                ) AS completed,
                COALESCE(
                    (
                        SELECT COUNT(*)
                        FROM habit_week_days HWD
                        JOIN habits H2 ON HWD.habit_id = H2.id
                        WHERE HWD.week_day = (WEEKDAY(dr.date) + 1) % 7
                        AND DATE(H2.created_at) <= dr.date
                        AND H2.user_id = :userId2
                    ), 0
                ) AS total
            FROM date_range dr
            ORDER BY dr.date ASC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'userId1' => $userId,
            'userId2' => $userId,
            'start_of_year' => $startOfYear,
            'current_date' => $currentDate,
        ]);

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn (array $row): array => [
            'date' => $row['date'],
            'completed' => (int) $row['completed'],
            'total' => (int) $row['total'],
        ], $results);
    }

    public function findAllByUserId(int $userId): array
    {
        $sql = 'SELECT * FROM habits WHERE user_id = :user_id ORDER BY created_at DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['user_id' => $userId]);

        return $this->hydrateMultiple($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    private function syncWeekDays(Habit $habit, array $weekDays): void
    {
        $this->deleteWeekDays($habit->getId());
        $this->insertWeekDays($habit->getId(), $weekDays);
    }

    private function deleteWeekDays(int $habitId): void
    {
        $sql = 'DELETE FROM habit_week_days WHERE habit_id = :habit_id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['habit_id' => $habitId]);
    }

    private function insertWeekDays(int $habitId, array $weekDays): void
    {
        if ($weekDays === []) {
            return;
        }

        $sql = 'INSERT INTO habit_week_days (habit_id, week_day) VALUES (:habit_id, :week_day)';
        $stmt = $this->pdo->prepare($sql);

        foreach ($weekDays as $weekDay) {
            $stmt->execute([
                'habit_id' => $habitId,
                'week_day' => $weekDay,
            ]);
        }
    }

    private function hydrateMultiple(array $habitsData): array
    {
        $habits = [];
        foreach ($habitsData as $habitData) {
            $habits[] = $this->hydrate($habitData);
        }

        return $habits;
    }

    private function hydrate(array $data): Habit
    {
        $user = $this->userRepository->findById((int) $data['user_id']);

        if (!$user instanceof \App\Domain\Entity\User) {
            throw new RuntimeException(
                sprintf('Usuário com ID %d não encontrado para hidratação do hábito', $data['user_id']),
            );
        }

        $weekDays = $this->fetchWeekDaysForHabit((int) $data['id']);

        $habit = new Habit(
            title: $data['title'],
            user: $user,
            reminderTime: $data['reminder_time'] ?? null,
            createdAt: new DateTimeImmutable($data['created_at']),
            updatedAt: new DateTimeImmutable($data['updated_at']),
        );

        $habit->setId((int) $data['id']);
        $this->attachWeekDaysToHabit($habit, $weekDays);

        return $habit;
    }

    private function fetchWeekDaysForHabit(int $habitId): array
    {
        $sql = 'SELECT week_day FROM habit_week_days WHERE habit_id = :habit_id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['habit_id' => $habitId]);

        $weekDaysFromDb = array_map(intval(...), $stmt->fetchAll(PDO::FETCH_COLUMN));
        return $weekDaysFromDb;
    }

    private function attachWeekDaysToHabit(Habit $habit, array $weekDays): void
    {
        foreach ($weekDays as $weekDay) {
            $habitWeekDay = new HabitWeekDay($habit->getId(), $weekDay);
            $habit->addHabitWeekDay($habitWeekDay);
        }
    }

    private function formatDateTime(DateTimeImmutable $dateTime): string
    {
        return $dateTime->format(self::DATE_FORMAT);
    }
}
