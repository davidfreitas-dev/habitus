<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Domain\Entity\Person;
use App\Domain\Entity\Role;
use App\Domain\Entity\User;
use App\Domain\Entity\Habit;
use DateTimeImmutable;

trait EntityTestHelper
{
    private const DEFAULT_USER_ID = 1;

    private const DEFAULT_ROLE_ID = 1;

    private const DEFAULT_PERSON_NAME = 'John Doe';

    private const DEFAULT_PERSON_EMAIL = 'john.doe@example.com';

    private const DEFAULT_ROLE_NAME = 'user';

    private const DEFAULT_ROLE_DESCRIPTION = 'User role';

    private const DEFAULT_PASSWORD = 'password';

    private const DEFAULT_HABIT_TITLE = 'Read a book';

    private function setEntityId(object $entity, int $id): void
    {
        $reflection = new \ReflectionClass($entity);
        $property = $reflection->getProperty('id');

        // Skip if property is readonly (PHP 8.1+)
        if ($property->isReadOnly()) {
            return;
        }

        $property->setValue($entity, $id);
    }

    private function createPerson(array $data = []): Person
    {
        $defaults = [
            'id' => null,
            'name' => self::DEFAULT_PERSON_NAME,
            'email' => self::DEFAULT_PERSON_EMAIL,
            'phone' => null,
            'cpfcnpj' => null,
            'avatarUrl' => null,
            'createdAt' => new DateTimeImmutable(),
            'updatedAt' => new DateTimeImmutable(),
        ];
        $mergedData = array_merge($defaults, $data);

        $person = new Person(
            name: $mergedData['name'],
            email: $mergedData['email'],
            phone: $mergedData['phone'],
            cpfcnpj: $mergedData['cpfcnpj'],
            avatarUrl: $mergedData['avatarUrl'],
            createdAt: $mergedData['createdAt'],
            updatedAt: $mergedData['updatedAt'],
        );

        if ($mergedData['id'] !== null) {
            $this->setEntityId($person, $mergedData['id']);
        }

        return $person;
    }

    private function createRole(array $data = []): Role
    {
        $defaults = [
            'id' => self::DEFAULT_ROLE_ID,
            'name' => self::DEFAULT_ROLE_NAME,
            'description' => self::DEFAULT_ROLE_DESCRIPTION,
            'createdAt' => new DateTimeImmutable(),
            'updatedAt' => new DateTimeImmutable(),
        ];
        $mergedData = array_merge($defaults, $data);

        return new Role(
            $mergedData['id'],
            $mergedData['name'],
            $mergedData['description'],
            $mergedData['createdAt'],
            $mergedData['updatedAt']
        );
    }

    private function createUser(array $data = []): User
    {
        $personData = $data['personData'] ?? [];
        if (isset($data['id'])) {
            $personData['id'] = $data['id'];
        }

        $defaults = [
            'person' => $this->createPerson($personData),
            'role' => $this->createRole(),
            'password' => self::DEFAULT_PASSWORD,
            'isActive' => true,
            'isVerified' => false,
            'createdAt' => new DateTimeImmutable(),
            'updatedAt' => new DateTimeImmutable(),
        ];
        $mergedData = array_merge($defaults, $data);

        return new User(
            person: $mergedData['person'],
            role: $mergedData['role'],
            password: $mergedData['password'],
            isActive: $mergedData['isActive'],
            isVerified: $mergedData['isVerified'],
            createdAt: $mergedData['createdAt'],
            updatedAt: $mergedData['updatedAt'],
        );
    }

    private function createHabit(array $data = []): Habit
    {
        $defaults = [
            'id' => null,
            'user' => $this->createUser(['id' => self::DEFAULT_USER_ID]),
            'title' => self::DEFAULT_HABIT_TITLE,
            'reminderTime' => null,            // ✅ adicionar reminderTime nos defaults
            'createdAt' => new DateTimeImmutable(),
            'updatedAt' => new DateTimeImmutable(),
        ];
        $mergedData = array_merge($defaults, $data);

        $habit = new Habit(
            $mergedData['title'],
            $mergedData['user'],
            $mergedData['reminderTime'],       // ✅ passar reminderTime na posição correta
            $mergedData['createdAt'],
            $mergedData['updatedAt']
        );

        if ($mergedData['id'] !== null) {
            $this->setEntityId($habit, $mergedData['id']);
        }

        return $habit;
    }
}