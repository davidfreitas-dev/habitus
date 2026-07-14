<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\DTO\Auth\PasswordResetResponseDTO;
use App\Application\DTO\Auth\ValidateResetCodeRequestDTO;
use App\Domain\Exception\NotFoundException;
use App\Domain\Repository\PasswordResetRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\ValueObject\Code;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;

class ValidateResetCodeUseCase
{
    public function __construct(
        private readonly PasswordResetRepositoryInterface $passwordResetRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function execute(ValidateResetCodeRequestDTO $request): PasswordResetResponseDTO
    {
        $user = $this->userRepository->findByEmail($request->email);
        if (!$user instanceof \App\Domain\Entity\User) {
            $this->logger->warning('Falha na validação do código de redefinição de senha para e-mail desconhecido', ['email' => $request->email]);

            throw new NotFoundException('E-mail ou código inválido.');
        }

        $passwordReset = $this->passwordResetRepository->findByCode(Code::from($request->code));

        if (!$passwordReset || $passwordReset->getUserId() !== $user->getId()) {
            $this->logger->warning('Falha na validação do código de redefinição de senha', [
                'email' => $request->email,
                'code' => $request->code,
            ]);

            throw new NotFoundException('E-mail ou código inválido.');
        }

        return new PasswordResetResponseDTO(
            id: $passwordReset->getId(),
            userId: $passwordReset->getUserId(),
            code: $passwordReset->getCode()->value,
            expiresAt: $passwordReset->getExpiresAt()->format(DateTimeImmutable::ATOM),
            usedAt: $passwordReset->getUsedAt()?->format(DateTimeImmutable::ATOM),
            ipAddress: $passwordReset->getIpAddress(),
        );
    }
}
