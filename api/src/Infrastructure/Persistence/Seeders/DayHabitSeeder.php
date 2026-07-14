<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Seeders;

use PDO;

class DayHabitSeeder
{
    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    public function run(): void
    {
        $userId = 1; // Usuário alvo

        $sql = "
            INSERT IGNORE INTO day_habits (day_id, habit_id)
            SELECT d.id, h.id
            FROM days d
            JOIN habits h ON h.user_id = :userId
            JOIN habit_week_days hwd ON hwd.habit_id = h.id
                AND hwd.week_day = DAYOFWEEK(d.date) - 1
            WHERE d.date BETWEEN CURDATE() - INTERVAL 35 DAY AND CURDATE() - INTERVAL 1 DAY
              -- Regras de início baseadas no título (já que os IDs podem variar)
              AND (h.title != 'Sem redes sociais até 9h' OR d.date >= CURDATE() - INTERVAL 20 DAY)
              AND (h.title != 'Dormir antes das 23h' OR d.date >= CURDATE() - INTERVAL 30 DAY)
              -- Simula ~75% de consistência inicial
              AND MOD(CRC32(CONCAT(d.date, '-', h.id)), 100) < 75
              -- Melhora para ~90% nas últimas 2 semanas
              AND (
                d.date < CURDATE() - INTERVAL 14 DAY
                OR MOD(CRC32(CONCAT(d.date, '-', h.id)), 100) < 90
              );
        ";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['userId' => $userId]);
            $count = $stmt->rowCount();
            echo "Realistic habit completions seeded for User 1 ({$count} entries)!
";
        } catch (\PDOException $pdoException) {
            echo "Error seeding day_habits: " . $pdoException->getMessage() . "
";
            throw $pdoException;
        }
    }
}
