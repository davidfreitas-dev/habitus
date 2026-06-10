<?php

declare(strict_types=1);

namespace Tests\Functional\Api\V1\Habits;

use App\Domain\Entity\Person;
use App\Domain\Entity\Role;
use App\Domain\Entity\User;
use App\Domain\Repository\PersonRepositoryInterface;
use App\Domain\Repository\RoleRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\Repository\HabitRepositoryInterface;
use App\Domain\ValueObject\CpfCnpj;
use Fig\Http\Message\StatusCodeInterface;
use Tests\Functional\FunctionalTestCase;
use Faker\Factory;

class CreateHabitTest extends FunctionalTestCase
{
    protected UserRepositoryInterface $userRepository;

    protected PersonRepositoryInterface $personRepository;

    protected RoleRepositoryInterface $roleRepository;

    protected HabitRepositoryInterface $habitRepository;

    protected \Faker\Generator $faker;

    protected ?User $testUser = null;

    protected string $accessToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userRepository = $this->app->getContainer()->get(UserRepositoryInterface::class);
        $this->personRepository = $this->app->getContainer()->get(PersonRepositoryInterface::class);
        $this->roleRepository = $this->app->getContainer()->get(RoleRepositoryInterface::class);
        $this->habitRepository = $this->app->getContainer()->get(HabitRepositoryInterface::class);
        $this->faker = Factory::create('pt_BR');

