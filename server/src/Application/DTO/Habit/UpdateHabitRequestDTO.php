<?php

declare(strict_types=1);

namespace App\Application\DTO\Habit;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateHabitRequestDTO
{
    public function __construct(
        #[Assert\NotBlank(message: 'O título não pode ser vazio.')]
        #[Assert\Length(
            min: 2,
            max: 255,
            minMessage: 'O título deve ter pelo menos {{ limit }} caracteres.',
            maxMessage: 'O título não pode ter mais de {{ limit }} caracteres.',
        )]
        public readonly ?string $title,
        #[Assert\NotBlank(message: 'Os dias da semana são obrigatórios.')]
        #[Assert\Type(type: 'array', message: 'Os dias da semana devem ser um array.')]
        #[Assert\Count(min: 1, minMessage: 'Selecione ao menos um dia da semana.')]
        #[Assert\All([
            new Assert\Range(notInRangeMessage: 'O dia da semana deve ser entre 0 e 6.', min: 0, max: 6),
            new Assert\Type(type: 'integer', message: 'Cada dia da semana deve ser um número inteiro.'),
        ])]
        public readonly ?array $weekDays,
        #[Assert\Regex(
            pattern: '/^([0-1]\d|2[0-3]):[0-5]\d$/',
            message: 'O horário do lembrete deve estar no formato HH:mm.',
        )]
        public readonly ?string $reminderTime,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            title: $data['title'] ?? null,
            weekDays: $data['week_days'] ?? null,
            reminderTime: $data['reminder_time'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'weekDays' => $this->weekDays,
            'reminder_time' => $this->reminderTime,
        ];
    }
}
