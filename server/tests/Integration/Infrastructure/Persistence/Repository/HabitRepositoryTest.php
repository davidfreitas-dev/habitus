<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Persistence\Repository;

use DateTimeImmutable;
use App\Domain\Entity\User;
use App\Domain\Entity\Habit;
use App\Domain\Entity\Person;
use Tests\Integration\DatabaseTestCase;
use App\Infrastructure\Persistence\MySQL\RoleRepository;
use App\Infrastructure\Persistence\MySQL\UserRepository;
use App\Infrastructure\Persistence\MySQL\PersonRepository;
use App\Infrastructure\Persistence\Repository\HabitRepository;

class HabitRepositoryTest extends DatabaseTestCase
{
    private HabitRepository $habitRepository;

    private UserRepository $userRepository;

    private PersonRepository $personRepository;

    private RoleRepository $roleRepository;

    private ?User $testUser = null;

    private const string TEST_DATE_MONDAY = '2026-02-09';

    private const int SUNDAY = 0;

    private const int MONDAY = 1;

    private const int TUESDAY = 2;

    private const int WEDNESDAY = 3;

    private const array ALL_WEEK_DAYS = [0, 1, 2, 3, 4, 5, 6];

    protected function setUp(): void
    {
        parent::setUp();

        $this->personRepository = new PersonRepository(self::$pdo);
        $this->roleRepository = new RoleRepository(self::$pdo);
        $this->userRepository = new UserRepository(self::$pdo, $this->personRepository, $this->roleRepository);
        $this->habitRepository = new HabitRepository(self::$pdo, $this->userRepository);

        $this->testUser = $this->createTestUser('Test User', 'test@example.com');
    }

    public function testCreate(): void
    {
        $habit = new Habit('New Habit', $this->testUser);
        $createdHabit = $this->habitRepository->create($habit, [self::SUNDAY, self::MONDAY, self::TUESDAY]);

        $this->assertInstanceOf(Habit::class, $createdHabit);
        $this->assertNotNull($createdHabit->getId());
        $this->assertEquals('New Habit', $createdHabit->getTitle());
        $this->assertEquals($this->testUser->getId(), $createdHabit->getUser()->getId());
        $this->assertCount(3, $createdHabit->getHabitWeekDays());

        $data = $this->getHabitFromDatabase($createdHabit->getId());
        $this->assertNotFalse($data);
        $this->assertEquals('New Habit', $data['title']);

        $weekDays = $this->getWeekDaysFromDatabase($createdHabit->getId());
        $this->assertEquals([self::SUNDAY, self::MONDAY, self::TUESDAY], $weekDays);
    }

    public function testFindById(): void
    {
        $habit = new Habit('Find Me', $this->testUser);
        $createdHabit = $this->habitRepository->create($habit, [self::SUNDAY]);

        $foundHabit = $this->habitRepository->findById($createdHabit->getId(), $this->testUser->getId());

        $this->assertInstanceOf(Habit::class, $foundHabit);
        $this->assertEquals($createdHabit->getId(), $foundHabit->getId());
        $this->assertEquals('Find Me', $foundHabit->getTitle());
        $this->assertEquals($this->testUser->getId(), $foundHabit->getUser()->getId());
        $this->assertCount(1, $foundHabit->getHabitWeekDays());
        $this->assertEquals(self::SUNDAY, $foundHabit->getHabitWeekDays()->first()->getWeekDay());
    }

    public function testFindByIdNotFound(): void
    {
        $foundHabit = $this->habitRepository->findById(999, $this->testUser->getId());
        $this->assertNull($foundHabit);
    }

    public function testFindByIdDifferentUser(): void
    {
        $habit = new Habit('User Habit', $this->testUser);
        $createdHabit = $this->habitRepository->create($habit, [self::SUNDAY]);

        $otherUser = $this->createTestUser('Other User', 'other@example.com');

        $foundHabit = $this->habitRepository->findById($createdHabit->getId(), $otherUser->getId());

        $this->assertNull($foundHabit);
    }

    public function testFindByTitle(): void
    {
        $habit = new Habit('Unique Title', $this->testUser);
        $createdHabit = $this->habitRepository->create($habit, [self::MONDAY]);

        $foundHabit = $this->habitRepository->findByTitle('Unique Title', $this->testUser->getId());

        $this->assertInstanceOf(Habit::class, $foundHabit);
        $this->assertEquals($createdHabit->getId(), $foundHabit->getId());
        $this->assertEquals('Unique Title', $foundHabit->getTitle());
    }

