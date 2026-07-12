<?php

declare(strict_types=1);

namespace App\Application\DTO\ErrorLog;

use JsonSerializable;

class ErrorLogListResponseDTO implements JsonSerializable
{
    /**
     * @param ErrorLogResponseDTO[] $errorLogs
     */
    public function __construct(
        public readonly array $errorLogs,
        public readonly int $total,
        public readonly int $page,
        public readonly int $perPage,
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'error_logs' => \array_map(static fn (ErrorLogResponseDTO $dto): array => $dto->jsonSerialize(), $this->errorLogs),
            'total' => $this->total,
            'page' => $this->page,
            'per_page' => $this->perPage,
        ];
    }
}
