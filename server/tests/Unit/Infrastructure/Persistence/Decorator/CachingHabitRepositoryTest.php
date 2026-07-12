<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Persistence\Decorator;

use App\Domain\Entity\Habit;
use App\Domain\Entity\User;
use App\Domain\Repository\HabitRepositoryInterface;
use App\Infrastructure\Persistence\Decorator\CachingHabitRepository;
use App\Infrastructure\Persistence\Redis\RedisCache;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class CachingHabitRepositoryTest extends TestCase
{
    private HabitRepositoryInterface&MockObject $decoratedRepository;

    private RedisCache&MockObject $redisCache;

    private LoggerInterface&MockObject $logger;

    private CachingHabitRepository $cachingHabitRepository;

    private int $userId;

    private User&MockObject $user;

    private int $cacheTtl;

    protected function setUp(): void
    {
        parent::setUp();

        $this->decoratedRepository = $this->createMock(HabitRepositoryInterface::class);
        $this->redisCache = $this->createMock(RedisCache::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->userId = 1;

        // Mock User entity and its dependencies
        /** @var \App\Domain\Entity\Person&MockObject $personMock */
        $personMock = $this->createMock(\App\Domain\Entity\Person::class);

        /** @var \App\Domain\Entity\Role&MockObject $roleMock */
        $roleMock = $this->createMock(\App\Domain\Entity\Role::class);

        $this->user = $this->createMock(User::class);
        $this->user->method('getId')->willReturn($this->userId);
        $this->user->method('getPerson')->willReturn($personMock);
        $this->user->method('getRole')->willReturn($roleMock);
        $this->user->method('isActive')->willReturn(true);
        $this->user->method('isVerified')->willReturn(true);
        $this->user->method('getCreatedAt')->willReturn(new DateTimeImmutable());
        $this->user->method('getUpdatedAt')->willReturn(new DateTimeImmutable());
        $this->user->method('getPassword')->willReturn('hashed_password');

        $this->cacheTtl = 3600;

        $this->cachingHabitRepository = new CachingHabitRepository(
            $this->decoratedRepository,
            $this->redisCache,
            $this->logger,
        );
    }

    private function createHabit(int $id, string $title): Habit
    {
        $habit = new Habit($title, $this->user);
        $reflection = new \ReflectionClass($habit);
        $property = $reflection->getProperty('id');
        $property->setValue($habit, $id);
        return $habit;
    }

    public function testFindByIdReturnsCachedHabit(): void
    {
        $habitId = 1;
        $habit = $this->createHabit($habitId, 'Read a book');
        $serializedHabit = serialize($habit);
        $cacheKey = 'habit:id:' . $habitId . ':' . $this->userId;

        $this->redisCache->expects($this->once())
            ->method('get')
            ->with($cacheKey)
            ->willReturn($serializedHabit);

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Cache de hábito encontrado para ID: ' . $habitId);

        $this->decoratedRepository->expects($this->never())
            ->method('findById');

        $this->redisCache->expects($this->never())
            ->method('set');

        $result = $this->cachingHabitRepository->findById($habitId, $this->userId);

        $this->assertEquals($habit, $result);
    }

    public function testFindByIdFetchesFromDecoratedAndCachesIfNotFoundInCache(): void
    {
        $habitId = 1;
        $habit = $this->createHabit($habitId, 'Read a book');
        $cacheKeyId = 'habit:id:' . $habitId . ':' . $this->userId;
        $cacheKeyTitle = 'habit:title:' . md5($habit->getTitle()) . ':' . $this->userId;

        $this->redisCache->expects($this->once())
            ->method('get')
            ->with($cacheKeyId)
            ->willReturn(null);

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Cache de hábito não encontrado para ID: ' . $habitId);

        $this->decoratedRepository->expects($this->once())
            ->method('findById')
            ->with($habitId, $this->userId)
            ->willReturn($habit);

        $this->redisCache->expects($this->exactly(2))
            ->method('set')
            ->with(
                $this->logicalOr(
                    $this->equalTo($cacheKeyId),
                    $this->equalTo($cacheKeyTitle)
                ),
                $this->isType('string'), // Expect any string for serialized habit
                $this->equalTo($this->cacheTtl)
            )
            ->willReturn(true);

        $result = $this->cachingHabitRepository->findById($habitId, $this->userId);

        $this->assertEquals($habit, $result);
    }

    public function testFindByTitleReturnsCachedHabit(): void
    {
        $habitTitle = 'Write code';
        $habit = $this->createHabit(2, $habitTitle);
        $serializedHabit = serialize($habit);
        $cacheKey = 'habit:title:' . md5($habitTitle) . ':' . $this->userId;

        $this->redisCache->expects($this->once())
            ->method('get')
            ->with($cacheKey)
            ->willReturn($serializedHabit);

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Cache de hábito encontrado para título: ' . $habitTitle);

        $this->decoratedRepository->expects($this->never())
            ->method('findByTitle');

        $this->redisCache->expects($this->never())
            ->method('set');

        $result = $this->cachingHabitRepository->findByTitle($habitTitle, $this->userId);

        $this->assertEquals($habit, $result);
    }

    public function testFindByTitleFetchesFromDecoratedAndCachesIfNotFoundInCache(): void
    {
        $habitTitle = 'Write code';
        $habit = $this->createHabit(2, $habitTitle);
        $cacheKeyId = 'habit:id:' . $habit->getId() . ':' . $this->userId;
        $cacheKeyTitle = 'habit:title:' . md5($habitTitle) . ':' . $this->userId;

        $this->redisCache->expects($this->once())
            ->method('get')
            ->with($cacheKeyTitle)
            ->willReturn(null);

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Cache de hábito não encontrado para título: ' . $habitTitle);

        $this->decoratedRepository->expects($this->once())
            ->method('findByTitle')
            ->with($habitTitle, $this->userId)
            ->willReturn($habit);

        $this->redisCache->expects($this->exactly(2))
            ->method('set')
            ->with(
                $this->logicalOr(
                    $this->equalTo($cacheKeyId),
                    $this->equalTo($cacheKeyTitle)
                ),
                $this->isType('string'), // Expect any string for serialized habit
                $this->equalTo($this->cacheTtl)
            )
            ->willReturn(true);

        $result = $this->cachingHabitRepository->findByTitle($habitTitle, $this->userId);

        $this->assertEquals($habit, $result);
    }

    public function testCreateInvalidatesCache(): void
    {
        $habitId = 4;
        $habit = new Habit('New Habit', $this->user);
        $weekDays = [0, 1, 2];
        $createdHabit = $this->createHabit($habitId, 'New Habit');

        $cacheKeyId = 'habit:id:' . $createdHabit->getId() . ':' . $this->userId;
        $cacheKeyAll = 'habit:all:' . $this->userId;

        $this->decoratedRepository->expects($this->once())
            ->method('create')
            ->with($habit, $weekDays)
            ->willReturn($createdHabit);

        $this->redisCache->expects($this->exactly(2))
            ->method('delete')
            ->with($this->logicalOr(
                $this->equalTo($cacheKeyId),
                $this->equalTo($cacheKeyAll)
            ))
            ->willReturn(true);

        $this->redisCache->expects($this->exactly(3))
            ->method('deleteByPattern')
            ->willReturnOnConsecutiveCalls(0, 0, 0);

        $this->logger->expects($this->exactly(5))
            ->method('info')
            ->with(
                $this->logicalOr(
                    $this->equalTo('Cache de hábito invalidado para ID: ' . $createdHabit->getId()),
                    $this->equalTo('Invalidados 0 entradas de cache de sumário para o usuário: ' . $this->userId),
                    $this->equalTo('Invalidados 0 entradas de cache de hábitos possíveis para o usuário: ' . $this->userId),
                    $this->equalTo('Invalidados 0 entradas de cache de hábitos completados para o usuário: ' . $this->userId),
                    $this->equalTo('Cache de todos os hábitos invalidado para o usuário: ' . $this->userId)
                )
            );


        $result = $this->cachingHabitRepository->create($habit, $weekDays);

        $this->assertEquals($createdHabit, $result);
    }

    public function testUpdateInvalidatesAndRecaches(): void
    {
        $habitId = 5;
        $oldTitle = 'Old Title';
        $newTitle = 'Updated Title';

        $oldHabit = $this->createHabit($habitId, $oldTitle);
        $updatedHabit = $this->createHabit($habitId, $newTitle);

        $weekDays = [3, 4, 5];

        $cacheKeyId = 'habit:id:' . $habitId . ':' . $this->userId;
        $cacheKeyAll = 'habit:all:' . $this->userId;
        $cacheKeyNewTitle = 'habit:title:' . md5($newTitle) . ':' . $this->userId;

        $this->decoratedRepository->expects($this->once())
            ->method('update')
            ->with($oldHabit, $weekDays)
            ->willReturn($updatedHabit);

        $this->redisCache->expects($this->exactly(2))
            ->method('delete')
            ->with($this->logicalOr(
                $this->equalTo($cacheKeyId),
                $this->equalTo($cacheKeyAll)
            ))
            ->willReturn(true);

        $this->redisCache->expects($this->exactly(3))
            ->method('deleteByPattern')
            ->willReturnOnConsecutiveCalls(0, 0, 0);

        $this->logger->expects($this->exactly(5))
            ->method('info')
            ->with(
                $this->logicalOr(
                    $this->equalTo('Cache de hábito invalidado para ID: ' . $habitId),
                    $this->equalTo('Invalidados 0 entradas de cache de sumário para o usuário: ' . $this->userId),
                    $this->equalTo('Invalidados 0 entradas de cache de hábitos possíveis para o usuário: ' . $this->userId),
                    $this->equalTo('Invalidados 0 entradas de cache de hábitos completados para o usuário: ' . $this->userId),
                    $this->equalTo('Cache de todos os hábitos invalidado para o usuário: ' . $this->userId)
                )
            );

        $this->redisCache->expects($this->exactly(2))
            ->method('set')
            ->with(
                $this->logicalOr(
                    $this->equalTo($cacheKeyId),
                    $this->equalTo($cacheKeyNewTitle)
                ),
                $this->isType('string'), // Expect any string for serialized habit
                $this->equalTo($this->cacheTtl)
            )
            ->willReturn(true);

        $result = $this->cachingHabitRepository->update($oldHabit, $weekDays);

        $this->assertEquals($updatedHabit, $result);
    }

    public function testDeleteInvalidatesCache(): void
    {
        $habitId = 6;
        $habitTitle = 'Habit to delete';
        $habitToDelete = $this->createHabit($habitId, $habitTitle);

        $cacheKeyId = 'habit:id:' . $habitId . ':' . $this->userId;
        $cacheKeyTitle = 'habit:title:' . md5($habitTitle) . ':' . $this->userId; // New cache key to expect deletion
        $cacheKeyAll = 'habit:all:' . $this->userId;

        $this->redisCache->expects($this->once())
            ->method('get')
            ->with($cacheKeyId)
            ->willReturn(null); // Simulate cache miss for findById within delete

        $this->decoratedRepository->expects($this->once())
            ->method('findById')
            ->with($habitId, $this->userId)
            ->willReturn($habitToDelete);

        $this->decoratedRepository->expects($this->once())
            ->method('delete')
            ->with($habitId, $this->userId)
            ->willReturn(true);

        // Expect three delete calls on redisCache: one for ID, one for Title, one for All
        $this->redisCache->expects($this->exactly(3))
            ->method('delete')
            ->with(
                $this->logicalOr(
                    $this->equalTo($cacheKeyId),
                    $this->equalTo($cacheKeyTitle),
                    $this->equalTo($cacheKeyAll)
                )
            )
            ->willReturn(true);

        $this->redisCache->expects($this->exactly(3))
            ->method('deleteByPattern')
            ->willReturnOnConsecutiveCalls(0, 0, 0);

        $this->logger->expects($this->exactly(6))
            ->method('info')
            ->with(
                $this->logicalOr(
                    $this->equalTo('Cache de hábito não encontrado para ID: ' . $habitId),
                    $this->equalTo('Cache de hábito invalidado para ID: ' . $habitId),
                    $this->equalTo('Invalidados 0 entradas de cache de sumário para o usuário: ' . $this->userId),
                    $this->equalTo('Invalidados 0 entradas de cache de hábitos possíveis para o usuário: ' . $this->userId),
                    $this->equalTo('Invalidados 0 entradas de cache de hábitos completados para o usuário: ' . $this->userId),
                    $this->equalTo('Cache de todos os hábitos invalidado para o usuário: ' . $this->userId)
                )
            );

        $result = $this->cachingHabitRepository->delete($habitId, $this->userId);

        $this->assertTrue($result);
    }

    public function testFindPossibleHabitsReturnsCached(): void
    {
        $date = new DateTimeImmutable('2024-01-01');
        $dateString = $date->format('Y-m-d');
        $habits = [$this->createHabit(1, 'Habit 1'), $this->createHabit(2, 'Habit 2')];
        $serializedHabits = serialize($habits);
        $cacheKey = 'habit:possible:' . $this->userId . ':' . $dateString;

        $this->redisCache->expects($this->once())
            ->method('get')
            ->with($cacheKey)
            ->willReturn($serializedHabits);

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Cache de hábitos possíveis encontrado para ' . $dateString);

        $this->decoratedRepository->expects($this->never())
            ->method('findPossibleHabits');

        $this->redisCache->expects($this->never())
            ->method('set');

        $result = $this->cachingHabitRepository->findPossibleHabits($date, $this->userId);

        $this->assertEquals($habits, $result);
    }

    public function testFindPossibleHabitsFetchesAndCaches(): void
    {
        $date = new DateTimeImmutable('2024-01-01');
        $dateString = $date->format('Y-m-d');
        $habits = [$this->createHabit(1, 'Habit 1'), $this->createHabit(2, 'Habit 2')];
        $cacheKey = 'habit:possible:' . $this->userId . ':' . $dateString;

        $this->redisCache->expects($this->once())
            ->method('get')
            ->with($cacheKey)
            ->willReturn(null);

        $this->decoratedRepository->expects($this->once())
            ->method('findPossibleHabits')
            ->with($date, $this->userId)
            ->willReturn($habits);

        $this->redisCache->expects($this->once())
            ->method('set')
            ->with($cacheKey, $this->isType('string'), $this->cacheTtl) // Use isType('string')
            ->willReturn(true);

        $result = $this->cachingHabitRepository->findPossibleHabits($date, $this->userId);

        $this->assertEquals($habits, $result);
    }

    public function testFindCompletedHabitsReturnsCached(): void
    {
        $date = new DateTimeImmutable('2024-01-01');
        $dateString = $date->format('Y-m-d');
        $habits = [$this->createHabit(1, 'Habit 1'), $this->createHabit(2, 'Habit 2')];
        $serializedHabits = serialize($habits);
        $cacheKey = 'habit:completed:' . $this->userId . ':' . $dateString;

        $this->redisCache->expects($this->once())
            ->method('get')
            ->with($cacheKey)
            ->willReturn($serializedHabits);

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Cache de hábitos completados encontrado para ' . $dateString);

        $this->decoratedRepository->expects($this->never())
            ->method('findCompletedHabits');

        $this->redisCache->expects($this->never())
            ->method('set');

        $result = $this->cachingHabitRepository->findCompletedHabits($date, $this->userId);

        $this->assertEquals($habits, $result);
    }

    public function testFindCompletedHabitsFetchesAndCaches(): void
    {
        $date = new DateTimeImmutable('2024-01-01');
        $dateString = $date->format('Y-m-d');
        $habits = [new Habit('Habit 1', $this->user), new Habit('Habit 2', $this->user)];
        $cacheKey = 'habit:completed:' . $this->userId . ':' . $dateString;

        $this->redisCache->expects($this->once())
            ->method('get')
            ->with($cacheKey)
            ->willReturn(null);

        $this->decoratedRepository->expects($this->once())
            ->method('findCompletedHabits')
            ->with($date, $this->userId)
            ->willReturn($habits);

        $this->redisCache->expects($this->once())
            ->method('set')
            ->with($cacheKey, $this->isType('string'), $this->cacheTtl) // Use isType('string')
            ->willReturn(true);

        $result = $this->cachingHabitRepository->findCompletedHabits($date, $this->userId);

        $this->assertEquals($habits, $result);
    }

    public function testGetHabitsSummaryReturnsCached(): void
    {
        $summary = [
            ['date' => '2024-01-01', 'completed' => 2, 'total' => 3],
            ['date' => '2024-01-02', 'completed' => 1, 'total' => 2],
        ];
        $serializedSummary = serialize($summary);
        $cacheKey = 'habit:summary:' . $this->userId . ':all';

        $this->redisCache->expects($this->once())
            ->method('get')
            ->with($cacheKey)
            ->willReturn($serializedSummary);

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Cache de sumário de hábitos encontrado para o usuário: ' . $this->userId . ' (all)');

        $this->decoratedRepository->expects($this->never())
            ->method('getHabitsSummary');

        $this->redisCache->expects($this->never())
            ->method('set');

        $result = $this->cachingHabitRepository->getHabitsSummary($this->userId);

        $this->assertEquals($summary, $result);
    }

    public function testGetHabitsSummaryFetchesAndCaches(): void
    {
        $summary = [
            ['date' => '2024-01-01', 'completed' => 2, 'total' => 3],
            ['date' => '2024-01-02', 'completed' => 1, 'total' => 2],
        ];
        $cacheKey = 'habit:summary:' . $this->userId . ':all';

        $this->redisCache->expects($this->once())
            ->method('get')
            ->with($cacheKey)
            ->willReturn(null);

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Cache de sumário de hábitos não encontrado para o usuário: ' . $this->userId . ' (all)');

        $this->decoratedRepository->expects($this->once())
            ->method('getHabitsSummary')
            ->with($this->userId)
            ->willReturn($summary);

        $this->redisCache->expects($this->once())
            ->method('set')
            ->with($cacheKey, $this->isType('string'), $this->cacheTtl)
            ->willReturn(true);

        $result = $this->cachingHabitRepository->getHabitsSummary($this->userId);

        $this->assertEquals($summary, $result);
    }

    public function testGetHabitsSummaryReturnsEmptyArrayWhenNoData(): void
    {
        $cacheKey = 'habit:summary:' . $this->userId . ':all';

        $this->redisCache->expects($this->once())
            ->method('get')
            ->with($cacheKey)
            ->willReturn(null);

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Cache de sumário de hábitos não encontrado para o usuário: ' . $this->userId . ' (all)');

        $this->decoratedRepository->expects($this->once())
            ->method('getHabitsSummary')
            ->with($this->userId)
            ->willReturn([]);

        $this->redisCache->expects($this->never())
            ->method('set'); // Não deve cachear array vazio

        $result = $this->cachingHabitRepository->getHabitsSummary($this->userId);

        $this->assertEquals([], $result);
    }

    public function testInvalidateUserHabitsCacheInvalidatesSummaryAndPossibleHabits(): void
    {
        $summaryPattern = 'habit:summary:' . $this->userId . ':*';
        $possiblePattern = 'habit:possible:' . $this->userId . ':*';
        $completedPattern = 'habit:completed:' . $this->userId . ':*';
        $allKey = 'habit:all:' . $this->userId;

        $this->redisCache->expects($this->exactly(3))
            ->method('deleteByPattern')
            ->with(
                $this->logicalOr(
                    $this->equalTo($summaryPattern),
                    $this->equalTo($possiblePattern),
                    $this->equalTo($completedPattern)
                )
            )
            ->willReturnOnConsecutiveCalls(5, 3, 0);

        $this->redisCache->expects($this->once())
            ->method('delete')
            ->with($allKey)
            ->willReturn(true);

        $this->logger->expects($this->exactly(4))
            ->method('info')
            ->with(
                $this->logicalOr(
                    $this->equalTo('Invalidados 5 entradas de cache de sumário para o usuário: ' . $this->userId),
                    $this->equalTo('Invalidados 3 entradas de cache de hábitos possíveis para o usuário: ' . $this->userId),
                    $this->equalTo('Invalidados 0 entradas de cache de hábitos completados para o usuário: ' . $this->userId),
                    $this->equalTo('Cache de todos os hábitos invalidado para o usuário: ' . $this->userId)
                )
            );

        $this->cachingHabitRepository->invalidateUserHabitsCache($this->userId);
    }
}