    public function testFindByTitleNotFound(): void
    {
        $foundHabit = $this->habitRepository->findByTitle('Non Existent Title', $this->testUser->getId());
        $this->assertNull($foundHabit);
    }

    public function testUpdate(): void
    {
        $habit = new Habit('Original Title', $this->testUser);
        $createdHabit = $this->habitRepository->create($habit, [self::SUNDAY, self::MONDAY]);

        $createdHabit->setTitle('Updated Title');

        $updatedHabit = $this->habitRepository->update($createdHabit, [self::TUESDAY, self::WEDNESDAY, 4]);

        $this->assertInstanceOf(Habit::class, $updatedHabit);
        $this->assertEquals('Updated Title', $updatedHabit->getTitle());
        $this->assertNotEquals($createdHabit->getUpdatedAt(), $updatedHabit->getUpdatedAt());
        $this->assertInstanceOf(DateTimeImmutable::class, $updatedHabit->getUpdatedAt());
        $this->assertCount(3, $updatedHabit->getHabitWeekDays());

        $data = $this->getHabitFromDatabase($updatedHabit->getId());
        $this->assertNotFalse($data);
        $this->assertEquals('Updated Title', $data['title']);

        $weekDays = $this->getWeekDaysFromDatabase($updatedHabit->getId());
        $this->assertEquals([self::TUESDAY, self::WEDNESDAY, 4], $weekDays);
    }

    public function testDelete(): void
    {
        $habit = new Habit('Habit to Delete', $this->testUser);
        $createdHabit = $this->habitRepository->create($habit, [self::SUNDAY]);

        $this->habitRepository->delete($createdHabit->getId(), $this->testUser->getId());

        $data = $this->getHabitFromDatabase($createdHabit->getId());
        $this->assertFalse($data);

        $stmt = self::$pdo->prepare('SELECT * FROM habit_week_days WHERE habit_id = :habit_id');
        $stmt->execute(['habit_id' => $createdHabit->getId()]);
        $this->assertFalse($stmt->fetch(\PDO::FETCH_ASSOC));
    }

    public function testFindPossibleHabits(): void
    {
        $date = new DateTimeImmutable(self::TEST_DATE_MONDAY);

        $habit1 = new Habit('Daily Habit', $this->testUser, null, new DateTimeImmutable('2026-02-08'), new DateTimeImmutable('2026-02-08'));
        $this->habitRepository->create($habit1, self::ALL_WEEK_DAYS);

        $habit2 = new Habit('Monday Habit', $this->testUser, null, new DateTimeImmutable('2026-02-08'), new DateTimeImmutable('2026-02-08'));
        $this->habitRepository->create($habit2, [self::MONDAY]);

        $habit3 = new Habit('Tuesday Habit', $this->testUser, null, new DateTimeImmutable('2026-02-08'), new DateTimeImmutable('2026-02-08'));
        $this->habitRepository->create($habit3, [self::TUESDAY]);

        $futureHabitDate = new DateTimeImmutable('2026-02-10');
        $habit4 = new Habit('Future Habit', $this->testUser, null, $futureHabitDate, $futureHabitDate);
        $this->habitRepository->create($habit4, [self::MONDAY]);

        $possibleHabits = $this->habitRepository->findPossibleHabits($date, $this->testUser->getId());

        $this->assertCount(2, $possibleHabits);
        $this->assertEquals('Daily Habit', $possibleHabits[0]->getTitle());
        $this->assertEquals('Monday Habit', $possibleHabits[1]->getTitle());
    }

    public function testFindCompletedHabits(): void
    {
        $date = new DateTimeImmutable(self::TEST_DATE_MONDAY);
        $dayId = $this->ensureDayExists($date);

        $habit1 = new Habit('Completed Habit 1', $this->testUser, null, new DateTimeImmutable('2026-02-08'), new DateTimeImmutable('2026-02-08'));
        $createdHabit1 = $this->habitRepository->create($habit1, [self::SUNDAY]);

        $habit2 = new Habit('Completed Habit 2', $this->testUser, null, new DateTimeImmutable('2026-02-08'), new DateTimeImmutable('2026-02-08'));
        $createdHabit2 = $this->habitRepository->create($habit2, [self::SUNDAY]);

        $habit3 = new Habit('Uncompleted Habit', $this->testUser, null, new DateTimeImmutable('2026-02-08'), new DateTimeImmutable('2026-02-08'));
        $this->habitRepository->create($habit3, [self::SUNDAY]);

        $this->markHabitAsCompleted($dayId, $createdHabit1->getId());
        $this->markHabitAsCompleted($dayId, $createdHabit2->getId());

        $completedHabits = $this->habitRepository->findCompletedHabits($date, $this->testUser->getId());

        $this->assertCount(2, $completedHabits);
        $this->assertEquals('Completed Habit 1', $completedHabits[0]->getTitle());
        $this->assertEquals('Completed Habit 2', $completedHabits[1]->getTitle());
    }

