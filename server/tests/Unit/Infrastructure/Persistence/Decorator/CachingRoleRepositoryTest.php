<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Persistence\Decorator;

use App\Domain\Entity\Role;
use App\Domain\Repository\RoleRepositoryInterface;
use App\Infrastructure\Persistence\Decorator\CachingRoleRepository;
use App\Infrastructure\Persistence\Redis\RedisCache;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class CachingRoleRepositoryTest extends TestCase
{
    private RoleRepositoryInterface&MockObject $decoratedRepository;

    private RedisCache&MockObject $redisCache;

    private LoggerInterface&MockObject $logger;

    private CachingRoleRepository $cachingRoleRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->decoratedRepository = $this->createMock(RoleRepositoryInterface::class);
        $this->redisCache = $this->createMock(RedisCache::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->cachingRoleRepository = new CachingRoleRepository(
            $this->decoratedRepository,
            $this->redisCache,
            $this->logger,
        );
    }

    public function testFindByIdReturnsCachedRole(): void
    {
        $id = 1;
        $role = new Role($id, 'admin', 'Administrator', new \DateTimeImmutable(), new \DateTimeImmutable());
        $serializedRole = serialize($role);
        $cacheKey = 'role:id:' . $id;

        $this->redisCache->expects($this->once())
            ->method('get')
            ->with($cacheKey)
            ->willReturn($serializedRole);

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Cache de perfil encontrado para o ID: ' . $id);

        $this->decoratedRepository->expects($this->never())
            ->method('findById');

        $this->redisCache->expects($this->never())
            ->method('set');

        $result = $this->cachingRoleRepository->findById($id);

        $this->assertEquals($role, $result);
    }

    public function testFindByIdFetchesFromDecoratedAndCachesIfNotFoundInCache(): void
    {
        $id = 1;
        $role = new Role($id, 'admin', 'Administrator', new \DateTimeImmutable(), new \DateTimeImmutable());
        $serializedRole = serialize($role);
        $cacheKeyId = 'role:id:' . $id;
        $cacheKeyName = 'role:name:' . $role->getName();
        $cacheTtl = 3600 * 24;

        $this->redisCache->expects($this->once())
            ->method('get')
            ->with($cacheKeyId)
            ->willReturn(null);

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Cache de perfil não encontrado para o ID: ' . $id);

        $this->decoratedRepository->expects($this->once())
            ->method('findById')
            ->with($id)
            ->willReturn($role);

        $this->redisCache->expects($this->exactly(2))
            ->method('set')
            ->with(
                $this->logicalOr(
                    $this->equalTo($cacheKeyId),
                    $this->equalTo($cacheKeyName)
                ),
                $this->equalTo($serializedRole),
                $this->equalTo($cacheTtl)
            )
            ->willReturn(true);

        $result = $this->cachingRoleRepository->findById($id);

        $this->assertEquals($role, $result);
    }

    public function testFindByIdReturnsNullIfNotFoundAnywhere(): void
    {
        $id = 99;
        $cacheKeyId = 'role:id:' . $id;

        $this->redisCache->expects($this->once())
            ->method('get')
            ->with($cacheKeyId)
            ->willReturn(null);

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Cache de perfil não encontrado para o ID: ' . $id);

        $this->decoratedRepository->expects($this->once())
            ->method('findById')
            ->with($id)
            ->willReturn(null);

        $this->redisCache->expects($this->never())
            ->method('set');

        $result = $this->cachingRoleRepository->findById($id);

        $this->assertNull($result);
    }

    public function testFindByNameReturnsCachedRole(): void
    {
        $name = 'user';
        $role = new Role(2, $name, 'User', new \DateTimeImmutable(), new \DateTimeImmutable());
        $serializedRole = serialize($role);
        $cacheKey = 'role:name:' . $name;

        $this->redisCache->expects($this->once())
            ->method('get')
            ->with($cacheKey)
            ->willReturn($serializedRole);

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Cache de perfil encontrado para o nome: ' . $name);

        $this->decoratedRepository->expects($this->never())
            ->method('findByName');

        $this->redisCache->expects($this->never())
            ->method('set');

        $result = $this->cachingRoleRepository->findByName($name);

        $this->assertEquals($role, $result);
    }

    public function testFindByNameFetchesFromDecoratedAndCachesIfNotFoundInCache(): void
    {
        $name = 'user';
        $role = new Role(2, $name, 'User', new \DateTimeImmutable(), new \DateTimeImmutable());
        $serializedRole = serialize($role);
        $cacheKeyId = 'role:id:' . $role->getId();
        $cacheKeyName = 'role:name:' . $name;
        $cacheTtl = 3600 * 24;

        $this->redisCache->expects($this->once())
            ->method('get')
            ->with($cacheKeyName)
            ->willReturn(null);

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Cache de perfil não encontrado para o nome: ' . $name);

        $this->decoratedRepository->expects($this->once())
            ->method('findByName')
            ->with($name)
            ->willReturn($role);

        $this->redisCache->expects($this->exactly(2))
            ->method('set')
            ->with(
                $this->logicalOr(
                    $this->equalTo($cacheKeyId),
                    $this->equalTo($cacheKeyName)
                ),
                $this->equalTo($serializedRole),
                $this->equalTo($cacheTtl)
            )
            ->willReturn(true);

        $result = $this->cachingRoleRepository->findByName($name);

        $this->assertEquals($role, $result);
    }

    public function testFindByNameReturnsNullIfNotFoundAnywhere(): void
    {
        $name = 'nonexistent';
        $cacheKeyName = 'role:name:' . $name;

        $this->redisCache->expects($this->once())
            ->method('get')
            ->with($cacheKeyName)
            ->willReturn(null);

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Cache de perfil não encontrado para o nome: ' . $name);

        $this->decoratedRepository->expects($this->once())
            ->method('findByName')
            ->with($name)
            ->willReturn(null);

        $this->redisCache->expects($this->never())
            ->method('set');

        $result = $this->cachingRoleRepository->findByName($name);

        $this->assertNull($result);
    }
}