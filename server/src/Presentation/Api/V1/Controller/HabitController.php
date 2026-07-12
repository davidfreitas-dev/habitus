<?php

declare(strict_types=1);

namespace App\Presentation\Api\V1\Controller;

use App\Application\DTO\Habit\CreateHabitRequestDTO;
use App\Application\DTO\Habit\HabitsByDayRequestDTO;
use App\Application\DTO\Habit\HabitStatsRequestDTO;
use App\Application\DTO\Habit\ToggleHabitRequestDTO;
use App\Application\DTO\Habit\UpdateHabitRequestDTO;
use App\Application\Service\ValidationService;
use App\Application\UseCase\CreateHabitUseCase;
use App\Application\UseCase\DeleteHabitUseCase;
use App\Application\UseCase\GetAllHabitsUseCase;
use App\Application\UseCase\GetHabitDetailsUseCase;
use App\Application\UseCase\GetHabitsByDayUseCase;
use App\Application\UseCase\GetHabitsSummaryUseCase;
use App\Application\UseCase\GetHabitStatsUseCase;
use App\Application\UseCase\ToggleHabitUseCase;
use App\Application\UseCase\UpdateHabitUseCase;
use App\Domain\Exception\HabitAlreadyExistsException;
use App\Domain\Exception\HabitNotFoundException;
use App\Domain\Exception\NotFoundException;
use App\Domain\Exception\ValidationException;
use App\Infrastructure\Http\Response\JsonResponseFactory;
use DateTimeImmutable;
use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Throwable;