        $this->testUser = $this->createTestUser('testuser@example.com', $this->faker->cpf());
        $this->accessToken = $this->getAccessToken($this->testUser->getEmail(), 'password123');
    }

    protected function createTestUser(string $email, string $cpfcnpj): User
    {
        $password = 'password123';
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $person = new Person(
            name: $this->faker->name(),
            email: $email,
            cpfcnpj: CpfCnpj::fromString($cpfcnpj)
        );
        $person = $this->personRepository->create($person);

        $role = $this->roleRepository->findByName('user');
        if (!$role instanceof Role) {
            throw new \RuntimeException("Perfil 'user' não encontrado no banco de dados.");
        }

        $user = new User(
            person: $person,
            role: $role,
            password: $hashedPassword,
            isActive: true,
            isVerified: true
        );

        return $this->userRepository->create($user);
    }

    protected function getAccessToken(string $email, string $password): string
    {
        $response = $this->sendRequest('POST', '/api/v1/auth/login', [
            'email' => $email,
            'password' => $password,
        ]);

        $response->getBody()->rewind();
        $body = json_decode((string) $response->getBody(), true);

        if (!isset($body['data']['access_token'])) {
            throw new \RuntimeException(
                "Falha ao obter o token de acesso. Resposta: " . json_encode($body)
            );
        }

        return $body['data']['access_token'];
    }

    protected function getHabitFromDatabase(int $habitId): array|false
    {
        $pdo = $this->app->getContainer()->get(\PDO::class);
        $stmt = $pdo->prepare('SELECT * FROM habits WHERE id = :id');
        $stmt->execute(['id' => $habitId]);

        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    protected function getWeekDaysFromDatabase(int $habitId): array
    {
        $pdo = $this->app->getContainer()->get(\PDO::class);
        $stmt = $pdo->prepare('SELECT week_day FROM habit_week_days WHERE habit_id = :habit_id ORDER BY week_day ASC');
        $stmt->execute(['habit_id' => $habitId]);

        return array_map(intval(...), $stmt->fetchAll(\PDO::FETCH_COLUMN));
    }

    protected function createHabit(string $title, array $weekDays): int
    {
        $payload = [
            'title' => $title,
            'week_days' => $weekDays,
            'created_at' => new \DateTimeImmutable()->format('Y-m-d H:i:s'),
        ];

        $response = $this->sendRequest('POST', '/api/v1/habits', $payload, [
            'Authorization' => 'Bearer ' . $this->accessToken,
        ]);
        $response->getBody()->rewind();
        $body = json_decode((string) $response->getBody(), true);

        $this->assertEquals(StatusCodeInterface::STATUS_CREATED, $response->getStatusCode());
        $this->assertEquals('Hábito criado com sucesso.', $body['message']);
        $this->assertArrayHasKey('id', $body['data']);

        return $body['data']['id'];
    }

    protected function isHabitCompletedInDatabase(int $habitId, string $date): bool
    {
        $pdo = $this->app->getContainer()->get(\PDO::class);
        $stmt = $pdo->prepare('
            SELECT COUNT(*)
            FROM day_habits dh
            JOIN days d ON dh.day_id = d.id
            WHERE dh.habit_id = :habit_id AND d.date = :date
        ');
        $stmt->execute(['habit_id' => $habitId, 'date' => $date]);
        return (bool) $stmt->fetchColumn();
    }

    public function testCreateHabitReturnsCreated(): void
    {
        // Arrange
        $payload = [
            'title' => $this->faker->sentence(3),
            'week_days' => [1, 2, 3], // Monday, Tuesday, Wednesday
            'created_at' => new \DateTimeImmutable()->format('Y-m-d H:i:s'),
        ];

        // Act
        $response = $this->sendRequest('POST', '/api/v1/habits', $payload, [
            'Authorization' => 'Bearer ' . $this->accessToken,
        ]);

        $response->getBody()->rewind();
        $body = json_decode((string) $response->getBody(), true);

        // Assert
        $this->assertEquals(StatusCodeInterface::STATUS_CREATED, $response->getStatusCode());
        $this->assertEquals('success', $body['status']);
        $this->assertEquals('Hábito criado com sucesso.', $body['message']);
        $this->assertArrayHasKey('data', $body);
        $this->assertArrayHasKey('id', $body['data']);
        $this->assertArrayHasKey('title', $body['data']);
        $this->assertArrayHasKey('user_id', $body['data']);
        $this->assertArrayHasKey('week_days', $body['data']);

        $this->assertEquals($payload['title'], $body['data']['title']);
        $this->assertEquals($this->testUser->getId(), $body['data']['user_id']);
        $this->assertEquals($payload['week_days'], $body['data']['week_days']);

        // Verify in database
        $habitId = $body['data']['id'];
        $dbHabit = $this->getHabitFromDatabase($habitId);
        $this->assertNotFalse($dbHabit);
        $this->assertEquals($payload['title'], $dbHabit['title']);
        $this->assertEquals($this->testUser->getId(), $dbHabit['user_id']);

        $dbWeekDays = $this->getWeekDaysFromDatabase($habitId);
        $this->assertEquals($payload['week_days'], $dbWeekDays);
    }

    public function testCreateHabitWithCustomCreatedAt(): void
    {
        // Arrange
        $customDate = '2023-01-01 10:00:00';
        $payload = [
            'title' => 'Habit with custom date',
            'week_days' => [1, 2, 3],
            'created_at' => $customDate,
        ];

        // Act
        $response = $this->sendRequest('POST', '/api/v1/habits', $payload, [
            'Authorization' => 'Bearer ' . $this->accessToken,
        ]);

        $response->getBody()->rewind();
        $body = json_decode((string) $response->getBody(), true);

        // Assert
        $this->assertEquals(StatusCodeInterface::STATUS_CREATED, $response->getStatusCode());

        // Verify in database
        $habitId = $body['data']['id'];
        $dbHabit = $this->getHabitFromDatabase($habitId);
        $this->assertNotFalse($dbHabit);
        $this->assertEquals($customDate, $dbHabit['created_at']);
    }

    public function testCreateHabitWithoutAuthenticationReturnsUnauthorized(): void
    {
        // Arrange
        $payload = [
            'title' => 'Habit without auth',
            'week_days' => [1],
            'created_at' => new \DateTimeImmutable()->format('Y-m-d H:i:s'),
        ];

        // Act
        $response = $this->sendRequest('POST', '/api/v1/habits', $payload);

        // Assert
        $this->assertEquals(StatusCodeInterface::STATUS_UNAUTHORIZED, $response->getStatusCode());
    }

    public function testCreateHabitWithMissingTitleReturnsBadRequest(): void
    {
        // Arrange
        $payload = [
            'week_days' => [1, 2],
            'created_at' => new \DateTimeImmutable()->format('Y-m-d H:i:s'),
        ];

        // Act
        $response = $this->sendRequest('POST', '/api/v1/habits', $payload, [
            'Authorization' => 'Bearer ' . $this->accessToken,
        ]);

        // Assert
        $this->assertEquals(StatusCodeInterface::STATUS_BAD_REQUEST, $response->getStatusCode());
        $response->getBody()->rewind();
        $body = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey('data', $body);
        $this->assertContains('O título é obrigatório.', $body['data']);
    }

    public function testCreateHabitWithEmptyWeekDaysReturnsBadRequest(): void
    {
        // Arrange
        $payload = [
            'title' => 'Habit with empty week days',
            'week_days' => [],
            'created_at' => new \DateTimeImmutable()->format('Y-m-d H:i:s'),
        ];

        // Act
        $response = $this->sendRequest('POST', '/api/v1/habits', $payload, [
            'Authorization' => 'Bearer ' . $this->accessToken,
        ]);

        // Assert
        $this->assertEquals(StatusCodeInterface::STATUS_BAD_REQUEST, $response->getStatusCode());
        $response->getBody()->rewind();
        $body = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey('data', $body);
        $this->assertContains('Selecione ao menos um dia da semana.', $body['data']);
    }

    public function testCreateHabitWithInvalidWeekDayReturnsBadRequest(): void
    {
        // Arrange
        $payload = [
            'title' => 'Habit with invalid week day',
            'week_days' => [1, 8], // 8 is an invalid week day
            'created_at' => new \DateTimeImmutable()->format('Y-m-d H:i:s'),
        ];

        // Act
        $response = $this->sendRequest('POST', '/api/v1/habits', $payload, [
            'Authorization' => 'Bearer ' . $this->accessToken,
        ]);

        // Assert
        $this->assertEquals(StatusCodeInterface::STATUS_BAD_REQUEST, $response->getStatusCode());
        $response->getBody()->rewind();
        $body = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey('data', $body);
        $this->assertContains('O dia da semana deve ser entre 0 e 6.', $body['data']);
    }

    public function testCreateHabitWithMissingCreatedAtReturnsBadRequest(): void
    {
        // Arrange
        $payload = [
            'title' => 'Habit with missing created at',
            'week_days' => [1, 2],
        ];

        // Act
        $response = $this->sendRequest('POST', '/api/v1/habits', $payload, [
            'Authorization' => 'Bearer ' . $this->accessToken,
        ]);

        // Assert
        $this->assertEquals(StatusCodeInterface::STATUS_BAD_REQUEST, $response->getStatusCode());
        $response->getBody()->rewind();
        $body = json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey('data', $body);
        $this->assertContains('A data de criação é obrigatória.', $body['data']);
    }
}
