<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use DateTimeImmutable;
use JsonSerializable;

class Day implements JsonSerializable
{
    private ?int $id = null;

    public function __construct(private DateTimeImmutable $date, private readonly ?DateTimeImmutable $createdAt = new DateTimeImmutable(), private ?DateTimeImmutable $updatedAt = new DateTimeImmutable())
    {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        if ($this->id !== null) {
            return;
        }

        $this->id = $id;
    }

    public function getDate(): DateTimeImmutable
    {
        return $this->date;
    }

    public function setDay(DateTimeImmutable $date): void
    {
        if ($this->date->format('Y-m-d') !== $date->format('Y-m-d')) {
            $this->date = $date;
            $this->touch();
        }
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'date' => $this->date->format('Y-m-d'),
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public static function fromArray(array $data): self
    {
        $day = new self(
            new DateTimeImmutable($data['date']),
            new DateTimeImmutable($data['created_at']),
            new DateTimeImmutable($data['updated_at']),
        );

        if (isset($data['id'])) {
            $day->setId($data['id']);
        }

        return $day;
    }

    private function touch(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}
