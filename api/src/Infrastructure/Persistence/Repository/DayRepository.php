<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repository;

use App\Domain\Entity\Day;
use App\Domain\Repository\DayRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;
use DateTimeImmutable;
use PDO;
use PDOException;

class DayRepository implements DayRepositoryInterface
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly UserRepositoryInterface $userRepository, // This dependency is still needed for hydrating User in other contexts
    ) {
    }

    public function findOneByDate(DateTimeImmutable $date): ?Day
    {
        $sql = 'SELECT * FROM days WHERE date = :date';
        $params = ['date' => $date->format('Y-m-d')];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ? $this->hydrate($data) : null;
    }

    public function create(DateTimeImmutable $date): Day
    {
        try {
            $sql = 'INSERT INTO days (date) VALUES (:date)';
            $params = [
                'date' => $date->format('Y-m-d'),
            ];
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            $day = new Day(date: $date);
            $day->setId((int)$this->pdo->lastInsertId());

            return $day;
        } catch (PDOException $pdoException) {
            // Check for unique constraint violation error code (e.g., MySQL 23000)
            if ($pdoException->getCode() === '23000') {
                // If the day already exists, fetch and return it
                $existingDay = $this->findOneByDate($date);
                if ($existingDay instanceof \App\Domain\Entity\Day) {
                    return $existingDay;
                }
            }

            // If it's another PDOException or findOneByDate failed, re-throw
            throw $pdoException;
        }
    }

    public function findCompletedHabitIdsByDate(int $userId, DateTimeImmutable $date): array
    {
        $sql = "
            SELECT dh.habit_id
            FROM day_habits dh
            JOIN habits h ON dh.habit_id = h.id
            JOIN days d ON dh.day_id = d.id
            WHERE h.user_id = :user_id
              AND d.date = :date
        ";
        $params = [
            'user_id' => $userId,
            'date' => $date->format('Y-m-d'),
        ];
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    private function hydrate(array $data): Day
    {
        // Day entity no longer has a user property, so we don't try to hydrate it here
        $day = new Day(
            date: new DateTimeImmutable($data['date']),
        );
        $day->setId((int)$data['id']);

        return $day;
    }
}
