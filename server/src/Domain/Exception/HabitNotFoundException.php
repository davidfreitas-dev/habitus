<?php

declare(strict_types=1);

namespace App\Domain\Exception;

use Exception;
use Throwable;

class HabitNotFoundException extends Exception
{
    public function __construct(string $message = "Habit not found", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode(): int
    {
        return 404;
    }
}
