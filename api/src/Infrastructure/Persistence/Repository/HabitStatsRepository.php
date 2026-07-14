<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repository;

use App\Domain\Repository\HabitStatsRepositoryInterface;
use DateTimeImmutable;
use PDO;

class HabitStatsRepository implements HabitStatsRepositoryInterface
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function getWeekStats(int $userId, DateTimeImmutable $startDate, DateTimeImmutable $endDate): array
    {
        return $this->fetchStats($userId, $startDate, $endDate);
    }

    public function getAggregatedStats(int $userId, DateTimeImmutable $startDate, DateTimeImmutable $endDate): array
    {
        return $this->fetchStats($userId, $startDate, $endDate);
    }

    public function getStreaks(int $userId, ?DateTimeImmutable $date = null): array
    {
        $referenceDate = $date ?? new DateTimeImmutable('today');
        $referenceDateString = $referenceDate->format('Y-m-d');

        // A streak is a sequence of consecutive days where all habits scheduled for that day were completed.
        // We consider days starting from the user's first habit creation date.

        $sql = "
            WITH RECURSIVE dates AS (
                SELECT DATE(MIN(created_at)) AS date
                FROM habits
                WHERE user_id = :user_id1
                UNION ALL
                SELECT date + INTERVAL 1 DAY
                FROM dates
                WHERE date < :reference_date1
            ),
            daily_completion AS (
                SELECT 
                    d.date,
                    (
                        SELECT COUNT(*)
                        FROM habit_week_days hwd
                        JOIN habits h ON hwd.habit_id = h.id
                        WHERE h.user_id = :user_id2
                        AND hwd.week_day = (WEEKDAY(d.date) + 1) % 7
                        AND DATE(h.created_at) <= d.date
                    ) AS total,
                    (
                        SELECT COUNT(*)
                        FROM day_habits dh
                        JOIN days ds ON dh.day_id = ds.id
                        JOIN habits h2 ON dh.habit_id = h2.id
                        WHERE h2.user_id = :user_id3
                        AND ds.date = d.date
                    ) AS completed
                FROM dates d
            ),
            successful_days AS (
                SELECT 
                    date,
                    CASE WHEN total > 0 AND completed >= total THEN 1 ELSE 0 END as success,
                    CASE WHEN total = 0 THEN 1 ELSE 0 END as skip -- Days with no habits don't break the streak
                FROM daily_completion
            ),
            islands AS (
                SELECT 
                    date,
                    success,
                    skip,
                    -- Gaps and Islands: group consecutive success/skip days
                    ROW_NUMBER() OVER (ORDER BY date) - 
                    ROW_NUMBER() OVER (PARTITION BY (success = 1 OR skip = 1) ORDER BY date) as grp
                FROM successful_days
            ),
            streak_groups AS (
                SELECT 
                    grp,
                    MIN(date) as start_date,
                    MAX(date) as end_date,
                    COUNT(*) as total_days,
                    SUM(success) as success_count
                FROM islands
                WHERE (success = 1 OR skip = 1)
                GROUP BY grp
            )
            SELECT 
                -- We only count streaks that have at least one success
                -- and we subtract any leading 'skip' days from that specific streak
                (
                    SELECT COUNT(*)
                    FROM islands i2
                    WHERE i2.grp = sg.grp 
                    AND (i2.success = 1 OR i2.skip = 1)
                    AND i2.date >= (SELECT MIN(i3.date) FROM islands i3 WHERE i3.grp = sg.grp AND i3.success = 1)
                ) as adjusted_streak
            FROM streak_groups sg
            WHERE success_count > 0
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'user_id1' => $userId,
            'user_id2' => $userId,
            'user_id3' => $userId,
            'reference_date1' => $referenceDateString,
        ]);

        $streaks = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($streaks)) {
            return ['current_streak' => 0, 'longest_streak' => 0];
        }

        $longestStreak = (int) max($streaks);

        // Calculate current streak specifically
        // We need to check if the last sequence ends today or yesterday
        $currentStreakSql = "
            WITH RECURSIVE dates AS (
                SELECT CAST(:reference_date2 AS DATE) AS date
                UNION ALL
                SELECT date - INTERVAL 1 DAY
                FROM dates
                WHERE date > (SELECT DATE(MIN(created_at)) FROM habits WHERE user_id = :user_id1)
            ),
            daily_check AS (
                SELECT 
                    d.date,
                    (
                        SELECT COUNT(*)
                        FROM habit_week_days hwd
                        JOIN habits h ON hwd.habit_id = h.id
                        WHERE h.user_id = :user_id2
                        AND hwd.week_day = (WEEKDAY(d.date) + 1) % 7
                        AND DATE(h.created_at) <= d.date
                    ) AS total,
                    (
                        SELECT COUNT(*)
                        FROM day_habits dh
                        JOIN days ds ON dh.day_id = ds.id
                        JOIN habits h2 ON dh.habit_id = h2.id
                        WHERE h2.user_id = :user_id3
                        AND ds.date = d.date
                    ) AS completed
                FROM dates d
            )
            SELECT date, total, completed
            FROM daily_check
            ORDER BY date DESC
        ";

        $stmt = $this->pdo->prepare($currentStreakSql);
        $stmt->execute([
            'user_id1' => $userId,
            'user_id2' => $userId,
            'user_id3' => $userId,
            'reference_date2' => $referenceDateString,
        ]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $currentStreak = 0;
        $firstDay = true;
        $hasMetSuccess = false;
        $tempStreak = 0;

        foreach ($rows as $row) {
            $total = (int) $row['total'];
            $completed = (int) $row['completed'];
            $isToday = $row['date'] === $referenceDateString;

            if ($total === 0) {
                // Skip days with no habits scheduled
                $tempStreak++;
                continue;
            }

            if ($completed >= $total) {
                $tempStreak++;
                $hasMetSuccess = true;
                $firstDay = false;
            } else {
                // If it's today and not completed yet, it doesn't break the streak (yet)
                if ($firstDay && $isToday) {
                    $firstDay = false;
                    continue;
                }

                break;
            }

            if ($hasMetSuccess) {
                $currentStreak = $tempStreak;
            }
        }

        return [
            'current_streak' => $currentStreak,
            'longest_streak' => $longestStreak,
        ];
    }

    /**
     * @return array<int, array{week_day: int, completed: int, total: int}>
     */
    private function fetchStats(int $userId, DateTimeImmutable $startDate, DateTimeImmutable $endDate): array
    {
        $sql = "
            WITH RECURSIVE dates AS (
                SELECT :start_date AS date
                UNION ALL
                SELECT date + INTERVAL 1 DAY
                FROM dates
                WHERE date < :end_date
            ),
            day_stats AS (
                SELECT 
                    d.date,
                    (WEEKDAY(d.date) + 1) % 7 as week_day,
                    COUNT(DISTINCT CASE WHEN dh.id IS NOT NULL THEN hwd.habit_id END) as completed,
                    COUNT(DISTINCT hwd.id) as total
                FROM dates d
                CROSS JOIN (SELECT :user_id as user_id) u
                LEFT JOIN habits h ON h.user_id = u.user_id 
                    AND DATE(h.created_at) <= d.date
                LEFT JOIN habit_week_days hwd ON hwd.habit_id = h.id 
                    AND hwd.week_day = (WEEKDAY(d.date) + 1) % 7
                LEFT JOIN days ds ON ds.date = d.date
                LEFT JOIN day_habits dh ON dh.day_id = ds.id AND dh.habit_id = hwd.habit_id
                GROUP BY d.date
            )
            SELECT 
                week_day,
                SUM(completed) as completed,
                SUM(total) as total
            FROM day_stats
            GROUP BY week_day
            ORDER BY week_day ASC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'user_id' => $userId,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
