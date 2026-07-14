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

class GetHabitsByDayTest extends FunctionalTestCase
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

    public function testGetHabitsByDayReturnsOk(): void
    {
        // Arrange
        $today = new \DateTimeImmutable();
        $currentWeekDay = (int) $today->format('w'); // 0 for Sunday, 6 for Saturday
        $todayFormatted = $today->format('Y-m-d');

        // Create a habit for the current day
        $habitId = $this->createHabit($this->faker->sentence(3), [$currentWeekDay]);

        // Toggle the habit for today
        $toggleResponse = $this->sendRequest(
            'PATCH',
            sprintf('/api/v1/habits/%d/toggle', $habitId),
            ['date' => $today->format('Y-m-d')],
            ['Authorization' => 'Bearer ' . $this->accessToken]
        );
        $this->assertEquals(StatusCodeInterface::STATUS_OK, $toggleResponse->getStatusCode());

        $payload = [
            'date' => $today->format('Y-m-d'),
        ];

        // Act
        $response = $this->sendRequest('GET', '/api/v1/habits/day?date=' . $payload['date'], [], [
            'Authorization' => 'Bearer ' . $this->accessToken,
        ]);

        $response->getBody()->rewind();
        $body = json_decode((string) $response->getBody(), true);

        // Assert
        $this->assertEquals(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        $this->assertEquals('success', $body['status']);
        $this->assertEquals('Hábitos do dia obtidos com sucesso.', $body['message']);
        $this->assertArrayHasKey('data', $body);
        $this->assertIsArray($body['data']);
        $this->assertArrayHasKey('possible_habits', $body['data']);
        $this->assertArrayHasKey('completed_habits', $body['data']);

        // Check for the created habit in possible_habits
        $foundInPossible = false;
        foreach ($body['data']['possible_habits'] as $habit) {
            if ($habit['id'] === $habitId) {
                $foundInPossible = true;
                break;
            }
        }

        $this->assertTrue($foundInPossible, 'Created habit not found in the possible habits list.');

        // Check for the created habit in completed_habits
        $foundInCompleted = false;
        foreach ($body['data']['completed_habits'] as $habit) {
            if ($habit['id'] === $habitId) {
                $this->assertEquals($this->testUser->getId(), $habit['user_id']);
                // The presence of the habit in this list implies it is completed.
                $foundInCompleted = true;
                break;
            }
        }

        $this->assertTrue($foundInCompleted, 'Created habit not found in the completed habits list.');
    }
}