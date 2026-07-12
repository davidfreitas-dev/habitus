<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\DTO\User\CreateUserAdminRequestDTO;
use App\Application\DTO\User\UserResponseDTO;
use App\Domain\Entity\Person;
use App\Domain\Entity\Role;
use App\Domain\Entity\User;
use App\Domain\Exception\ConflictException;
use App\Domain\Exception\NotFoundException;
use App\Domain\Repository\PersonRepositoryInterface;
use App\Domain\Repository\RoleRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\ValueObject\CpfCnpj;
use App\Infrastructure\Security\PasswordHasher;
use Exception;
use PDO;

class CreateUserAdminUseCase
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly PersonRepositoryInterface $personRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly RoleRepositoryInterface $roleRepository,
        private readonly PasswordHasher $passwordHasher,
    ) {
    }

    public function execute(CreateUserAdminRequestDTO $dto): UserResponseDTO
    {
        // Check if email already exists
        if ($this->personRepository->findByEmail($dto->email) instanceof \App\Domain\Entity\Person) {
            throw new ConflictException('O e-mail já está cadastrado');
        }

        // Check if CPF/CNPJ already exists
        if ($dto->cpfcnpj && $this->personRepository->findByCpfCnpj($dto->cpfcnpj)) {
            throw new ConflictException('O CPF/CNPJ já está cadastrado');
        }

        // Find the role, or throw an exception if it doesn't exist
        $role = $this->roleRepository->findByName($dto->roleName);
        if (!$role instanceof Role) {
            throw new NotFoundException(sprintf("O perfil '%s' não foi encontrado.", $dto->roleName));
        }

        $this->pdo->beginTransaction();

        try {
            // Create person
            $person = new Person(
                name: $dto->name,
                email: $dto->email,
                phone: $dto->phone,
                cpfcnpj: $dto->cpfcnpj !== null ? CpfCnpj::fromString($dto->cpfcnpj) : null,
            );

            $person = $this->personRepository->create($person);

            // Create user
            $hashedPassword = $this->passwordHasher->hash($dto->password);

            $user = new User(
                person: $person,
                role: $role,
                password: $hashedPassword,
                isActive: true,
                isVerified: true, // Admin-created users are pre-verified
            );

            $createdUser = $this->userRepository->create($user);

            $this->pdo->commit();
        } catch (Exception $exception) {
            $this->pdo->rollBack();

            throw $exception;
        }

        return new UserResponseDTO(
            id: $createdUser->getId(),
            name: $createdUser->getPerson()->getName(),
            email: $createdUser->getPerson()->getEmail(),
            roleName: $createdUser->getRole()->getName(),
            roleId: $createdUser->getRole()->getId(),
            isActive: $createdUser->isActive(),
            isVerified: $createdUser->isVerified(),
            phone: $createdUser->getPerson()->getPhone(),
            cpfcnpj: $createdUser->getPerson()->getCpfCnpj() instanceof \App\Domain\ValueObject\CpfCnpj ? $createdUser->getPerson()->getCpfCnpj()->value() : null,
            avatarUrl: null,
            createdAt: $createdUser->getCreatedAt()->format('Y-m-d H:i:s'),
            updatedAt: $createdUser->getUpdatedAt()->format('Y-m-d H:i:s'),
        );
    }
}
