<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\DTO\User\UserListResponseDTO;
use App\Application\DTO\User\UserResponseDTO;
use App\Domain\Entity\User;
use App\Domain\Repository\UserRepositoryInterface;

class ListUsersUseCase
{
    public function __construct(private readonly UserRepositoryInterface $userRepository)
    {
    }

    public function execute(int $limit = 20, int $offset = 0): UserListResponseDTO
    {
        $users = $this->userRepository->findAll($limit, $offset);
        $total = $this->userRepository->count(); // Using the existing count method

        $userDTOs = \array_map(
            static fn (User $user): UserResponseDTO => new UserResponseDTO(
                id: $user->getId(),
                name: $user->getPerson()->getName(),
                email: $user->getPerson()->getEmail(),
                roleName: $user->getRole()->getName(),
                roleId: $user->getRole()->getId(),
                isActive: $user->isActive(),
                isVerified: $user->isVerified(),
                phone: $user->getPerson()->getPhone(),
                cpfcnpj: $user->getPerson()->getCpfCnpj() instanceof \App\Domain\ValueObject\CpfCnpj ? $user->getPerson()->getCpfCnpj()->value() : null,
                avatarUrl: $user->getPerson()->getAvatarUrl(),
                createdAt: $user->getCreatedAt()->format('Y-m-d H:i:s'),
                updatedAt: $user->getUpdatedAt()->format('Y-m-d H:i:s'),
            ),
            $users,
        );

        return new UserListResponseDTO(
            users: $userDTOs,
            total: $total,
            limit: $limit,
            offset: $offset,
        );
    }
}
