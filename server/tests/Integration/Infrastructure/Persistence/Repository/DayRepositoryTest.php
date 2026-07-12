<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Persistence\Repository;

use DateTimeImmutable;
use App\Domain\Entity\Day;
use Tests\Integration\DatabaseTestCase;
use App\Infrastructure\Persistence\Repository\DayRepository;
use App\Infrastructure\Persistence\MySQL\RoleRepository;
use App\Infrastructure\Persistence\MySQL\UserRepository;
use App\Infrastructure\Persistence\MySQL\PersonRepository;
use App\Domain\Entity\Role;
use App\Domain\Entity\User;
use App\Domain\Entity\Person;

class DayRepositoryTest extends DatabaseTestCase
{
    private DayRepository $dayRepository;

    private UserRepository $userRepository;

    private PersonRepository $personRepository;

    private RoleRepository $roleRepository;

    private ?User $testUser = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->personRepository = new PersonRepository(self::$pdo);
        $this->roleRepository = new RoleRepository(self::$pdo);
        $this->userRepository = new UserRepository(self::$pdo, $this->personRepository, $this->roleRepository);
        $this->dayRepository = new DayRepository(self::$pdo, $this->userRepository);

        $this->testUser = $this->createTestUser('Test User', 'test@example.com');
    }

    public function testCreate(): void
    {
        $date = new DateTimeImmutable('2026-02-04');
        $createdDay = $this->dayRepository->create($date);

        $this->assertInstanceOf(Day::class, $createdDay);
        $this->assertNotNull($createdDay->getId());
        $this->assertEquals($date->format('Y-m-d'), $createdDay->getDate()->format('Y-m-d'));

        $data = $this->getDayFromDatabase($createdDay->getId());
        $this->assertNotFalse($data);
        $this->assertEquals($date->format('Y-m-d'), $data['date']);
    }

    public function testCreateDuplicateDayReturnsExistingDay(): void
    {
        $date = new DateTimeImmutable('2026-02-05');
        $firstCreate = $this->dayRepository->create($date);
        $secondCreate = $this->dayRepository->create($date);

        $this->assertEquals($firstCreate->getId(), $secondCreate->getId());
        $this->assertEquals($firstCreate->getDate()->format('Y-m-d'), $secondCreate->getDate()->format('Y-m-d'));
    }

    public function testFindOneByDate(): void
    {
        $date = new DateTimeImmutable('2026-02-06');
        $createdDay = $this->dayRepository->create($date);

        $foundDay = $this->dayRepository->findOneByDate($date);

        $this->assertInstanceOf(Day::class, $foundDay);
        $this->assertEquals($createdDay->getId(), $foundDay->getId());
        $this->assertEquals($date->format('Y-m-d'), $foundDay->getDate()->format('Y-m-d'));
    }

    public function testFindOneByDateNotFound(): void
    {
        $date = new DateTimeImmutable('2026-02-07');
        $foundDay = $this->dayRepository->findOneByDate($date);
        $this->assertNull($foundDay);
    }

    public function testFindCompletedHabitIdsByDate(): void
    {
        $date = new DateTimeImmutable('2026-02-08');
        $day = $this->dayRepository->create($date);

        $habit1 = $this->createTestHabit('Habit 1', $this->testUser, [1]); //monday
        $habit2 = $this->createTestHabit('Habit 2', $this->testUser, [1]); //monday
        $this->createTestHabit('Habit 3', $this->testUser, [1]); //monday for other user

        $otherUser = $this->createTestUser('Other User', 'otheruser@example.com');
        $this->createTestHabit('Habit for Other User', $otherUser, [1]);

        $this->markHabitAsCompleted($day->getId(), $habit1->getId());
        $this->markHabitAsCompleted($day->getId(), $habit2->getId());

        $completedHabitIds = $this->dayRepository->findCompletedHabitIdsByDate($this->testUser->getId(), $date);

        $this->assertCount(2, $completedHabitIds);
        $this->assertContains($habit1->getId(), $completedHabitIds);
        $this->assertContains($habit2->getId(), $completedHabitIds);
        $this->assertNotContains($this->createTestHabit('Habit 3 for user', $this->testUser, [2])->getId(), $completedHabitIds);
    }

    public function testFindCompletedHabitIdsByDateNoCompletedHabits(): void
    {
        $date = new DateTimeImmutable('2026-02-09');
        $this->dayRepository->create($date);

        $completedHabitIds = $this->dayRepository->findCompletedHabitIdsByDate($this->testUser->getId(), $date);
        $this->assertEmpty($completedHabitIds);
    }

    private function getDayFromDatabase(int $dayId): array|false
    {
        $stmt = self::$pdo->prepare('SELECT * FROM days WHERE id = :id');
        $stmt->execute(['id' => $dayId]);

        return $stmt->fetch(\PDO::FETCH_ASSOC);
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

    private function createTestHabit(string $title, User $user, array $weekDays): \App\Domain\Entity\Habit
    {
        $habitRepository = new \App\Infrastructure\Persistence\Repository\HabitRepository(self::$pdo, $this->userRepository);
        $habit = new \App\Domain\Entity\Habit($title, $user);
        return $habitRepository->create($habit, $weekDays);
    }

    private function markHabitAsCompleted(int $dayId, int $habitId): void
    {
        $stmt = self::$pdo->prepare('INSERT INTO day_habits (day_id, habit_id) VALUES (:day_id, :habit_id)');
        $stmt->execute(['day_id' => $dayId, 'habit_id' => $habitId]);
    }
}
