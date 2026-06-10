<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Persistence\Decorator;

use App\Domain\Entity\Day;
use App\Domain\Repository\DayRepositoryInterface;
use App\Infrastructure\Persistence\Decorator\CachingDayRepository;
use App\Infrastructure\Persistence\Redis\RedisCache;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class CachingDayRepositoryTest extends TestCase
{
    private DayRepositoryInterface&MockObject $decoratedRepository;

    private RedisCache&MockObject $redisCache;

    private LoggerInterface&MockObject $logger;

    private CachingDayRepository $cachingDayRepository;

    private int $cacheTtl;

    protected function setUp(): void
    {
        parent::setUp();

        $this->decoratedRepository = $this->createMock(DayRepositoryInterface::class);
        $this->redisCache = $this->createMock(RedisCache::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->cacheTtl = 3600 * 24; // Corresponds to CACHE_TTL in CachingDayRepository

        $this->cachingDayRepository = new CachingDayRepository(
            $this->decoratedRepository,
            $this->redisCache,
            $this->logger,
        );
    }

    private function createDay(string $dateString): Day
    {
        $date = new DateTimeImmutable($dateString);
        $day = new Day($date);
        $reflection = new \ReflectionClass($day);
        $property = $reflection->getProperty('id');
        $property->setValue($day, 1);
        return $day;
    }

    public function testFindOneByDateReturnsCachedDay(): void
    {
        $dateString = '2024-02-05';
        $date = new DateTimeImmutable($dateString);
        $day = $this->createDay($dateString);
        $cacheKey = 'day:date:' . $dateString;

        $this->redisCache->expects($this->once())
            ->method('get')
            ->with($cacheKey)
            ->willReturn($day); // RedisCache returns unserialized object

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Cache de dia encontrado para a data: ' . $dateString);

        $this->decoratedRepository->expects($this->never())
            ->method('findOneByDate');

        $this->redisCache->expects($this->never())
            ->method('set');

        $result = $this->cachingDayRepository->findOneByDate($date);

        $this->assertEquals($day, $result);
    }

    public function testFindOneByDateFetchesFromDecoratedAndCachesIfNotFoundInCache(): void
    {
        $dateString = '2024-02-05';
        $date = new DateTimeImmutable($dateString);
        $day = $this->createDay($dateString);
        $cacheKey = 'day:date:' . $dateString;

        $this->redisCache->expects($this->once())
            ->method('get')
            ->with($cacheKey)
            ->willReturn(null);

        $this->logger->expects($this->exactly(2))
            ->method('info')
            ->willReturnOnConsecutiveCalls(
                'Cache de dia não encontrado para a data: ' . $dateString,
                'Dia salvo no cache para a data: ' . $dateString
            );

        $this->decoratedRepository->expects($this->once())
            ->method('findOneByDate')
            ->with($date)
            ->willReturn($day);

        $this->redisCache->expects($this->once())
            ->method('set')
            ->with($cacheKey, $day, $this->cacheTtl)
            ->willReturn(true);

        $result = $this->cachingDayRepository->findOneByDate($date);

        self::assertEquals($day->toArray(), $result->toArray());
    }

    public function testFindOneByDateReturnsNullIfNotFoundAnywhere(): void
    {
        $dateString = '2024-02-05';
        $date = new DateTimeImmutable($dateString);
        $cacheKey = 'day:date:' . $dateString;

        $this->redisCache->expects($this->once())
            ->method('get')
            ->with($cacheKey)
            ->willReturn(null);

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Cache de dia não encontrado para a data: ' . $dateString);

        $this->decoratedRepository->expects($this->once())
            ->method('findOneByDate')
            ->with($date)
            ->willReturn(null);

        $this->redisCache->expects($this->never())
            ->method('set');

        $result = $this->cachingDayRepository->findOneByDate($date);

        $this->assertNull($result);
    }

    public function testCreateDelegatesToDecoratedRepository(): void
    {
        $dateString = '2024-02-05';
        $date = new DateTimeImmutable($dateString);
        $day = $this->createDay($dateString);

        $this->decoratedRepository->expects($this->once())
            ->method('create')
            ->with($date)
            ->willReturn($day);

        $this->redisCache->expects($this->never())
            ->method('get');
        $this->redisCache->expects($this->never())
            ->method('set');

        $result = $this->cachingDayRepository->create($date);

        $this->assertEquals($day, $result);
    }
}
