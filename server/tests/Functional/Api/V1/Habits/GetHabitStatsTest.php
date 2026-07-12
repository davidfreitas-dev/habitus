<?php

declare(strict_types=1);

namespace Tests\Functional\Api\V1\Habits;

use App\Domain\Entity\Person;
use App\Domain\Entity\Role;
use App\Domain\Entity\User;
use App\Domain\Repository\PersonRepositoryInterface;
use App\Domain\Repository\RoleRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\ValueObject\CpfCnpj;
use Fig\Http\Message\StatusCodeInterface;
use Tests\Functional\FunctionalTestCase;
use Faker\Factory;
use DateTimeImmutable;

class GetHabitStatsTest extends FunctionalTestCase
{
    protected UserRepositoryInterface $userRepository;

    protected PersonRepositoryInterface $personRepository;

    protected RoleRepositoryInterface $roleRepository;

    protected \Faker\Generator $faker;

    protected ?User $testUser = null;

    protected string $accessToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userRepository = $this->app->getContainer()->get(UserRepositoryInterface::class);
        $this->personRepository = $this->app->getContainer()->get(PersonRepositoryInterface::class);
        $this->roleRepository = $this->app->getContainer()->get(RoleRepositoryInterface::class);
        $this->faker = Factory::create('pt_BR');

        $this->testUser = $this->createTestUser('testuser_stats@example.com', $this->faker->cpf());
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

        return $body['data']['access_token'];
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

        if (!isset($body['data']['id'])) {
            throw new \RuntimeException('Failed to create habit: ' . json_encode($body));
        }

        return $body['data']['id'];
    }

    public function testGetStatsReturnsOk(): void
    {
        // Arrange
        // Create a habit that is active every day
        $habitId = $this->createHabit('Stats Habit', [0, 1, 2, 3, 4, 5, 6]);

        $today = new DateTimeImmutable();
        $todayFormatted = $today->format('Y-m-d');

        // Toggle habit for today
        $this->sendRequest(
            'PATCH',
            sprintf('/api/v1/habits/%d/toggle', $habitId),
            ['date' => $todayFormatted],
            ['Authorization' => 'Bearer ' . $this->accessToken]
        );

        // Act
        $response = $this->sendRequest('GET', '/api/v1/habits/stats', ['period' => 'W'], [
            'Authorization' => 'Bearer ' . $this->accessToken,
        ]);

        $response->getBody()->rewind();
        $body = json_decode((string) $response->getBody(), true);

        // Assert
        $this->assertEquals(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        $this->assertEquals('success', $body['status']);
        $this->assertEquals('Estatísticas obtidas com sucesso.', $body['message']);

        $this->assertIsArray($body['data']['daily_stats']);
        $this->assertCount(7, $body['data']['daily_stats']);
        $this->assertArrayHasKey('current_streak', $body['data']);
        $this->assertArrayHasKey('longest_streak', $body['data']);

        // Check if today's weekday has at least one completed and one total
        $todayWeekDay = (int) $today->format('w');
        $foundToday = false;
        foreach ($body['data']['daily_stats'] as $stat) {
            if ($stat['week_day'] === $todayWeekDay) {
                $this->assertGreaterThanOrEqual(1, $stat['total']);
                $this->assertGreaterThanOrEqual(1, $stat['completed']);
                $this->assertNotNull($stat['percentage']);
                $foundToday = true;
                break;
            }
        }

        $this->assertTrue($foundToday);
    }

    public function testGetStatsWithDifferentPeriods(): void
    {
        $periods = ['W', 'M', '3M', '6M', 'Y'];

        foreach ($periods as $period) {
            $response = $this->sendRequest('GET', '/api/v1/habits/stats', ['period' => $period], [
                'Authorization' => 'Bearer ' . $this->accessToken,
            ]);

            $this->assertEquals(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
            $response->getBody()->rewind();
            $body = json_decode((string) $response->getBody(), true);
            $this->assertIsArray($body['data']['daily_stats']);
        }
    }
}
