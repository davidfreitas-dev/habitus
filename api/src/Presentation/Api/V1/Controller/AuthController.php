<?php

declare(strict_types=1);

namespace App\Presentation\Api\V1\Controller;

use App\Application\DTO\Auth\ForgotPasswordRequestDTO;
use App\Application\DTO\Auth\LoginRequestDTO;
use App\Application\DTO\Auth\RegisterResponseDTO;
use App\Application\DTO\Auth\RegisterUserRequestDTO;
use App\Application\DTO\Auth\ResetPasswordRequestDTO;
use App\Application\DTO\Auth\SocialLoginRequestDTO;
use App\Application\DTO\Auth\ValidateResetCodeRequestDTO;
use App\Application\Exception\EmailSendingFailedException;
use App\Application\Service\ValidationService;
use App\Application\UseCase\AuthenticateWithSocialProviderUseCase;
use App\Application\UseCase\ForgotPasswordUseCase;
use App\Application\UseCase\LoginUseCase;
use App\Application\UseCase\RegisterUserUseCase;
use App\Application\UseCase\ResetPasswordUseCase;
use App\Application\UseCase\ValidateResetCodeUseCase;
use App\Application\UseCase\VerifyEmailUseCase;
use App\Domain\Enum\JsonResponseKey;
use App\Domain\Enum\JwtTokenType;
use App\Domain\Exception\AuthenticationException;
use App\Domain\Exception\ConflictException;
use App\Domain\Exception\NotFoundException;
use App\Domain\Exception\ValidationException;
use App\Domain\Repository\UserRepositoryInterface;
use App\Infrastructure\Http\Response\JsonResponseFactory;
use App\Infrastructure\Security\JwtService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Throwable;

class AuthController
{
    public function __construct(
        private readonly RegisterUserUseCase $registerUseCase,
        private readonly LoginUseCase $loginUseCase,
        private readonly ForgotPasswordUseCase $forgotPasswordUseCase,
        private readonly ResetPasswordUseCase $resetPasswordUseCase,
        private readonly ValidateResetCodeUseCase $validateResetCodeUseCase,
        private readonly VerifyEmailUseCase $verifyEmailUseCase,
        private readonly AuthenticateWithSocialProviderUseCase $authenticateWithSocialProviderUseCase,
        private readonly UserRepositoryInterface $userRepository,
        private readonly JwtService $jwtService,
        private readonly LoggerInterface $logger,
        private readonly JsonResponseFactory $jsonResponseFactory,
        private readonly ValidationService $validationService,
    ) {
    }

    public function register(Request $request): Response
    {
        try {
            $data = $request->getParsedBody();
            $dto = RegisterUserRequestDTO::fromArray($data);
            $this->validationService->validate($dto);

            $userResponseDto = $this->registerUseCase->execute($dto); // Changed variable name

            $accessToken = $this->jwtService->generateAccessToken($userResponseDto->id, $userResponseDto->email);
            $refreshToken = $this->jwtService->generateRefreshToken($userResponseDto->id);
            $expiresIn = $this->jwtService->getAccessTokenExpire();

            $registerResponseDto = new RegisterResponseDTO(
                userId: $userResponseDto->id,
                userName: $userResponseDto->name,
                userEmail: $userResponseDto->email,
                userRoleName: $userResponseDto->roleName,
                accessToken: $accessToken,
                refreshToken: $refreshToken,
                tokenType: 'Bearer',
                expiresIn: $expiresIn,
            );

            $responseData = [
                JsonResponseKey::ACCESS_TOKEN->value => $registerResponseDto->accessToken,
                JsonResponseKey::REFRESH_TOKEN->value => $registerResponseDto->refreshToken,
                JsonResponseKey::TOKEN_TYPE->value => $registerResponseDto->tokenType,
                JsonResponseKey::EXPIRES_IN->value => $registerResponseDto->expiresIn,
            ];

            $response = $this->jsonResponseFactory->success(
                $responseData,
                'Usuário registrado e logado com sucesso. Por favor, verifique seu e-mail para confirmar sua conta.',
                201,
            );
            return $this->withRefreshTokenCookie($response, $registerResponseDto->refreshToken);
        } catch (ConflictException $e) {
            $this->logger->warning('Conflito no registro de usuário', ['exception' => $e]);

            return $this->jsonResponseFactory->fail(null, $e->getMessage(), 409);
        } catch (ValidationException $e) {
            $this->logger->warning('Falha na validação do registro de usuário', ['exception' => $e]);

            return $this->jsonResponseFactory->fail($e->getErrors(), $e->getMessage(), 400);
        } catch (EmailSendingFailedException $e) {
            $this->logger->error('Falha ao enviar e-mail de boas-vindas', ['exception' => $e]);

            return $this->jsonResponseFactory->error(
                'Usuário registrado, mas falha ao enviar e-mail de boas-vindas.',
                null,
                500,
            );
        } catch (Throwable $e) {
            $this->logger->error('Ocorreu um erro inesperado durante o registro de usuário', ['exception' => $e]);

            return $this->jsonResponseFactory->error(
                'Ocorreu um erro inesperado. Por favor, tente novamente mais tarde.',
                null,
                500,
            );
        }
    }

