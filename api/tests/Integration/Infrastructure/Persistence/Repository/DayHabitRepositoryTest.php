<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Persistence\Repository;

use DateTimeImmutable;
use App\Domain\Entity\Day;
use App\Domain\Entity\Habit;
use App\Domain\Entity\Person;
use App\Domain\Entity\Role;
use App\Domain\Entity\User;
use Tests\Integration\DatabaseTestCase;
use App\Infrastructure\Persistence\MySQL\PersonRepository;
use App\Infrastructure\Persistence\MySQL\RoleRepository;
use App\Infrastructure\Persistence\MySQL\UserRepository;
use App\Infrastructure\Persistence\Repository\DayHabitRepository;
use App\Infrastructure\Persistence\Repository\DayRepository;
use App\Infrastructure\Persistence\Repository\HabitRepository;

class DayHabitRepositoryTest extends DatabaseTestCase
{
    private DayHabitRepository $dayHabitRepository;

    private DayRepository $dayRepository;

    private HabitRepository $habitRepository;

    private UserRepository $userRepository;

    private PersonRepository $personRepository;

    private RoleRepository $roleRepository;

    private ?User $testUser = null;

    private ?User $otherUser = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->personRepository = new PersonRepository(self::$pdo);
        $this->roleRepository = new RoleRepository(self::$pdo);
        $this->userRepository = new UserRepository(self::$pdo, $this->personRepository, $this->roleRepository);
        $this->dayRepository = new DayRepository(self::$pdo, $this->userRepository);
        $this->habitRepository = new HabitRepository(self::$pdo, $this->userRepository);
        $this->dayHabitRepository = new DayHabitRepository(self::$pdo);

        $this->testUser = $this->createTestUser('Test User', 'test@example.com');
        $this->otherUser = $this->createTestUser('Other User', 'other@example.com');
    }

    public function testToggleCompletesHabit(): void
    {
        $date = new DateTimeImmutable('2026-02-10');
        $day = $this->dayRepository->create($date);
        $habit = $this->createTestHabit('Test Toggle Habit', $this->testUser, [1]);

        $isCompleted = $this->dayHabitRepository->toggle($day->getId(), $habit->getId(), $this->testUser->getId());

        $this->assertTrue($isCompleted);
        $this->assertTrue($this->isHabitMarkedAsCompletedInDb($day->getId(), $habit->getId()));
    }

    public function testToggleUncompletesHabit(): void
    {
        $date = new DateTimeImmutable('2026-02-11');
        $day = $this->dayRepository->create($date);
        $habit = $this->createTestHabit('Test Uncomplete Habit', $this->testUser, [2]);

        // First, complete the habit
        $this->dayHabitRepository->toggle($day->getId(), $habit->getId(), $this->testUser->getId());
        $this->assertTrue($this->isHabitMarkedAsCompletedInDb($day->getId(), $habit->getId()));

        // Then, uncomplete it
        $isCompleted = $this->dayHabitRepository->toggle($day->getId(), $habit->getId(), $this->testUser->getId());

        $this->assertFalse($isCompleted);
        $this->assertFalse($this->isHabitMarkedAsCompletedInDb($day->getId(), $habit->getId()));
    }

    public function testToggleReturnsFalseForHabitOfAnotherUser(): void
    {
        $date = new DateTimeImmutable('2026-02-12');
        $day = $this->dayRepository->create($date);
        $habit = $this->createTestHabit('Other User Habit', $this->otherUser, [3]);

        // Attempt to toggle a habit owned by 'otherUser' with 'testUser'
        $isCompleted = $this->dayHabitRepository->toggle($day->getId(), $habit->getId(), $this->testUser->getId());

        $this->assertFalse($isCompleted);
        $this->assertFalse($this->isHabitMarkedAsCompletedInDb($day->getId(), $habit->getId()));
    }

    public function testIsCompletedReturnsTrueWhenCompleted(): void
    {
        $date = new DateTimeImmutable('2026-02-13');
        $day = $this->dayRepository->create($date);
        $habit = $this->createTestHabit('Is Completed Habit', $this->testUser, [4]);

        $this->dayHabitRepository->toggle($day->getId(), $habit->getId(), $this->testUser->getId()); // Complete it

        $isCompleted = $this->dayHabitRepository->isCompleted($day->getId(), $habit->getId(), $this->testUser->getId());
        $this->assertTrue($isCompleted);
    }

    public function testIsCompletedReturnsFalseWhenNotCompleted(): void
    {
        $date = new DateTimeImmutable('2026-02-14');
        $day = $this->dayRepository->create($date);
        $habit = $this->createTestHabit('Not Completed Habit', $this->testUser, [5]);

        $isCompleted = $this->dayHabitRepository->isCompleted($day->getId(), $habit->getId(), $this->testUser->getId());
        $this->assertFalse($isCompleted);
    }

    public function testIsCompletedReturnsFalseForHabitOfAnotherUser(): void
    {
        $date = new DateTimeImmutable('2026-02-15');
        $day = $this->dayRepository->create($date);
        $habit = $this->createTestHabit('Another User Habit', $this->otherUser, [6]);

        // Complete the habit with the owner user
        $this->dayHabitRepository->toggle($day->getId(), $habit->getId(), $this->otherUser->getId());
        $this->assertTrue($this->isHabitMarkedAsCompletedInDb($day->getId(), $habit->getId()));

        // Check completion with a different user
        $isCompleted = $this->dayHabitRepository->isCompleted($day->getId(), $habit->getId(), $this->testUser->getId());
        $this->assertFalse($isCompleted);
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

    private function createTestHabit(string $title, User $user, array $weekDays): Habit
    {
        $habit = new Habit($title, $user);
        return $this->habitRepository->create($habit, $weekDays);
    }

    private function isHabitMarkedAsCompletedInDb(int $dayId, int $habitId): bool
    {
        $stmt = self::$pdo->prepare('SELECT 1 FROM day_habits WHERE day_id = :day_id AND habit_id = :habit_id');
        $stmt->execute(['day_id' => $dayId, 'habit_id' => $habitId]);
        return (bool) $stmt->fetchColumn();
    }
}
