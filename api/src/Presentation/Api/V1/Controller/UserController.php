<?php

declare(strict_types=1);

namespace App\Presentation\Api\V1\Controller;

use App\Application\DTO\User\ChangePasswordRequestDTO;
use App\Application\DTO\User\UpdateUserProfileRequestDTO;
use App\Application\DTO\User\UserProfileResponseDTO;
use App\Application\Service\ValidationService;
use App\Application\UseCase\ChangePasswordUseCase;
use App\Application\UseCase\DeleteUserUseCase;
use App\Application\UseCase\UpdateUserProfileUseCase;
use App\Domain\Exception\ConflictException;
use App\Domain\Exception\NotFoundException;
use App\Domain\Exception\ValidationException;
use App\Domain\Repository\UserRepositoryInterface;
use App\Infrastructure\Http\Response\JsonResponseFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class UserController
{
    public function __construct(
        private readonly UpdateUserProfileUseCase $updateUserProfileUseCase,
        private readonly ChangePasswordUseCase $changePasswordUseCase,
        private readonly DeleteUserUseCase $deleteUserUseCase,
        private readonly UserRepositoryInterface $userRepository,
        private readonly JsonResponseFactory $jsonResponseFactory,
        private readonly ValidationService $validationService,
    ) {
    }

    public function get(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');
        $user = $this->userRepository->findById((int)$userId);
        if (!$user instanceof \App\Domain\Entity\User) {
            return $this->jsonResponseFactory->fail(message: 'Usuário não encontrado.', statusCode: 404);
        }

        $userProfileDTO = UserProfileResponseDTO::fromEntity($user);

        return $this->jsonResponseFactory->success($userProfileDTO);
    }

    public function update(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');
        $data = $request->getParsedBody();
        $uploadedFiles = $request->getUploadedFiles();
        $profileImage = $uploadedFiles['profile_image'] ?? null;

        try {
            $dto = UpdateUserProfileRequestDTO::fromArray(
                [
                    'name' => $data['name'] ?? null,
                    'email' => $data['email'] ?? null,
                    'phone' => $data['phone'] ?? null,
                    'cpfcnpj' => $data['cpfcnpj'] ?? null,
                ],
                $userId,
                $profileImage,
            );

            $this->validationService->validate($dto);
            $userResponseDto = $this->updateUserProfileUseCase->execute($dto);

            $responseData = $userResponseDto->jsonSerialize();

            return $this->jsonResponseFactory->success($responseData, 'Perfil atualizado com sucesso.');
        } catch (ValidationException $e) {
            return $this->jsonResponseFactory->fail($e->getErrors(), $e->getMessage(), 400);
        } catch (ConflictException $e) {
            return $this->jsonResponseFactory->fail(null, $e->getMessage(), 409);
        } catch (\Exception) {
            return $this->jsonResponseFactory->error('Ocorreu um erro inesperado. Por favor, tente novamente mais tarde.', null, 500);
        }
    }

    public function changePassword(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');
        $data = $request->getParsedBody();

        try {
            $dto = ChangePasswordRequestDTO::fromArray($data, $userId);
            $this->validationService->validate($dto);
            $this->changePasswordUseCase->execute($dto);

            return $this->jsonResponseFactory->success(message: 'Senha atualizada com sucesso.');
        } catch (ValidationException $e) {
            return $this->jsonResponseFactory->fail($e->getErrors(), $e->getMessage(), 400);
        } catch (NotFoundException $e) {
            return $this->jsonResponseFactory->fail(message: $e->getMessage(), statusCode: 404);
        } catch (\Exception) {
            // Log the exception for debugging purposes
            // $this->logger->error($e->getMessage());
            return $this->jsonResponseFactory->error('Ocorreu um erro inesperado. Por favor, tente novamente mais tarde.', null, 500);
        }
    }

    public function delete(Request $request): Response
    {
        $userId = $request->getAttribute('user_id');

        try {
            $user = $this->userRepository->findById((int)$userId);

            if (!$user instanceof \App\Domain\Entity\User) {
                return $this->jsonResponseFactory->fail(message: 'Usuário não encontrado.', statusCode: 404);
            }

            if ($user->getRole()->getName() === 'admin') {
                return $this->jsonResponseFactory->fail(null, 'Administradores não podem excluir a própria conta.', 403);
            }

            $this->deleteUserUseCase->execute($userId);

            return $this->jsonResponseFactory->success(message: 'Conta excluída com sucesso.');
        } catch (NotFoundException $notFoundException) {
            return $this->jsonResponseFactory->fail(message: $notFoundException->getMessage(), statusCode: 404);
        } catch (\Exception) {
            // Log the exception for debugging purposes
            // $this->logger->error($e->getMessage());
            return $this->jsonResponseFactory->error('Ocorreu um erro inesperado. Por favor, tente novamente mais tarde.', null, 500);
        }
    }
}
