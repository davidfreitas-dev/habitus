<?php

declare(strict_types=1);

namespace App\Application\DTO\Habit;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class UpdateHabitRequestDTO
{
    /**
     * @param HabitReminderItemDTO[] $reminders
     */
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
        #[Assert\Type(type: 'array', message: 'Os lembretes devem ser um array.')]
        #[Assert\Valid]
        public readonly array $reminders = [],
    ) {
    }

    #[Assert\Callback]
    public function validateReminders(ExecutionContextInterface $context): void
    {
        HabitReminderItemDTO::validateSchedule($this->reminders, $this->weekDays, $context);
    }

    public static function fromArray(array $data): self
    {
        $rawReminders = is_array($data['reminders'] ?? null) ? $data['reminders'] : [];
        $reminders = array_map(
            fn (mixed $item): HabitReminderItemDTO => HabitReminderItemDTO::fromArray(is_array($item) ? $item : []),
            $rawReminders,
        );

        return new self(
            title: $data['title'] ?? null,
            weekDays: $data['week_days'] ?? null,
            reminders: $reminders,
        );
    }

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'weekDays' => $this->weekDays,
            'reminders' => array_map(fn (HabitReminderItemDTO $r): array => $r->toArray(), $this->reminders),
        ];
    }
}