    public function testGetHabitsSummary(): void
    {
        // Ensure a user exists (already done in setUp)
        $userId = $this->testUser->getId();

        // Create some habits that should appear in the summary
        // Habit 1: Daily habit, completed on a specific day
        $habit1 = new Habit('Daily Habit', $this->testUser, null, new DateTimeImmutable('2026-02-08'), new DateTimeImmutable('2026-02-08'));
        $createdHabit1 = $this->habitRepository->create($habit1, self::ALL_WEEK_DAYS);

        // Habit 2: Monday habit
        $habit2 = new Habit('Monday Habit', $this->testUser, null, new DateTimeImmutable('2026-02-08'), new DateTimeImmutable('2026-02-08'));
        $this->habitRepository->create($habit2, [self::MONDAY]);

        // Mark habit1 as completed on TEST_DATE_MONDAY
        $dateForCompletion = new DateTimeImmutable(self::TEST_DATE_MONDAY);
        $dayId = $this->ensureDayExists($dateForCompletion);
        $this->markHabitAsCompleted($dayId, $createdHabit1->getId());

        // Get the summary for the user
        $summary = $this->habitRepository->getHabitsSummary($userId);

        // Assert that the summary is an array and not empty
        $this->assertIsArray($summary);
        $this->assertNotEmpty($summary);
        $mondaySummary = array_find($summary, fn($item): bool => $item['date'] === self::TEST_DATE_MONDAY);

        // Assert that the summary for TEST_DATE_MONDAY is found and has expected values
        $this->assertNotNull($mondaySummary, "Summary for " . self::TEST_DATE_MONDAY . " not found.");
        $this->assertEquals(1, $mondaySummary['completed']);
        // Daily habit (habit1) + Monday habit (habit2) = 2 total possible habits
        // This relies on the current date logic of the getHabitsSummary method
        $this->assertEquals(2, $mondaySummary['total']);
    }

    private function createTestUser(string $name, string $email): User
    {
        $person = new Person(name: $name, email: $email);
        $createdPerson = $this->personRepository->create($person);
        $fetchedPerson = $this->personRepository->findByEmail($createdPerson->getEmail());

        $customerRole = $this->roleRepository->findByName('customer');
        if (!$customerRole instanceof \App\Domain\Entity\Role) {
            throw new \RuntimeException("Perfil 'customer' não encontrada no seed do banco de dados.");
        }

        $user = new User(person: $fetchedPerson, role: $customerRole, password: 'password');
        $this->userRepository->create($user);

        $fetchedUser = $this->userRepository->findByEmail($email);
        if (!$fetchedUser instanceof \App\Domain\Entity\User) {
            throw new \RuntimeException("Usuário não pôde ser criado ou encontrado.");
        }

        return $fetchedUser;
    }

    private function ensureDayExists(DateTimeImmutable $date): int
    {
        $stmt = self::$pdo->prepare('INSERT IGNORE INTO days (date) VALUES (:date)');
        $stmt->execute(['date' => $date->format('Y-m-d')]);

        $stmt = self::$pdo->prepare('SELECT id FROM days WHERE date = :date');
        $stmt->execute(['date' => $date->format('Y-m-d')]);

        return (int)$stmt->fetchColumn();
    }

    private function markHabitAsCompleted(int $dayId, int $habitId): void
    {
        $stmt = self::$pdo->prepare('INSERT INTO day_habits (day_id, habit_id) VALUES (:day_id, :habit_id)');
        $stmt->execute(['day_id' => $dayId, 'habit_id' => $habitId]);
    }

    private function getHabitFromDatabase(int $habitId): array|false
    {
        $stmt = self::$pdo->prepare('SELECT * FROM habits WHERE id = :id');
        $stmt->execute(['id' => $habitId]);

        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    private function getWeekDaysFromDatabase(int $habitId): array
    {
        $stmt = self::$pdo->prepare('SELECT week_day FROM habit_week_days WHERE habit_id = :habit_id ORDER BY week_day ASC');
        $stmt->execute(['habit_id' => $habitId]);

        return array_map(intval(...), $stmt->fetchAll(\PDO::FETCH_COLUMN));
    }
}