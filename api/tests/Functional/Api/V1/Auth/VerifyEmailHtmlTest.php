<?php

declare(strict_types=1);

namespace Tests\Functional\Api\V1\Auth;

use Faker\Factory;
use DateTimeImmutable;
use App\Domain\Entity\Role;
use App\Domain\Entity\User;
use App\Domain\Entity\Person;
use App\Domain\ValueObject\CpfCnpj;
use App\Domain\Entity\UserVerification;
use App\Domain\Exception\NotFoundException;
use App\Domain\Repository\RoleRepositoryInterface;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\Repository\PersonRepositoryInterface;
use App\Domain\Repository\UserVerificationRepositoryInterface;
use Tests\Functional\FunctionalTestCase;
use Fig\Http\Message\StatusCodeInterface;

class VerifyEmailHtmlTest extends FunctionalTestCase
{
    private PersonRepositoryInterface $personRepository;

    private UserRepositoryInterface $userRepository;

    private UserVerificationRepositoryInterface $userVerificationRepository;

    private RoleRepositoryInterface $roleRepository;

    private \Faker\Generator $faker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->personRepository = $this->app->getContainer()->get(PersonRepositoryInterface::class);
        $this->userRepository = $this->app->getContainer()->get(UserRepositoryInterface::class);
        $this->userVerificationRepository = $this->app->getContainer()->get(UserVerificationRepositoryInterface::class);
        $this->roleRepository = $this->app->getContainer()->get(RoleRepositoryInterface::class);
        $this->faker = Factory::create('pt_BR');
    }

    public function testVerifyEmailHtmlWithValidTokenReturnsHtmlSuccessPage(): void
    {
        // Arrange
        $person = new Person(
            name: 'testuserhtml',
            email: 'test_html@example.com',
            cpfcnpj: CpfCnpj::fromString($this->faker->cpf())
        );
        $person = $this->personRepository->create($person);

        $role = $this->roleRepository->findByName('user');
        if (!$role instanceof Role) {
            throw new NotFoundException("Perfil 'user' não encontrado no banco de dados.");
        }

        $user = new User(
            person: $person,
            role: $role,
            password: 'hashedpassword',
            isActive: true,
            isVerified: false
        );
        $user = $this->userRepository->create($user);

        $token = \Ramsey\Uuid\Uuid::uuid4()->toString();
        $userVerification = new UserVerification(
            userId: $user->getId(),
            token: $token,
            expiresAt: new DateTimeImmutable('+1 hour')
        );
        $this->userVerificationRepository->create($userVerification);

        // Act
        $response = $this->sendRequest('GET', '/verify-email?token=' . $token);
        $response->getBody()->rewind();
        $body = $response->getBody()->getContents();

        // Assert
        $this->assertEquals(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        $this->assertStringContainsString('text/html', $response->getHeaderLine('Content-Type'));
        $this->assertStringContainsString('E-mail verificado!', $body);
        $this->assertStringContainsString('E-mail verificado com sucesso!', $body);
        $this->assertStringContainsString('habits://verify-email?token=' . $token, $body);

        $updatedUser = $this->userRepository->findById($user->getId());
        $this->assertTrue($updatedUser->isVerified());
    }

    public function testVerifyEmailHtmlWithInvalidTokenReturnsHtmlErrorPage(): void
    {
        // Act
        $response = $this->sendRequest('GET', '/verify-email?token=invalidtoken');
        $response->getBody()->rewind();
        $body = $response->getBody()->getContents();

        // Assert
        $this->assertEquals(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        $this->assertStringContainsString('text/html', $response->getHeaderLine('Content-Type'));
        $this->assertStringContainsString('Falha na verificação', $body);
        $this->assertStringContainsString('Token de verificação inválido', $body);
    }

    public function testVerifyEmailHtmlWithMissingTokenReturnsHtmlErrorPage(): void
    {
        // Act
        $response = $this->sendRequest('GET', '/verify-email');
        $response->getBody()->rewind();
        $body = $response->getBody()->getContents();

        // Assert
        $this->assertEquals(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        $this->assertStringContainsString('text/html', $response->getHeaderLine('Content-Type'));
        $this->assertStringContainsString('Falha na verificação', $body);
        $this->assertStringContainsString('Token de verificação está faltando', $body);
    }

    public function testAppleAppSiteAssociationReturnsJson(): void
    {
        $response = $this->sendRequest('GET', '/.well-known/apple-app-site-association');
        $response->getBody()->rewind();
        $body = $response->getBody()->getContents();
        $data = json_decode($body, true);

        $this->assertEquals(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        $this->assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertArrayHasKey('applinks', $data);
        $this->assertArrayHasKey('details', $data['applinks']);
    }

    public function testAssetLinksReturnsJson(): void
    {
        $response = $this->sendRequest('GET', '/.well-known/assetlinks.json');
        $response->getBody()->rewind();
        $body = $response->getBody()->getContents();
        $data = json_decode($body, true);

        $this->assertEquals(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        $this->assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertIsArray($data);
        $this->assertEquals('android_app', $data[0]['target']['namespace']);
        $this->assertEquals('br.dev.davidfreitas.habitus', $data[0]['target']['package_name']);
    }
}
