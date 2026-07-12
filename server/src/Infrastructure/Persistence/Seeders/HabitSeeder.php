<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Seeders;

use App\Domain\Entity\Habit;
use App\Domain\Repository\HabitRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;
use DateTimeImmutable;
use PDO;

class HabitSeeder
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly UserRepositoryInterface $userRepository,
        private readonly HabitRepositoryInterface $habitRepository,
    ) {
    }

    public function run(): void
    {
        $userId = 1; // Usuário alvo
        $user = $this->userRepository->findById($userId);

        if (!$user instanceof \App\Domain\Entity\User) {
            echo "User ID 1 not found. Skipping habit seeding.\n";
            return;
        }

        $habitsData = [
            [
                'id' => 100,
                'title' => 'Beber 2L de água',
                'reminder_time' => '08:00:00',
                'interval' => 'P35D',
                'week_days' => [0, 1, 2, 3, 4, 5, 6],
            ],
            [
                'id' => 101,
                'title' => 'Meditar',
                'reminder_time' => '07:00:00',
                'interval' => 'P35D',
                'week_days' => [1, 2, 3, 4, 5],
            ],
            [
                'id' => 102,
                'title' => 'Exercício físico',
                'reminder_time' => '06:30:00',
                'interval' => 'P35D',
                'week_days' => [1, 3, 5, 6],
            ],
            [
                'id' => 103,
                'title' => 'Ler 30 minutos',
                'reminder_time' => '21:00:00',
                'interval' => 'P35D',
                'week_days' => [0, 1, 2, 3, 4, 5, 6],
            ],
            [
                'id' => 104,
                'title' => 'Dormir antes das 23h',
                'reminder_time' => '22:30:00',
                'interval' => 'P30D',
                'week_days' => [1, 2, 3, 4],
            ],
            [
                'id' => 105,
                'title' => 'Sem redes sociais até 9h',
                'reminder_time' => null,
                'interval' => 'P20D',
                'week_days' => [1, 2, 3, 4, 5],
            ],
        ];

        $this->pdo->beginTransaction();
        try {
            foreach ($habitsData as $data) {
                // Verificar se o hábito já existe para não duplicar em re-execuções
                $existing = $this->habitRepository->findByTitle($data['title'], $userId);
                if ($existing instanceof \App\Domain\Entity\Habit) {
                    continue;
                }

                $createdAt = new DateTimeImmutable()->sub(new \DateInterval($data['interval']));

                $habit = new Habit(
                    title: $data['title'],
                    user: $user,
                    reminderTime: $data['reminder_time'],
                    createdAt: $createdAt,
                    updatedAt: new DateTimeImmutable(),
                );

                // Usamos o repositório para garantir que a tabela pivô habit_week_days também seja populada
                $this->habitRepository->create($habit, $data['week_days']);
            }

            $this->pdo->commit();
            echo "Specific habits for User 1 seeded successfully!\n";
        } catch (\Exception $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }
}
