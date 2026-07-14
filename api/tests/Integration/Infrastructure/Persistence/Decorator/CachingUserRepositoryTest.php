<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Persistence\Decorator;

use App\Domain\Entity\Person;
use App\Domain\Entity\User;
use App\Domain\Entity\Role;
use App\Domain\ValueObject\CpfCnpj;
use App\Infrastructure\Persistence\Decorator\CachingUserRepository;
use App\Infrastructure\Persistence\MySQL\PersonRepository;
use App\Infrastructure\Persistence\MySQL\RoleRepository;
use App\Infrastructure\Persistence\MySQL\UserRepository;
use App\Infrastructure\Persistence\Redis\RedisCache;
use Faker\Factory;
use Monolog\Handler\NullHandler;
use Monolog\Logger;
use Redis;
use Tests\Integration\DatabaseTestCase;

class CachingUserRepositoryTest extends DatabaseTestCase
{
    private CachingUserRepository $cachingUserRepository;

    private UserRepository $userRepository;

    private PersonRepository $personRepository;

    private RoleRepository $roleRepository;

    private RedisCache $redisCache;

    private \Faker\Generator $faker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->personRepository = new PersonRepository(self::$pdo);
        $this->roleRepository = new RoleRepository(self::$pdo);
        $this->userRepository = new UserRepository(
            self::$pdo,
            $this->personRepository,
            $this->roleRepository
        );

        // Criar conexão Redis para testes
        $redis = new Redis();
        $redis->connect(
            $_ENV['REDIS_HOST'] ?? 'redis',
            (int)($_ENV['REDIS_PORT'] ?? 6379)
        );

        if (!empty($_ENV['REDIS_PASSWORD'])) {
            $redis->auth($_ENV['REDIS_PASSWORD']);
        }

        if (!empty($_ENV['REDIS_DATABASE'])) {
            $redis->select((int)$_ENV['REDIS_DATABASE']);
        }

        $this->redisCache = new RedisCache($redis);

        $logger = new Logger('test');
        $logger->pushHandler(new NullHandler());

        $this->cachingUserRepository = new CachingUserRepository(
            $this->userRepository,
            $this->redisCache,
            $logger
        );

        $this->faker = Factory::create('pt_BR');
    }

    private function createTestUser(int $roleId = 1): User
    {
        // Retrieve the role by ID, which is seeded by DatabaseTestCase
        $role = $this->roleRepository->findById($roleId);

        if (!$role instanceof Role) {
            throw new \RuntimeException(sprintf('Role with ID %d not found in test database. Check DatabaseTestCase seeding.', $roleId));
        }

        $person = new Person(
            name: $this->faker->name,
            email: $this->faker->unique()->email,
            cpfcnpj: CpfCnpj::fromString($this->faker->unique()->cpf)
        );
        $this->personRepository->create($person);

        $user = new User(
            person: $person,
            role: $role,
            password: 'password123'
        );

        return $this->userRepository->create($user);
    }

    public function testFindByIdCachesUser(): void
    {
        $user = $this->createTestUser();
        $userId = $user->getId();
        $cacheKey = 'user:id:' . $userId;

        // First call, should miss cache and store it
        $foundUser = $this->cachingUserRepository->findById($userId);

        $this->assertNotNull($foundUser);
        $this->assertNotNull(
            $this->redisCache->get($cacheKey),
            'O usuário deve estar no cache após findById'
        );

        // Mock the decorated repository to ensure the next call hits the cache
        $mockedRepo = $this->createMock(UserRepository::class);
        $mockedRepo->expects($this->never())->method('findById');

        $cachingRepoWithMock = new CachingUserRepository(
            $mockedRepo,
            $this->redisCache,
            new Logger('test', [new NullHandler()])
        );

        // Second call, should hit cache
        $cachedUser = $cachingRepoWithMock->findById($userId);
        $this->assertNotNull($cachedUser);
        $this->assertEquals($user->getEmail(), $cachedUser->getEmail());
    }

    public function testFindByEmailCachesUser(): void
    {
        $user = $this->createTestUser();
        $userEmail = $user->getEmail();
        $cacheKey = 'user:email:' . $userEmail;

        // First call, should miss cache and store it
        $foundUser = $this->cachingUserRepository->findByEmail($userEmail);

        $this->assertNotNull($foundUser);
        $this->assertNotNull(
            $this->redisCache->get($cacheKey),
            'O usuário deve estar no cache após findByEmail'
        );

        // Mock the decorated repository to ensure the next call hits the cache
        $mockedRepo = $this->createMock(UserRepository::class);
        $mockedRepo->expects($this->never())->method('findByEmail');

        $cachingRepoWithMock = new CachingUserRepository(
            $mockedRepo,
            $this->redisCache,
            new Logger('test', [new NullHandler()])
        );

        // Second call, should hit cache
        $cachedUser = $cachingRepoWithMock->findByEmail($userEmail);
        $this->assertNotNull($cachedUser);
        $this->assertEquals($user->getId(), $cachedUser->getId());
    }

    public function testUpdateInvalidatesAndUpdatesCache(): void
    {
        $user = $this->createTestUser();
        $userId = $user->getId();
        $oldEmail = $user->getEmail();

        // Prime the cache
        $this->cachingUserRepository->findById($userId);
        $this->cachingUserRepository->findByEmail($oldEmail);

        $this->assertNotNull($this->redisCache->get('user:id:' . $userId));
        $this->assertNotNull($this->redisCache->get('user:email:' . $oldEmail));

        // Update user
        $user->getPerson()->setName('New Name');
        $user->getPerson()->setEmail($this->faker->unique()->email);
        $this->cachingUserRepository->update($user);

        // Check old caches are invalidated
        $this->assertNull(
            $this->redisCache->get('user:email:' . $oldEmail),
            'O cache de e-mail antigo deve ser invalidado'
        );

        // Check new caches are created
        $this->assertNotNull(
            $this->redisCache->get('user:id:' . $userId),
            'O cache de ID deve ser atualizado'
        );
        $this->assertNotNull(
            $this->redisCache->get('user:email:' . $user->getEmail()),
            'Um novo cache de e-mail deve ser criado'
        );
    }

    public function testDeleteInvalidatesCache(): void
    {
        $user = $this->createTestUser();
        $userId = $user->getId();
        $userEmail = $user->getEmail();

        // Prime the cache
        $this->cachingUserRepository->findById($userId);

        $this->assertNotNull($this->redisCache->get('user:id:' . $userId));
        $this->assertNotNull($this->redisCache->get('user:email:' . $userEmail));

        // Delete user
        $this->cachingUserRepository->delete($userId);

        // Check caches are invalidated
        $this->assertNull(
            $this->redisCache->get('user:id:' . $userId),
            'O cache de ID deve ser invalidado após a exclusão'
        );
        $this->assertNull(
            $this->redisCache->get('user:email:' . $userEmail),
            'O cache de e-mail deve ser invalidado após a exclusão'
        );
    }
}