class HabitController
{
    public function __construct(
        private readonly CreateHabitUseCase $createHabitUseCase,
        private readonly GetHabitsByDayUseCase $getHabitsByDayUseCase,
        private readonly GetHabitsSummaryUseCase $getHabitsSummaryUseCase,
        private readonly GetHabitDetailsUseCase $getHabitDetailsUseCase,
        private readonly UpdateHabitUseCase $updateHabitUseCase,
        private readonly DeleteHabitUseCase $deleteHabitUseCase,
        private readonly ToggleHabitUseCase $toggleHabitUseCase,
        private readonly GetHabitStatsUseCase $getHabitStatsUseCase,
        private readonly GetAllHabitsUseCase $getAllHabitsUseCase,
        private readonly ValidationService $validationService,
        private readonly JsonResponseFactory $jsonResponseFactory,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function getAll(Request $request): Response
    {
        try {
            $userId = (int) $request->getAttribute('user_id');
            if ($userId === 0) {
                return $this->jsonResponseFactory->fail(null, 'Usuário não autenticado.', 401);
            }

            $habits = $this->getAllHabitsUseCase->execute($userId);

            return $this->jsonResponseFactory->success($habits, 'Lista de hábitos obtida com sucesso.');
        } catch (Throwable $throwable) {
            $this->logger->error('Erro inesperado ao buscar todos os hábitos', ['exception' => $throwable]);
            return $this->jsonResponseFactory->error(
                'Ocorreu um erro inesperado. Por favor, tente novamente mais tarde.',
                null,
                500,
            );
        }
    }

    public function getStats(Request $request): Response
    {
        try {
            $userId = (int) $request->getAttribute('user_id');
            if ($userId === 0) {
                return $this->jsonResponseFactory->fail(null, 'Usuário não autenticado.', 401);
            }

            $queryParams = $request->getQueryParams();
            $dto = HabitStatsRequestDTO::fromArray($queryParams);

            $this->validationService->validate($dto);

            $date = $dto->date ? new DateTimeImmutable($dto->date) : null;

            $stats = $this->getHabitStatsUseCase->execute($userId, $dto->period, $date);

            return $this->jsonResponseFactory->success($stats, 'Estatísticas obtidas com sucesso.');
        } catch (ValidationException $e) {
            return $this->jsonResponseFactory->fail($e->getErrors(), $e->getMessage(), 400);
        } catch (Throwable $e) {
            $this->logger->error('Erro ao obter estatísticas de hábitos', [
                'exception' => $e,
                'user_id' => $request->getAttribute('user_id'),
            ]);

            return $this->jsonResponseFactory->error(
                'Ocorreu um erro ao obter as estatísticas. Por favor, tente novamente mais tarde.',
                null,
                500,
            );
        }
    }

    public function create(Request $request): Response
    {
        try {
            $userId = (int) $request->getAttribute('user_id');
            if ($userId === 0) {
                return $this->jsonResponseFactory->fail(null, 'Usuário não autenticado.', 401);
            }

            $data = $request->getParsedBody();
            $dto = CreateHabitRequestDTO::fromArray($data);

            $this->validationService->validate($dto);

            $habitResponseDto = $this->createHabitUseCase->execute($dto, $userId);

            return $this->jsonResponseFactory->success(
                $habitResponseDto,
                'Hábito criado com sucesso.',
                201,
            );
        } catch (ValidationException $e) {
            $this->logger->warning('Falha na validação da criação de hábito', ['exception' => $e]);
            return $this->jsonResponseFactory->fail($e->getErrors(), $e->getMessage(), 400);
        } catch (HabitAlreadyExistsException $e) {
            $this->logger->warning('Tentativa de criar hábito duplicado', ['exception' => $e]);
            return $this->jsonResponseFactory->fail(null, $e->getMessage(), 409);
        } catch (NotFoundException $e) {
            $this->logger->warning('Usuário não encontrado ao criar hábito', ['exception' => $e]);
            return $this->jsonResponseFactory->fail(null, $e->getMessage(), 404);
        } catch (Throwable $e) {
            $this->logger->error('Erro inesperado ao criar hábito', ['exception' => $e]);
            return $this->jsonResponseFactory->error(
                'Ocorreu um erro inesperado. Por favor, tente novamente mais tarde.',
                null,
                500,
            );
        }
    }

    public function getByDay(Request $request): Response
    {
        try {
            $userId = (int) $request->getAttribute('user_id');
            if ($userId === 0) {
                return $this->jsonResponseFactory->fail(null, 'Usuário não autenticado.', 401);
            }

            $date = $request->getQueryParams()['date'] ?? '';
            $dto = new HabitsByDayRequestDTO($date);

            $this->validationService->validate($dto);

            $responseDto = $this->getHabitsByDayUseCase->execute($dto, $userId);

            return $this->jsonResponseFactory->success($responseDto, 'Hábitos do dia obtidos com sucesso.');

        } catch (ValidationException $e) {
            $this->logger->warning('Data inválida para buscar hábitos', ['exception' => $e]);
            return $this->jsonResponseFactory->fail($e->getErrors(), $e->getMessage(), 400);
        } catch (Throwable $e) {
            $this->logger->error('Erro inesperado ao buscar hábitos por dia', ['exception' => $e]);
            return $this->jsonResponseFactory->error(
                'Ocorreu um erro inesperado. Por favor, tente novamente mais tarde.',
                null,
                500,
            );
        }
    }

    public function getSummary(Request $request): Response
    {
        try {
            $userId = (int) $request->getAttribute('user_id');
            if ($userId === 0) {
                return $this->jsonResponseFactory->fail(null, 'Usuário não autenticado.', 401);
            }

            $dateString = $request->getQueryParams()['date'] ?? null;
            $date = $dateString ? new DateTimeImmutable($dateString) : null;

            $summary = $this->getHabitsSummaryUseCase->execute($userId, $date);

            return $this->jsonResponseFactory->success($summary, 'Resumo de hábitos obtido com sucesso.');
        } catch (Throwable $throwable) {
            $this->logger->error('Erro inesperado ao buscar resumo de hábitos', ['exception' => $throwable]);
            return $this->jsonResponseFactory->error(
                'Ocorreu um erro inesperado. Por favor, tente novamente mais tarde.',
                null,
                500,
            );
        }
    }

    public function getDetails(Request $request, Response $response, array $args): Response
    {
        try {
            $habitId = (int) $args['id']; // Assuming 'id' is the route parameter
            $userId = (int) $request->getAttribute('user_id');
            if ($userId === 0) {
                return $this->jsonResponseFactory->fail(null, 'Usuário não autenticado.', 401);
            }

            $habitResponseDto = $this->getHabitDetailsUseCase->execute($habitId, $userId);

            return $this->jsonResponseFactory->success($habitResponseDto, 'Hábito obtido com sucesso.');
        } catch (HabitNotFoundException $e) {
            $this->logger->warning('Hábito não encontrado', ['exception' => $e]);
            return $this->jsonResponseFactory->fail(null, $e->getMessage(), 404);
        } catch (Throwable $e) {
            $this->logger->error('Erro inesperado ao buscar detalhes do hábito', ['exception' => $e]);
            return $this->jsonResponseFactory->error(
                'Ocorreu um erro inesperado. Por favor, tente novamente mais tarde.',
                null,
                500,
            );
        }
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        try {
            $habitId = (int) $args['id'];
            $userId = (int) $request->getAttribute('user_id');
            if ($userId === 0) {
                return $this->jsonResponseFactory->fail(null, 'Usuário não autenticado.', 401);
            }

            $data = (array) $request->getParsedBody();
            $dto = UpdateHabitRequestDTO::fromArray($data);

            $this->validationService->validate($dto);

            $habitResponseDto = $this->updateHabitUseCase->execute($dto, $habitId, $userId);

            return $this->jsonResponseFactory->success($habitResponseDto, 'Hábito atualizado com sucesso.');
        } catch (ValidationException $e) {
            $this->logger->warning('Falha na validação da atualização de hábito', ['exception' => $e]);
            return $this->jsonResponseFactory->fail($e->getErrors(), $e->getMessage(), 400);
        } catch (HabitNotFoundException $e) {
            $this->logger->warning('Hábito não encontrado ao atualizar', ['exception' => $e]);
            return $this->jsonResponseFactory->fail(null, $e->getMessage(), 404);
        } catch (HabitAlreadyExistsException $e) {
            $this->logger->warning('Tentativa de atualizar hábito para um nome duplicado', ['exception' => $e]);
            return $this->jsonResponseFactory->fail(null, $e->getMessage(), 409);
        } catch (Throwable $e) {
            $this->logger->error('Erro inesperado ao atualizar hábito', ['exception' => $e]);
            return $this->jsonResponseFactory->error(
                'Ocorreu um erro inesperado. Por favor, tente novamente mais tarde.',
                null,
                500,
            );
        }
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        try {
            $habitId = (int) $args['id'];
            $userId = (int) $request->getAttribute('user_id');
            if ($userId === 0) {
                return $this->jsonResponseFactory->fail(null, 'Usuário não autenticado.', 401);
            }

            $this->deleteHabitUseCase->execute($habitId, $userId);

            return $this->jsonResponseFactory->success(null, 'Hábito deletado com sucesso.', 204);
        } catch (HabitNotFoundException $e) {
            $this->logger->warning('Hábito não encontrado ao deletar', ['exception' => $e]);
            return $this->jsonResponseFactory->fail(null, $e->getMessage(), 404);
        } catch (Throwable $e) {
            $this->logger->error('Erro inesperado ao deletar hábito', ['exception' => $e]);
            return $this->jsonResponseFactory->error(
                'Ocorreu um erro inesperado. Por favor, tente novamente mais tarde.',
                null,
                500,
            );
        }
    }

    public function toggle(Request $request, Response $response, array $args): Response
    {
        try {
            $habitId = (int) $args['id'];
            $userId = (int) $request->getAttribute('user_id');
            if ($userId === 0) {
                return $this->jsonResponseFactory->fail(null, 'Usuário não autenticado.', 401);
            }

            $data = (array) $request->getParsedBody();
            $dto = ToggleHabitRequestDTO::fromArray($data);

            $this->validationService->validate($dto);

            try {
                $date = new DateTimeImmutable($dto->date);
            } catch (Exception $e) {
                return $this->jsonResponseFactory->fail(null, 'Formato de data inválido.', 400);
            }

            $isCompleted = $this->toggleHabitUseCase->execute($habitId, $userId, $date);
            $message = $isCompleted ? 'Hábito marcado com sucesso.' : 'Hábito desmarcado com sucesso.';

            return $this->jsonResponseFactory->success(null, $message);
        } catch (ValidationException $e) {
            $this->logger->warning('Falha na validação do toggle de hábito', ['exception' => $e]);
            return $this->jsonResponseFactory->fail($e->getErrors(), $e->getMessage(), 400);
        } catch (HabitNotFoundException $e) {
            $this->logger->warning('Hábito não encontrado ao alterar status', ['exception' => $e]);
            return $this->jsonResponseFactory->fail(null, $e->getMessage(), 404);
        } catch (Throwable $e) {
            $this->logger->error('Erro inesperado ao alterar status do hábito', ['exception' => $e]);
            return $this->jsonResponseFactory->error(
                'Ocorreu um erro inesperado. Por favor, tente novamente mais tarde.',
                null,
                500,
            );
        }
    }
}
