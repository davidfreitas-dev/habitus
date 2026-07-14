<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Decorator;

use App\Domain\Entity\Day;
use App\Domain\Repository\DayRepositoryInterface;
use App\Infrastructure\Persistence\Redis\RedisCache;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;

class CachingDayRepository implements DayRepositoryInterface
{
    private const CACHE_TTL = 3600 * 24; // 24 hours

    public function __construct(
        private readonly DayRepositoryInterface $decoratedRepository,
        private readonly RedisCache $cache,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function findOneByDate(DateTimeImmutable $date): ?Day
    {
        $dateString = $date->format('Y-m-d');
        $cacheKey = 'day:date:' . $dateString;

        $cachedDay = $this->cache->get($cacheKey);
        if ($cachedDay !== null) {
            $this->logger->info('Cache de dia encontrado para a data: ' . $dateString);
            return $cachedDay;
        }

        $this->logger->info('Cache de dia não encontrado para a data: ' . $dateString);
        $day = $this->decoratedRepository->findOneByDate($date);

        if ($day instanceof \App\Domain\Entity\Day) {
            $this->cache->set($cacheKey, $day, self::CACHE_TTL);
            $this->logger->info('Dia salvo no cache para a data: ' . $dateString);
        }

        return $day;
    }

    public function create(DateTimeImmutable $date): Day
    {
        return $this->decoratedRepository->create($date);
    }

    public function findCompletedHabitIdsByDate(int $userId, DateTimeImmutable $date): array
    {
        return $this->decoratedRepository->findCompletedHabitIdsByDate($userId, $date);
    }
}
