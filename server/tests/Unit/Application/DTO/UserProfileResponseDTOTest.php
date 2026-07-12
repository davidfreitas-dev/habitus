<?php

declare(strict_types=1);

namespace Tests\Unit\Application\DTO;

use App\Application\DTO\User\UserProfileResponseDTO;
use App\Domain\Entity\Person;
use App\Domain\Entity\Role;
use App\Domain\Entity\User;
use App\Domain\ValueObject\CpfCnpj;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(UserProfileResponseDTO::class)]
class UserProfileResponseDTOTest extends TestCase
{
    public function testFromEntity(): void
    {
        $now = new DateTimeImmutable();
        $cpfCnpj = CpfCnpj::fromString('111.444.777-35');

        $dto = new UserProfileResponseDTO(
            id: 1,
            name: 'Test User',
            email: 'test@example.com',
            phone: '123456789',
            cpfcnpj: $cpfCnpj->value(),
            avatarUrl: 'http://example.com/avatar.jpg',
            isActive: true,
            isVerified: true,
            roleId: 2,
            roleName: 'user',
            createdAt: $now->format('Y-m-d H:i:s'),
            updatedAt: $now->format('Y-m-d H:i:s'),
        );

        $this->assertSame(1, $dto->id);
        $this->assertSame('Test User', $dto->name);
        $this->assertSame('test@example.com', $dto->email);
        $this->assertSame('123456789', $dto->phone);
        $this->assertSame($cpfCnpj->value(), $dto->cpfcnpj);
        $this->assertSame('http://example.com/avatar.jpg', $dto->avatarUrl);
        $this->assertTrue($dto->isActive);
        $this->assertTrue($dto->isVerified);
        $this->assertSame(2, $dto->roleId);
        $this->assertSame('user', $dto->roleName);
        $this->assertSame($now->format('Y-m-d H:i:s'), $dto->createdAt);
        $this->assertSame($now->format('Y-m-d H:i:s'), $dto->updatedAt);
    }

    public function testJsonSerialize(): void
    {
        $now = new DateTimeImmutable();
        $dto = new UserProfileResponseDTO(
            id: 1,
            name: 'Test User',
            email: 'test@example.com',
            phone: '123456789',
            cpfcnpj: '11144477735',
            avatarUrl: 'http://example.com/avatar.jpg',
            isActive: true,
            isVerified: true,
            roleId: 2,
            roleName: 'user',
            createdAt: $now->format('Y-m-d H:i:s'),
            updatedAt: $now->format('Y-m-d H:i:s'),
        );

        $json = $dto->jsonSerialize();

        $this->assertSame(1, $json['id']);
        $this->assertSame('Test User', $json['name']);
        $this->assertSame('test@example.com', $json['email']);
        $this->assertSame('123456789', $json['phone']);
        $this->assertSame('11144477735', $json['cpfcnpj']);
        $this->assertSame('http://example.com/avatar.jpg', $json['avatar_url']);
        $this->assertTrue($json['is_active']);
        $this->assertTrue($json['is_verified']);
        $this->assertSame(2, $json['role_id']);
        $this->assertSame('user', $json['role_name']);
        $this->assertSame($now->format('Y-m-d H:i:s'), $json['created_at']);
        $this->assertSame($now->format('Y-m-d H:i:s'), $json['updated_at']);
    }
}
