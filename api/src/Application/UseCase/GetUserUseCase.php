<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\DTO\User\UserResponseDTO;
use App\Domain\Exception\NotFoundException;
use App\Domain\Repository\UserRepositoryInterface;

class GetUserUseCase
{
    public function __construct(private readonly UserRepositoryInterface $userRepository)
    {
    }

    public function execute(int $userId): UserResponseDTO
    {
        $user = $this->userRepository->findById($userId);

        if (!$user instanceof \App\Domain\Entity\User) {
            throw new NotFoundException('Usuário não encontrado.');
        }

        return new UserResponseDTO(
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
        );
    }
}
