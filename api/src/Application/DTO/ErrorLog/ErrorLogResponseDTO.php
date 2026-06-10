<?php

declare(strict_types=1);

namespace App\Application\DTO\ErrorLog;

use DateTimeImmutable;
use JsonSerializable;

class ErrorLogResponseDTO implements JsonSerializable
{
    public function __construct(
        public readonly int $id,
        public readonly string $severity,
        public readonly string $message,
        public readonly array $context,
        public readonly ?string $resolvedAt,
        public readonly ?int $resolvedByUserId,
        public readonly DateTimeImmutable $createdAt,
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'severity' => $this->severity,
            'message' => $this->message,
            'context' => $this->context,
            'resolved_at' => $this->resolvedAt,
            'resolved_by_user_id' => $this->resolvedByUserId,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
        ];
    }
}
