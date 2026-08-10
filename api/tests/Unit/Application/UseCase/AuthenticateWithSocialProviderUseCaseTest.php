<?php

declare(strict_types=1);

namespace Tests\Unit\Application\UseCase;

use App\Application\DTO\Auth\SocialLoginRequestDTO;
use App\Application\DTO\Auth\LoginResponseDTO;
use App\Application\UseCase\AuthenticateWithSocialProviderUseCase;
use App\Domain\Entity\Person;
use App\Domain\Entity\Role;
use App\Domain\Entity\User;
use App\Domain\Exception\AuthenticationException;
use App\Domain\Repository\PersonRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;
use App\Infrastructure\Security\GoogleTokenVerifier;
use App\Infrastructure\Security\JwtService;
use PHPUnit\Framework\TestCase;
use PDO;

class AuthenticateWithSocialProviderUseCaseTest extends TestCase
{
    private \PHPUnit\Framework\MockObject\MockObject $pdo;
    private \PHPUnit\Framework\MockObject\MockObject $googleVerifier;
    private \PHPUnit\Framework\MockObject\MockObject $personRepository;
    private \PHPUnit\Framework\MockObject\MockObject $userRepository;
    private \PHPUnit\Framework\MockObject\MockObject $jwtService;
    private Role $defaultUserRole;
    private AuthenticateWithSocialProviderUseCase $useCase;

    protected function setUp(): void
    {
        $this->pdo = $this->createMock(PDO::class);
        $this->googleVerifier = $this->createMock(GoogleTokenVerifier::class);
        $this->personRepository = $this->createMock(PersonRepositoryInterface::class);
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->jwtService = $this->createMock(JwtService::class);
        
        $this->defaultUserRole = new Role(
            1,
            'customer',
            'Customer role',
            new \DateTimeImmutable(),
            new \DateTimeImmutable()
        );

        $this->useCase = new AuthenticateWithSocialProviderUseCase(
            $this->pdo,
            $this->googleVerifier,
            $this->personRepository,
            $this->userRepository,
            $this->jwtService,
            $this->defaultUserRole
        );
    }

    public function testLoginExistingUser(): void
    {
        $dto = new SocialLoginRequestDTO('google', 'valid_token');

        $this->googleVerifier->expects($this->once())
            ->method('verify')
            ->with('valid_token')
            ->willReturn([
                'providerId' => '1234567890',
                'email' => 'test@example.com',
                'name' => 'Test User'
            ]);

        $person = $this->createMock(Person::class);
        $person->method('getEmail')->willReturn('test@example.com');
        
        $user = $this->createMock(User::class);
        $user->method('isActive')->willReturn(true);
        $user->method('getId')->willReturn(1);
        $user->method('getPerson')->willReturn($person);

        $this->userRepository->expects($this->once())
            ->method('findByEmail')
            ->with('test@example.com')
            ->willReturn($user);

        $this->jwtService->expects($this->once())
            ->method('generateAccessToken')
            ->with(1, 'test@example.com')
            ->willReturn('access_token');
            
        $this->jwtService->expects($this->once())
            ->method('generateRefreshToken')
            ->with(1)
            ->willReturn('refresh_token');
            
        $this->jwtService->method('getAccessTokenExpire')->willReturn(3600);

        $result = $this->useCase->execute($dto);

        $this->assertInstanceOf(LoginResponseDTO::class, $result);
        $this->assertEquals('access_token', $result->accessToken);
        $this->assertEquals('refresh_token', $result->refreshToken);
    }

    public function testLoginCreatesNewUser(): void
    {
        $dto = new SocialLoginRequestDTO('google', 'valid_token');

        $this->googleVerifier->expects($this->once())
            ->method('verify')
            ->with('valid_token')
            ->willReturn([
                'providerId' => '1234567890',
                'email' => 'new@example.com',
                'name' => 'New User'
            ]);

        $this->userRepository->expects($this->once())
            ->method('findByEmail')
            ->with('new@example.com')
            ->willReturn(null);

        $this->personRepository->expects($this->once())
            ->method('findByEmail')
            ->with('new@example.com')
            ->willReturn(null);

        $this->pdo->expects($this->once())->method('beginTransaction');
        $this->pdo->expects($this->once())->method('commit');

        $person = $this->createMock(Person::class);
        $person->method('getEmail')->willReturn('new@example.com');
        $person->method('getId')->willReturn(2);

        $this->personRepository->expects($this->once())
            ->method('create')
            ->willReturnCallback(function($p) use ($person) {
                return $person;
            });

        $user = $this->createMock(User::class);
        $user->method('isActive')->willReturn(true);
        $user->method('getId')->willReturn(2);
        $user->method('getPerson')->willReturn($person);

        $this->userRepository->expects($this->once())
            ->method('create')
            ->willReturnCallback(function($u) use ($user) {
                return $user;
            });

        $this->jwtService->method('generateAccessToken')->willReturn('access_token_new');
        $this->jwtService->method('generateRefreshToken')->willReturn('refresh_token_new');
        $this->jwtService->method('getAccessTokenExpire')->willReturn(3600);

        $result = $this->useCase->execute($dto);

        $this->assertInstanceOf(LoginResponseDTO::class, $result);
        $this->assertEquals('access_token_new', $result->accessToken);
        $this->assertEquals('refresh_token_new', $result->refreshToken);
    }

    public function testLoginWithInactiveUserThrowsException(): void
    {
        $dto = new SocialLoginRequestDTO('google', 'valid_token');

        $this->googleVerifier->method('verify')->willReturn([
            'providerId' => '1234567890',
            'email' => 'inactive@example.com',
            'name' => 'Inactive User'
        ]);

        $user = $this->createMock(User::class);
        $user->method('isActive')->willReturn(false);

        $this->userRepository->method('findByEmail')->willReturn($user);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('A conta do usuário não está ativa.');

        $this->useCase->execute($dto);
    }

    public function testInvalidProviderThrowsException(): void
    {
        $dto = new SocialLoginRequestDTO('facebook', 'valid_token');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Provider não suportado');

        $this->useCase->execute($dto);
    }
}
