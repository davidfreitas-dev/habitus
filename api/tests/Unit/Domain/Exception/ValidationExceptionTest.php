<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Exception;

use App\Domain\Exception\ValidationException;
use PHPUnit\Framework\TestCase;

class ValidationExceptionTest extends TestCase
{
    public function testGetStatusCode(): void
    {
        $exception = new ValidationException('Falha na validação');
        $this->assertEquals(422, $exception->getStatusCode());
    }

    public function testGetErrors(): void
    {
        $errors = ['field1' => 'Error on field 1'];
        $exception = new ValidationException('Falha na validação', $errors);
        $this->assertEquals($errors, $exception->getErrors());
    }

    public function testGetErrorsWhenNotProvided(): void
    {
        $exception = new ValidationException('Falha na validação');
        $this->assertEquals([], $exception->getErrors());
    }
}
