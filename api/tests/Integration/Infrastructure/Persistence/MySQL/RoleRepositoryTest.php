<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Persistence\MySQL;

use App\Infrastructure\Persistence\MySQL\RoleRepository;
use Tests\Integration\DatabaseTestCase;

class RoleRepositoryTest extends DatabaseTestCase
{
    private RoleRepository $roleRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->roleRepository = new RoleRepository(self::$pdo);
    }

    public function testFindById(): void
    {
        // Roles are seeded in DatabaseTestCase
        $role = $this->roleRepository->findById(1);

        $this->assertNotNull($role, 'Perfil com ID 1 deve ser encontrado');
        $this->assertEquals(1, $role->getId());
        $this->assertEquals('customer', $role->getName());
    }

    public function testFindByName(): void
    {
        // Roles are seeded in DatabaseTestCase
        $role = $this->roleRepository->findByName('admin');

        $this->assertNotNull($role, 'Perfil com nome "admin" deve ser encontrado');
        $this->assertEquals('admin', $role->getName());
        $this->assertEquals(3, $role->getId());
    }

    public function testFindByIdNotFound(): void
    {
        $role = $this->roleRepository->findById(9999);
        $this->assertNull($role, 'Não deve encontrar um perfil com um ID não existente');
    }

    public function testFindByNameNotFound(): void
    {
        $role = $this->roleRepository->findByName('non_existent_role');
        $this->assertNull($role, 'Não deve encontrar um perfil com um nome não existente');
    }
}
