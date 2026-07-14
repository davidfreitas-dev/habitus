<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Decorator;

use App\Domain\Entity\Role;
use App\Domain\Repository\RoleRepositoryInterface;
use App\Infrastructure\Persistence\Redis\RedisCache;
use Psr\Log\LoggerInterface;

class CachingRoleRepository implements RoleRepositoryInterface
{
    private const string CACHE_PREFIX_ID = 'role:id:';

    private const string CACHE_PREFIX_NAME = 'role:name:';

    private const CACHE_TTL = 3600 * 24; // 24 hours - roles are fairly static

    public function __construct(
        private readonly RoleRepositoryInterface $decoratedRepository,
        private readonly RedisCache $cache,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function findById(int $id): ?Role
    {
        $cacheKey = self::CACHE_PREFIX_ID . $id;

        $cachedRole = $this->cache->get($cacheKey);
        if ($cachedRole) {
            $this->logger->info('Cache de perfil encontrado para o ID: ' . $id);

            return unserialize((string) $cachedRole);
        }

        $this->logger->info('Cache de perfil não encontrado para o ID: ' . $id);

        $role = $this->decoratedRepository->findById($id);

        if ($role instanceof Role) {
            $this->setCache($role);
        }

        return $role;
    }

    public function findByName(string $name): ?Role
    {
        $cacheKey = self::CACHE_PREFIX_NAME . $name;

        $cachedRole = $this->cache->get($cacheKey);
        if ($cachedRole) {
            $this->logger->info('Cache de perfil encontrado para o nome: ' . $name);

            return unserialize((string) $cachedRole);
        }

        $this->logger->info('Cache de perfil não encontrado para o nome: ' . $name);

        $role = $this->decoratedRepository->findByName($name);

        if ($role instanceof Role) {
            $this->setCache($role);
        }

        return $role;
    }

    private function setCache(Role $role): void
    {
        $idCacheKey = self::CACHE_PREFIX_ID . $role->getId();
        $nameCacheKey = self::CACHE_PREFIX_NAME . $role->getName();

        $serializedRole = serialize($role);

        $this->cache->set($idCacheKey, $serializedRole, self::CACHE_TTL);
        $this->cache->set($nameCacheKey, $serializedRole, self::CACHE_TTL);
    }

    private function invalidateCache(Role $role): void
    {
        $idCacheKey = self::CACHE_PREFIX_ID . $role->getId();
        $nameCacheKey = self::CACHE_PREFIX_NAME . $role->getName();

        $this->cache->delete($idCacheKey);
        $this->cache->delete($nameCacheKey);

        $this->logger->info('Cache de perfil invalidado para o ID: ' . $role->getId());
    }
}