    public function login(Request $request): Response
    {
        try {
            $dto = LoginRequestDTO::fromArray($request->getParsedBody());
            $this->validationService->validate($dto);
            $loginResponseDto = $this->loginUseCase->execute($dto);

            $responseData = [
                JsonResponseKey::ACCESS_TOKEN->value => $loginResponseDto->accessToken,
                JsonResponseKey::REFRESH_TOKEN->value => $loginResponseDto->refreshToken,
                JsonResponseKey::TOKEN_TYPE->value => $loginResponseDto->tokenType,
                JsonResponseKey::EXPIRES_IN->value => $loginResponseDto->expiresIn,
            ];

            $response = $this->jsonResponseFactory->success($responseData, 'Login bem-sucedido');
            return $this->withRefreshTokenCookie($response, $loginResponseDto->refreshToken);
        } catch (ValidationException $e) {
            $this->logger->warning('Falha na validação do login de usuário', ['exception' => $e]);

            return $this->jsonResponseFactory->fail($e->getErrors(), $e->getMessage(), 400);
        } catch (AuthenticationException $e) {
            $this->logger->warning('Falha na autenticação do login de usuário', ['exception' => $e]);

            return $this->jsonResponseFactory->fail(null, $e->getMessage(), 401);
        } catch (Throwable $e) {
            $this->logger->error('Ocorreu um erro inesperado durante o login de usuário', ['exception' => $e]);

            return $this->jsonResponseFactory->error(
                'Ocorreu um erro inesperado. Por favor, tente novamente mais tarde.',
                null,
                500,
            );
        }
    }

    public function socialLogin(Request $request, Response $response, array $args): Response
    {
        try {
            $provider = $args['provider'] ?? '';
            $data = $request->getParsedBody();
            $idToken = $data['idToken'] ?? '';

            if (empty($idToken)) {
                return $this->jsonResponseFactory->fail(null, 'idToken não fornecido', 400);
            }

            $dto = new SocialLoginRequestDTO($provider, $idToken);
            $loginResponseDto = $this->authenticateWithSocialProviderUseCase->execute($dto);

            $responseData = [
                JsonResponseKey::ACCESS_TOKEN->value => $loginResponseDto->accessToken,
                JsonResponseKey::REFRESH_TOKEN->value => $loginResponseDto->refreshToken,
                JsonResponseKey::TOKEN_TYPE->value => $loginResponseDto->tokenType,
                JsonResponseKey::EXPIRES_IN->value => $loginResponseDto->expiresIn,
            ];

            $res = $this->jsonResponseFactory->success($responseData, 'Login social bem-sucedido');
            return $this->withRefreshTokenCookie($res, $loginResponseDto->refreshToken);
        } catch (\App\Application\Exception\InvalidSocialTokenException $e) {
            $this->logger->warning('Falha na autenticação social', ['exception' => $e]);
            return $this->jsonResponseFactory->fail(null, $e->getMessage(), 401);
        } catch (AuthenticationException $e) {
            $this->logger->warning('Falha na autenticação do login social de usuário', ['exception' => $e]);
            return $this->jsonResponseFactory->fail(null, $e->getMessage(), 401);
        } catch (\InvalidArgumentException $e) {
            $this->logger->warning('Provedor social inválido', ['exception' => $e]);
            return $this->jsonResponseFactory->fail(null, 'Provedor não suportado', 400);
        } catch (Throwable $e) {
            $this->logger->error('Ocorreu um erro inesperado durante o login social', ['exception' => $e]);
            return $this->jsonResponseFactory->error(
                'Ocorreu um erro inesperado. Por favor, tente novamente mais tarde.',
                null,
                500,
            );
        }
    }

