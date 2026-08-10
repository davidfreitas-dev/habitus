<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\DTO\Auth\SocialLoginRequestDTO;
use App\Application\DTO\Auth\LoginResponseDTO;
use App\Domain\Entity\Person;
use App\Domain\Entity\Role;
use App\Domain\Entity\User;
use App\Domain\Enum\SocialProvider;
use App\Domain\Exception\AuthenticationException;
use App\Domain\Repository\PersonRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;
use App\Infrastructure\Security\GoogleTokenVerifier;
use App\Infrastructure\Security\JwtService;
use Exception;
use PDO;

class AuthenticateWithSocialProviderUseCase
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly GoogleTokenVerifier $googleVerifier,
        private readonly PersonRepositoryInterface $personRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly JwtService $jwtService,
        private readonly Role $defaultUserRole,
    ) {}

    public function execute(SocialLoginRequestDTO $dto): LoginResponseDTO
    {
        $provider = SocialProvider::tryFrom($dto->provider);
        if (!$provider) {
            throw new \InvalidArgumentException('Provider não suportado');
        }

        $identity = match ($provider) {
            SocialProvider::GOOGLE => $this->googleVerifier->verify($dto->idToken),
        };

        $email = $identity['email'];
        
        $user = $this->userRepository->findByEmail($email);

        if (!$user instanceof User) {
            $this->pdo->beginTransaction();
            try {
                // Check if person exists
                $person = $this->personRepository->findByEmail($email);
                
                if (!$person instanceof Person) {
                    $person = new Person(
                        name: $identity['name'] ?? 'Usuário',
                        email: $email,
                    );
                    $person = $this->personRepository->create($person);
                }

                // Create user (password is not strictly needed for social login, but we can set a random one or empty)
                $randomPassword = \bin2hex(\random_bytes(16)); // Random strong password
                
                $user = new User(
                    person: $person,
                    role: $this->defaultUserRole,
                    password: \password_hash($randomPassword, PASSWORD_DEFAULT),
                    isActive: true,
                    isVerified: true, // Social login verifies email inherently
                );

                $user = $this->userRepository->create($user);
                
                $this->pdo->commit();
            } catch (Exception $e) {
                $this->pdo->rollBack();
                throw $e;
            }
        }

        if (!$user->isActive()) {
            throw new AuthenticationException('A conta do usuário não está ativa.');
        }

        $accessToken = $this->jwtService->generateAccessToken($user->getId(), $user->getPerson()->getEmail());
        $refreshToken = $this->jwtService->generateRefreshToken($user->getId());

        return new LoginResponseDTO(
            accessToken: $accessToken,
            refreshToken: $refreshToken,
            tokenType: 'Bearer',
            expiresIn: $this->jwtService->getAccessTokenExpire(),
        );
    }
}
