<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Decorator;

use App\Domain\Entity\Habit;
use App\Domain\Repository\HabitRepositoryInterface;
use App\Infrastructure\Persistence\Redis\RedisCache;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;

class CachingHabitRepository implements HabitRepositoryInterface
{
    private const string CACHE_PREFIX_ID = 'habit:id:';

    private const string CACHE_PREFIX_TITLE = 'habit:title:';

    private const string CACHE_PREFIX_POSSIBLE = 'habit:possible:';

    private const string CACHE_PREFIX_COMPLETED = 'habit:completed:';

    private const string CACHE_PREFIX_SUMMARY = 'habit:summary:';

    private const string CACHE_PREFIX_ALL = 'habit:all:';

    private const int CACHE_TTL = 3600; // 1 hour for habits

    public function __construct(
        private readonly HabitRepositoryInterface $decoratedRepository,
        private readonly RedisCache $cache,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function create(Habit $habit, array $weekDays): Habit
    {
        $createdHabit = $this->decoratedRepository->create($habit, $weekDays);
        $this->invalidateHabitCache($createdHabit->getId(), $createdHabit->getUser()->getId());
        $this->invalidateSummaryAndPossibleHabitsCache($createdHabit->getUser()->getId());
        $this->invalidateAllHabitsCache($createdHabit->getUser()->getId());

        return $createdHabit;
    }

    public function findById(int $id, int $userId): ?Habit
    {
        $cacheKey = self::CACHE_PREFIX_ID . $id . ':' . $userId;

        $cachedHabit = $this->cache->get($cacheKey);
        if ($cachedHabit) {
            $this->logger->info('Cache de hábito encontrado para ID: ' . $id);

            return unserialize((string) $cachedHabit);
        }

        $this->logger->info('Cache de hábito não encontrado para ID: ' . $id);

        $habit = $this->decoratedRepository->findById($id, $userId);

        if ($habit instanceof Habit) {
            $this->setHabitCache($habit);
        }

        return $habit;
    }

    public function findByTitle(string $title, int $userId): ?Habit
    {
        $cacheKey = self::CACHE_PREFIX_TITLE . \md5($title) . ':' . $userId; // Use MD5 for long titles

        $cachedHabit = $this->cache->get($cacheKey);
        if ($cachedHabit) {
            $this->logger->info('Cache de hábito encontrado para título: ' . $title);

            return unserialize((string) $cachedHabit);
        }

        $this->logger->info('Cache de hábito não encontrado para título: ' . $title);

        $habit = $this->decoratedRepository->findByTitle($title, $userId);

        if ($habit instanceof Habit) {
            $this->setHabitCache($habit);
        }

        return $habit;
    }

    public function update(Habit $habit, array $weekDays): Habit
    {
        // Invalidate old cache entries first
        $this->invalidateHabitCache($habit->getId(), $habit->getUser()->getId());
        $this->invalidateSummaryAndPossibleHabitsCache($habit->getUser()->getId());
        $this->invalidateAllHabitsCache($habit->getUser()->getId());

        $updatedHabit = $this->decoratedRepository->update($habit, $weekDays);

        // Set cache for the updated habit
        $this->setHabitCache($updatedHabit);

        return $updatedHabit;
    }

    public function delete(int $id, int $userId): bool
    {
        // We need to fetch the habit first to invalidate related caches (e.g., summary)
        $habitToDelete = $this->findById($id, $userId);

        $deleted = $this->decoratedRepository->delete($id, $userId);

        if ($deleted && $habitToDelete instanceof Habit) {
            $this->cache->delete(self::CACHE_PREFIX_TITLE . \md5($habitToDelete->getTitle()) . ':' . $userId);
            $this->invalidateHabitCache($id, $userId);
            $this->invalidateSummaryAndPossibleHabitsCache($userId);
            $this->invalidateAllHabitsCache($userId);
        }

        return $deleted;
    }

    public function findPossibleHabits(DateTimeImmutable $date, int $userId): array
    {
        $dateString = $date->format('Y-m-d');
        $cacheKey = self::CACHE_PREFIX_POSSIBLE . $userId . ':' . $dateString;

        $cachedHabits = $this->cache->get($cacheKey);
        if ($cachedHabits) {
            $this->logger->info('Cache de hábitos possíveis encontrado para ' . $dateString);

            return unserialize((string) $cachedHabits);
        }

        $habits = $this->decoratedRepository->findPossibleHabits($date, $userId);

        if ($habits !== []) {
            $this->cache->set($cacheKey, serialize($habits), self::CACHE_TTL);
        }

        return $habits;
    }

    public function findCompletedHabits(DateTimeImmutable $date, int $userId): array
    {
        $dateImmutable = DateTimeImmutable::createFromInterface($date);
        $dateString = $dateImmutable->format('Y-m-d');
        $cacheKey = self::CACHE_PREFIX_COMPLETED . $userId . ':' . $dateString;

        $cachedHabits = $this->cache->get($cacheKey);
        if ($cachedHabits) {
            $this->logger->info('Cache de hábitos completados encontrado para ' . $dateString);

            return unserialize((string) $cachedHabits);
        }

        $habits = $this->decoratedRepository->findCompletedHabits($date, $userId);

        if ($habits !== []) {
            $this->cache->set($cacheKey, serialize($habits), self::CACHE_TTL);
        }

        return $habits;
    }

    public function getHabitsSummary(int $userId, ?DateTimeImmutable $date = null): array
    {
        $dateSuffix = $date instanceof \DateTimeImmutable ? $date->format('Y-m-d') : 'all';
        $cacheKey = self::CACHE_PREFIX_SUMMARY . $userId . ':' . $dateSuffix;

        $cachedSummary = $this->cache->get($cacheKey);
        if ($cachedSummary) {
            $this->logger->info('Cache de sumário de hábitos encontrado para o usuário: ' . $userId . ' (' . $dateSuffix . ')');

            return unserialize((string) $cachedSummary);
        }

        $this->logger->info('Cache de sumário de hábitos não encontrado para o usuário: ' . $userId . ' (' . $dateSuffix . ')');

        $summary = $this->decoratedRepository->getHabitsSummary($userId, $date);

        if ($summary !== []) {
            $this->cache->set($cacheKey, serialize($summary), self::CACHE_TTL);
        }

        return $summary;
    }

    public function findAllByUserId(int $userId): array
    {
        $cacheKey = self::CACHE_PREFIX_ALL . $userId;

        $cachedHabits = $this->cache->get($cacheKey);
        if ($cachedHabits) {
            $this->logger->info('Cache de todos os hábitos encontrado para o usuário: ' . $userId);

            return unserialize((string) $cachedHabits);
        }

        $this->logger->info('Cache de todos os hábitos não encontrado para o usuário: ' . $userId);

        $habits = $this->decoratedRepository->findAllByUserId($userId);

        if ($habits !== []) {
            $this->cache->set($cacheKey, serialize($habits), self::CACHE_TTL);
        }

        return $habits;
    }

    public function invalidateUserHabitsCache(int $userId): void
    {
        $this->invalidateSummaryAndPossibleHabitsCache($userId);
        $this->invalidateAllHabitsCache($userId);
    }

    private function setHabitCache(Habit $habit): void
    {
        $serializedHabit = serialize($habit);

        $this->cache->set(self::CACHE_PREFIX_ID . $habit->getId() . ':' . $habit->getUser()->getId(), $serializedHabit, self::CACHE_TTL);
        $this->cache->set(self::CACHE_PREFIX_TITLE . \md5($habit->getTitle()) . ':' . $habit->getUser()->getId(), $serializedHabit, self::CACHE_TTL);
    }

    private function invalidateHabitCache(int $habitId, int $userId): void
    {
        // Delete by ID
        $this->cache->delete(self::CACHE_PREFIX_ID . $habitId . ':' . $userId);

        // Since findByTitle uses MD5 of title, we need to know the old title to invalidate.
        // For simplicity, we can assume the title might have changed and just delete the ID key.
        // If strict consistency is needed for title-based lookups, we'd need to fetch the old habit first.
        // For now, we rely on the next findByTitle to repopulate the cache if the title changed.

        $this->logger->info('Cache de hábito invalidado para ID: ' . $habitId);
    }

    private function invalidateSummaryAndPossibleHabitsCache(int $userId): void
    {
        // Invalidate all summary entries for the user
        $summaryPattern = self::CACHE_PREFIX_SUMMARY . $userId . ':*';
        $deletedSummaryKeys = $this->cache->deleteByPattern($summaryPattern);
        $this->logger->info('Invalidados ' . $deletedSummaryKeys . ' entradas de cache de sumário para o usuário: ' . $userId);

        // Invalidate all possible habits entries for the user
        $possiblePattern = self::CACHE_PREFIX_POSSIBLE . $userId . ':*';
        $deletedPossibleKeys = $this->cache->deleteByPattern($possiblePattern);
        $this->logger->info('Invalidados ' . $deletedPossibleKeys . ' entradas de cache de hábitos possíveis para o usuário: ' . $userId);

        // Invalidate all completed habits entries for the user
        $completedPattern = self::CACHE_PREFIX_COMPLETED . $userId . ':*';
        $deletedCompletedKeys = $this->cache->deleteByPattern($completedPattern);
        $this->logger->info('Invalidados ' . $deletedCompletedKeys . ' entradas de cache de hábitos completados para o usuário: ' . $userId);
    }

    private function invalidateAllHabitsCache(int $userId): void
    {
        $this->cache->delete(self::CACHE_PREFIX_ALL . $userId);
        $this->logger->info('Cache de todos os hábitos invalidado para o usuário: ' . $userId);
    }
}