    public function refresh(Request $request): Response
    {
        try {
            $cookies = $request->getCookieParams();
            $data = $request->getParsedBody();
            $refreshToken = $cookies['refresh_token'] ?? $data['refresh_token'] ?? '';
            $decoded = $this->jwtService->validateToken($refreshToken);

            if (JwtTokenType::REFRESH->value !== $decoded->type) {
                return $this->jsonResponseFactory->fail(null, 'Token de atualização inválido', 401);
            }

            if (!$this->jwtService->isRefreshTokenValid($decoded->jti)) {
                return $this->jsonResponseFactory->fail(null, 'Token de atualização foi revogado.', 401);
            }

            $user = $this->userRepository->findById((int)$decoded->sub);
            if (!$user instanceof \App\Domain\Entity\User) {
                return $this->jsonResponseFactory->fail(null, 'Usuário não encontrado.', 404);
            }

            // Invalidate the old refresh token
            $this->jwtService->revokeRefreshToken($decoded->jti);

            // Generate new access and refresh tokens
            $newAccessToken = $this->jwtService->generateAccessToken($user->getId(), $user->getPerson()->getEmail());
            $newRefreshToken = $this->jwtService->generateRefreshToken($user->getId());

            $tokenData = [
                JsonResponseKey::ACCESS_TOKEN->value => $newAccessToken,
                JsonResponseKey::REFRESH_TOKEN->value => $newRefreshToken,
                JsonResponseKey::TOKEN_TYPE->value => 'Bearer',
                JsonResponseKey::EXPIRES_IN->value => $this->jwtService->getAccessTokenExpire(),
            ];

            $response = $this->jsonResponseFactory->success($tokenData, 'Token atualizado com sucesso');
            return $this->withRefreshTokenCookie($response, $newRefreshToken);
        } catch (\App\Domain\Exception\AuthenticationException $e) {
            return $this->jsonResponseFactory->fail(null, $e->getMessage(), 401);
        } catch (Throwable $e) {
            $this->logger->error('Ocorreu um erro inesperado durante a atualização do token', ['exception' => $e]);
            return $this->jsonResponseFactory->error(
                'Ocorreu um erro inesperado. Por favor, tente novamente mais tarde.',
                null,
                500,
            );
        }
    }

    public function logout(Request $request): Response
    {
        try {
            $jti = $request->getAttribute('token_jti');
            $exp = $request->getAttribute('token_exp');
            $this->jwtService->blockToken($jti, $exp);

            $response = $this->jsonResponseFactory->success(null, 'Logout bem-sucedido');
            return $this->withClearRefreshTokenCookie($response);
        } catch (Throwable $throwable) {
            $this->logger->error('Ocorreu um erro inesperado durante o logout', ['exception' => $throwable]);
            return $this->jsonResponseFactory->error(
                'Ocorreu um erro inesperado. Por favor, tente novamente mais tarde.',
                null,
                500,
            );
        }
    }

