<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Seeders;

use DateTimeImmutable;
use PDO;

class DaySeeder
{
    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    public function run(): void
    {
        $today = new DateTimeImmutable();
        $currentYear = (int) $today->format('Y');
        $startDate = new DateTimeImmutable($currentYear . '-01-01');
        $endDate = $today;

        $interval = new \DateInterval('P1D');
        $period = new \DatePeriod($startDate, $interval, $endDate);

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare('INSERT IGNORE INTO days (date) VALUES (?)');
            foreach ($period as $date) {
                $stmt->execute([$date->format('Y-m-d')]);
            }

            $this->pdo->commit();
        } catch (\PDOException $pdoException) {
            $this->pdo->rollBack();
            throw $pdoException;
        }
    }
}
