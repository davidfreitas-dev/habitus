<?php

declare(strict_types=1);

namespace App\Application\Exception;

use App\Domain\Exception\AuthenticationException;

class InvalidSocialTokenException extends AuthenticationException
{
    public function __construct(string $message = 'Token social inválido.', int $code = 401, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