    public function forgotPassword(Request $request): Response
    {
        $data = $request->getParsedBody();
        $email = $data['email'] ?? '';
        $ipAddress = $request->getServerParams()['REMOTE_ADDR'] ?? 'UNKNOWN';

        try {
            $forgotPasswordRequest = new ForgotPasswordRequestDTO($email, $ipAddress);
            $this->validationService->validate($forgotPasswordRequest);
            $this->forgotPasswordUseCase->execute($forgotPasswordRequest);

            return $this->jsonResponseFactory->success(
                null,
                'Se este e-mail existir, um e-mail de redefinição de senha foi enviado.',
            );
        } catch (EmailSendingFailedException $e) {
            $this->logger->error('Failed to send password reset email', ['exception' => $e]);

            return $this->jsonResponseFactory->error(
                'Falha ao enviar e-mail de redefinição de senha. Por favor, tente novamente mais tarde.',
                null,
                500,
            );
        } catch (ValidationException $e) {
            $this->logger->warning('Falha na validação de "esqueceu a senha"', ['exception' => $e]);

            return $this->jsonResponseFactory->fail($e->getErrors(), $e->getMessage(), 400);
        } catch (Throwable $e) {
            $this->logger->error('Ocorreu um erro inesperado durante a redefinição de senha', ['exception' => $e]);

            return $this->jsonResponseFactory->error(
                'Ocorreu um erro inesperado. Por favor, tente novamente mais tarde.',
                null,
                500,
            );
        }
    }

    public function validateResetCode(Request $request): Response
    {
        $data = $request->getParsedBody();
        $email = $data['email'] ?? '';
        $code = $data['code'] ?? '';

        try {
            $validateRequest = new ValidateResetCodeRequestDTO($email, $code);
            $this->validationService->validate($validateRequest);
            $this->validateResetCodeUseCase->execute($validateRequest);
            return $this->jsonResponseFactory->success(null, 'Código é válido');
        } catch (NotFoundException $e) {
            $this->logger->warning('Tentativa de validação de código de redefinição inválido', ['exception' => $e]);

            return $this->jsonResponseFactory->fail(null, $e->getMessage(), 404);
        } catch (ValidationException $e) {
            $this->logger->warning('Entrada de código de redefinição inválida', ['exception' => $e]);

            return $this->jsonResponseFactory->fail($e->getErrors(), $e->getMessage(), 400);
        } catch (Throwable $e) {
            $this->logger->error('Ocorreu um erro inesperado durante a validação do código', ['exception' => $e]);

            return $this->jsonResponseFactory->error(
                'Ocorreu um erro inesperado. Por favor, tente novamente mais tarde.',
                null,
                500,
            );
        }
    }

    public function resetPassword(Request $request): Response
    {
        try {
            $resetPasswordDto = ResetPasswordRequestDTO::fromArray($request->getParsedBody());
            $this->validationService->validate($resetPasswordDto);

            $validateRequest = new ValidateResetCodeRequestDTO(
                $resetPasswordDto->email,
                $resetPasswordDto->code,
            );

            $passwordResetResponseDto = $this->validateResetCodeUseCase->execute($validateRequest);

            $this->resetPasswordUseCase->execute($passwordResetResponseDto, $resetPasswordDto);

            return $this->jsonResponseFactory->success(null, 'Senha redefinida com sucesso');
        } catch (NotFoundException $e) {
            $this->logger->warning('Redefinição de senha falhou devido a código inválido', ['exception' => $e]);

            return $this->jsonResponseFactory->fail(null, $e->getMessage(), 404);
        } catch (ValidationException $e) {
            $this->logger->warning('Redefinição de senha falhou devido a erro de validação', ['exception' => $e]);

            return $this->jsonResponseFactory->fail($e->getErrors(), $e->getMessage(), 400);
        } catch (Throwable $e) {
            $this->logger->error('Ocorreu um erro inesperado durante a redefinição de senha', ['exception' => $e]);

            return $this->jsonResponseFactory->error(
                'Ocorreu um erro inesperado. Por favor, tente novamente mais tarde.',
                null,
                500,
            );
        }
    }

