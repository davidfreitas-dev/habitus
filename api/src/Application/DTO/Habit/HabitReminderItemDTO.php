<?php

declare(strict_types=1);

namespace App\Application\DTO\Habit;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class HabitReminderItemDTO
{
    public const int MAX_REMINDERS_PER_DAY = 6;

    public function __construct(
        #[Assert\NotNull(message: 'O dia da semana do lembrete é obrigatório.')]
        #[Assert\Type(type: 'integer', message: 'O dia da semana do lembrete deve ser um número inteiro.')]
        #[Assert\Range(notInRangeMessage: 'O dia da semana deve ser entre 0 e 6.', min: 0, max: 6)]
        public readonly mixed $weekDay,
        #[Assert\NotBlank(message: 'O horário do lembrete é obrigatório.')]
        #[Assert\Regex(
            pattern: '/^([0-1]\d|2[0-3]):[0-5]\d$/',
            message: 'O horário do lembrete deve estar no formato HH:mm.',
        )]
        public readonly mixed $time,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            weekDay: $data['week_day'] ?? null,
            time: $data['time'] ?? '',
        );
    }

    public function toArray(): array
    {
        return [
            'week_day' => (int) $this->weekDay,
            'time' => (string) $this->time,
        ];
    }

    /**
     * Validates cross-field reminder rules (active weekdays, duplicates, and daily limit).
     *
     * @param HabitReminderItemDTO[] $reminders
     * @param int[]|null $weekDays
     */
    public static function validateSchedule(
        array $reminders,
        ?array $weekDays,
        ExecutionContextInterface $context,
    ): void {
        if ($reminders === [] || !is_array($weekDays)) {
            return;
        }

        $countByDay = [];
        $seen = [];

        foreach ($reminders as $index => $reminder) {
            if (!is_int($reminder->weekDay) || !is_string($reminder->time)) {
                continue;
            }

            if (!in_array($reminder->weekDay, $weekDays, true)) {
                $context->buildViolation('O lembrete só pode ser configurado para dias ativos do hábito.')
                    ->atPath(sprintf('reminders[%d].weekDay', $index))
                    ->addViolation();
                continue;
            }

            $key = $reminder->weekDay . ':' . $reminder->time;
            if (isset($seen[$key])) {
                $context->buildViolation('Não é permitido cadastrar horários duplicados no mesmo dia.')
                    ->atPath(sprintf('reminders[%d].time', $index))
                    ->addViolation();
                continue;
            }

            $seen[$key] = true;
            $countByDay[$reminder->weekDay] = ($countByDay[$reminder->weekDay] ?? 0) + 1;

            if ($countByDay[$reminder->weekDay] > self::MAX_REMINDERS_PER_DAY) {
                $context->buildViolation(
                    sprintf('Cada dia da semana pode ter no máximo %d lembretes.', self::MAX_REMINDERS_PER_DAY),
                )
                    ->atPath(sprintf('reminders[%d]', $index))
                    ->addViolation();
            }
        }
    }
}
