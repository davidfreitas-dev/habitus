<?php

declare(strict_types=1);

namespace Tests\Integration\Infrastructure\Persistence\MySQL;

use App\Domain\Entity\Person;
use App\Domain\ValueObject\CpfCnpj; 
use App\Infrastructure\Persistence\MySQL\PersonRepository;
use Faker\Factory;
use Tests\Integration\DatabaseTestCase;

class PersonRepositoryTest extends DatabaseTestCase
{
    private PersonRepository $personRepository;

    private \Faker\Generator $faker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->personRepository = new PersonRepository(self::$pdo);
        $this->faker = Factory::create('pt_BR');
    }

    private function createTestPerson(): Person
    {
        $person = new Person(
            name: $this->faker->name,
            email: $this->faker->unique()->email,
            phone: $this->faker->phoneNumber,
            cpfcnpj: CpfCnpj::fromString($this->faker->unique()->cpf), // Generate a valid CPF
        );

        return $this->personRepository->create($person);
    }

    public function testCreateAndFindById(): void
    {
        $createdPerson = $this->createTestPerson();

        $this->assertNotNull($createdPerson->getId(), 'O ID da pessoa não deve ser nulo após a criação');

        $foundPerson = $this->personRepository->findById($createdPerson->getId());

        $this->assertNotNull($foundPerson, 'Deve encontrar uma pessoa por ID');
        $this->assertEquals($createdPerson->getId(), $foundPerson->getId());
        $this->assertEquals($createdPerson->getEmail(), $foundPerson->getEmail());
        $this->assertEquals($createdPerson->getName(), $foundPerson->getName());
    }

    public function testFindByEmail(): void
    {
        $createdPerson = $this->createTestPerson();
        $email = $createdPerson->getEmail();

        $foundPerson = $this->personRepository->findByEmail($email);

        $this->assertNotNull($foundPerson, 'Deve encontrar uma pessoa por e-mail');
        $this->assertEquals($email, $foundPerson->getEmail());
    }

    public function testFindByCpfCnpj(): void
    {
        $createdPerson = $this->createTestPerson();
        $cpfCnpj = $createdPerson->getCpfCnpj();

        $foundPerson = $this->personRepository->findByCpfCnpj($cpfCnpj);

        $this->assertNotNull($foundPerson, 'Deve encontrar uma pessoa por CPF/CNPJ');
        $this->assertEquals($cpfCnpj, $foundPerson->getCpfCnpj());
    }

    public function testUpdate(): void
    {
        $createdPerson = $this->createTestPerson();

        $newName = 'Updated Name ' . $this->faker->name;
        $newEmail = $this->faker->unique()->email;

        $createdPerson->setName($newName);
        $createdPerson->setEmail($newEmail);

        $this->personRepository->update($createdPerson);

        $updatedPerson = $this->personRepository->findById($createdPerson->getId());


        $this->assertNotNull($updatedPerson, 'Pessoa atualizada deve ser encontrada');
        $this->assertEquals($newName, $updatedPerson->getName(), 'O nome da pessoa deve ser atualizado');
        $this->assertEquals($newEmail, $updatedPerson->getEmail(), 'O e-mail da pessoa deve ser atualizado');
    }

    public function testDelete(): void
    {
        $createdPerson = $this->createTestPerson();
        $personId = $createdPerson->getId();

        $deleted = $this->personRepository->delete($personId);
        $this->assertTrue($deleted, 'O método Delete deve retornar verdadeiro em caso de sucesso');

        $foundPerson = $this->personRepository->findById($personId);
        $this->assertNull($foundPerson, 'A pessoa não deve ser encontrada após a exclusão');
    }

    public function testFindByIdNotFound(): void
    {
        $foundPerson = $this->personRepository->findById(999999);
        $this->assertNull($foundPerson, 'Não deve encontrar uma pessoa com um ID não existente');
    }
}