    public function verifyEmail(Request $request): Response
    {
        $token = $request->getQueryParams()['token'] ?? '';

        if (empty($token)) {
            return $this->jsonResponseFactory->fail(null, 'Token de verificação está faltando.', 400);
        }

        try {
            $result = $this->verifyEmailUseCase->execute($token);

            $message = $result->wasAlreadyVerified()
                ? 'E-mail já verificado.'
                : 'E-mail verificado com sucesso.';

            return $this->jsonResponseFactory->success(
                $result->getTokenData(),
                $message,
            );
        } catch (NotFoundException $e) {
            $this->logger->warning('Falha na verificação de e-mail: ' . $e->getMessage(), ['exception' => $e]);
            return $this->jsonResponseFactory->fail(null, $e->getMessage(), 404);
        } catch (ValidationException $e) {
            $this->logger->warning('Falha na verificação de e-mail: ' . $e->getMessage(), ['exception' => $e]);

            return $this->jsonResponseFactory->fail(null, $e->getMessage(), 400);
        } catch (Throwable $e) {
            $this->logger->error('Ocorreu um erro inesperado durante a verificação de e-mail', ['exception' => $e]);

            return $this->jsonResponseFactory->error(
                'Ocorreu um erro inesperado. Por favor, tente novamente mais tarde.',
                null,
                500,
            );
        }
    }

    /**
     * Rota publica de fallback para o navegador. Processa o token e renderiza um HTML amigavel.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function verifyEmailHtml(Request $request, Response $response): Response
    {
        $token = $request->getQueryParams()['token'] ?? '';
        $success = false;
        $message = '';

        if (empty($token)) {
            $message = 'Token de verificação está faltando ou é inválido.';
        } else {
            try {
                $result = $this->verifyEmailUseCase->execute($token);
                $success = true;
                $message = $result->wasAlreadyVerified()
                    ? 'Seu e-mail já foi verificado anteriormente!'
                    : 'E-mail verificado com sucesso!';
            } catch (\Throwable $e) {
                $message = $e->getMessage();
            }
        }

        $html = $this->renderHtmlTemplate($success, $message, $token);

        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html; charset=utf-8');
    }

    /**
     * Renderiza o template HTML premium para exibicao no navegador.
     */
    private function renderHtmlTemplate(bool $success, string $message, string $token): string
    {
        $templatePath = __DIR__ . '/../../../../Presentation/templates/verify_email.php';

        $title = $success ? 'E-mail verificado!' : 'Falha na verificação';
        $statusClass = $success ? 'success' : 'error';
        $deeplinkUrl = 'habits://verify-email?token=' . \urlencode($token);

        $iconSvg = $success
            ? '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="width: 40px; height: 40px;"><polyline points="20 6 9 17 4 12"></polyline></svg>'
            : '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="width: 40px; height: 40px;"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>';

        // Prepara as variaveis a serem extraidas no escopo do template
        $data = [
            'success' => $success,
            'message' => $message,
            'title' => $title,
            'statusClass' => $statusClass,
            'deeplinkUrl' => $deeplinkUrl,
            'iconSvg' => $iconSvg,
        ];

        if (!\file_exists($templatePath)) {
            $this->logger->error('Template de verificacao de e-mail nao encontrado: ' . $templatePath);
            return 'Ocorreu um erro interno. Template nao encontrado.';
        }

        \extract($data);
        \ob_start();
        include $templatePath;
        return \ob_get_clean() ?: '';
    }

    private function withRefreshTokenCookie(Response $response, string $refreshToken): Response
    {
        $secure = isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] !== 'development' ? 'Secure;' : '';
        $maxAge = $this->jwtService->getRefreshTokenExpire();
        $cookieValue = sprintf(
            'refresh_token=%s; HttpOnly; %s SameSite=Lax; Path=/api; Max-Age=%d',
            $refreshToken,
            $secure,
            $maxAge,
        );
        return $response->withAddedHeader('Set-Cookie', $cookieValue);
    }

    private function withClearRefreshTokenCookie(Response $response): Response
    {
        $secure = isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] !== 'development' ? 'Secure;' : '';
        $cookieValue = sprintf(
            'refresh_token=; HttpOnly; %s SameSite=Lax; Path=/api; Expires=Thu, 01 Jan 1970 00:00:00 GMT',
            $secure,
        );
        return $response->withAddedHeader('Set-Cookie', $cookieValue);
    }
}
