<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Application\DTO\User\UpdateUserProfileRequestDTO;
use App\Application\DTO\User\UserResponseDTO;
use App\Application\Service\FileUploaderService;
use App\Domain\Entity\Person;
use App\Domain\Exception\NotFoundException;
use App\Domain\Exception\ValidationException;
use App\Domain\Repository\PersonRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\ValueObject\CpfCnpj;
use Exception;
use PDO;

class UpdateUserProfileUseCase
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly UserRepositoryInterface $userRepository,
        private readonly PersonRepositoryInterface $personRepository,
        private readonly FileUploaderService $fileUploaderService,
        private readonly string $uploadPath,
    ) {
    }

    public function execute(UpdateUserProfileRequestDTO $dto): UserResponseDTO
    {
        $user = $this->userRepository->findById($dto->userId);
        if (!$user instanceof \App\Domain\Entity\User) {
            throw new NotFoundException('Usuário não encontrado.');
        }

        $person = $user->getPerson();
        $oldAvatarUrl = $person->getAvatarUrl(); // Store old avatar URL for potential deletion

        $this->pdo->beginTransaction();

        try {
            // Update personal information
            if ($dto->name !== null) {
                $person->setName($dto->name);
            }

            if ($dto->email !== null) {
                // Check if email already exists for another person
                $existingPerson = $this->personRepository->findByEmail($dto->email);
                if ($existingPerson && $existingPerson->getId() !== $person->getId()) {
                    throw new ValidationException('O e-mail já está cadastrado por outro usuário.');
                }

                $person->setEmail($dto->email);
            }

            if ($dto->phone !== null) {
                $sanitizedPhone = \preg_replace('/[^0-9]/', '', $dto->phone);
                $person->setPhone($sanitizedPhone);
            }

            if ($dto->cpfcnpj !== null) {
                $sanitizedCpfCnpj = CpfCnpj::fromString($dto->cpfcnpj);
                // Check if CPF/CNPJ already exists for another person
                $existingPerson = $this->personRepository->findByCpfCnpj($sanitizedCpfCnpj);
                if ($existingPerson && $existingPerson->getId() !== $person->getId()) {
                    throw new ValidationException('O CPF/CNPJ já está cadastrado por outro usuário.');
                }

                $person->setCpfCnpj($sanitizedCpfCnpj);
            }

            // Handle profile image upload
            if ($dto->profileImage instanceof \Psr\Http\Message\UploadedFileInterface && $dto->profileImage->getError() === UPLOAD_ERR_OK) {
                $filename = $this->fileUploaderService->upload(
                    $dto->profileImage,
                    $this->uploadPath,
                    (string)$person->getId(),
                );

                $person->setAvatarUrl($filename);
            }

            $user->touch(); // Update the 'updatedAt' timestamp on the User entity

            $updatedUser = $this->userRepository->update($user);

            $this->pdo->commit();

            // Delete the old file only after successful commit
            if ($oldAvatarUrl && $oldAvatarUrl !== $updatedUser->getPerson()->getAvatarUrl()) {
                $this->fileUploaderService->delete($oldAvatarUrl, $this->uploadPath);
            }

            return new UserResponseDTO(
                id: $updatedUser->getId(),
                name: $updatedUser->getPerson()->getName(),
                email: $updatedUser->getPerson()->getEmail(),
                roleName: $updatedUser->getRole()->getName(),
                roleId: $updatedUser->getRole()->getId(),
                isActive: $updatedUser->isActive(),
                isVerified: $updatedUser->isVerified(),
                phone: $updatedUser->getPerson()->getPhone(),
                cpfcnpj: $updatedUser->getPerson()->getCpfCnpj()?->value(),
                avatarUrl: $updatedUser->getPerson()->getAvatarUrl(),
                createdAt: $updatedUser->getCreatedAt()->format('Y-m-d H:i:s'),
                updatedAt: $updatedUser->getUpdatedAt()->format('Y-m-d H:i:s'),
            );
        } catch (Exception $exception) {
            $this->pdo->rollBack();

            throw $exception;
        }
    }
}
