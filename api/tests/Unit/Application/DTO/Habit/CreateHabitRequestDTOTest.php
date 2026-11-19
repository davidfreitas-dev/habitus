<?php

declare(strict_types=1);

namespace Tests\Unit\Application\DTO\Habit;

use App\Application\DTO\Habit\CreateHabitRequestDTO;
use App\Application\Service\ValidationService;
use App\Domain\Exception\ValidationException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

class CreateHabitRequestDTOTest extends TestCase
{
    private ValidationService $validationService;

    protected function setUp(): void
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $this->validationService = new ValidationService($validator);
    }

    public function testAllowsMultipleRemindersPerDayUpToSixAndDifferentTimesPerDay(): void
    {
        $dto = CreateHabitRequestDTO::fromArray([
            'title' => 'Beber 2L de água',
            'week_days' => [1, 2],
            'reminders' => [
                ['week_day' => 1, 'time' => '08:00'],
                ['week_day' => 1, 'time' => '10:00'],
                ['week_day' => 1, 'time' => '12:00'],
                ['week_day' => 1, 'time' => '14:00'],
                ['week_day' => 1, 'time' => '16:00'],
                ['week_day' => 1, 'time' => '18:00'],
                ['week_day' => 2, 'time' => '09:30'],
            ],
            'created_at' => '2026-11-19 08:00:00',
        ]);

        $this->validationService->validate($dto);
        $this->assertCount(7, $dto->reminders);
    }

    public function testRejectsMoreThanSixRemindersOnSameDay(): void
    {
        $this->expectException(ValidationException::class);

        $dto = CreateHabitRequestDTO::fromArray([
            'title' => 'Beber 2L de água',
            'week_days' => [1],
            'reminders' => [
                ['week_day' => 1, 'time' => '08:00'],
                ['week_day' => 1, 'time' => '10:00'],
                ['week_day' => 1, 'time' => '12:00'],
                ['week_day' => 1, 'time' => '14:00'],
                ['week_day' => 1, 'time' => '16:00'],
                ['week_day' => 1, 'time' => '18:00'],
                ['week_day' => 1, 'time' => '20:00'],
            ],
            'created_at' => '2026-11-19 08:00:00',
        ]);

        $this->validationService->validate($dto);
    }

    public function testRejectsDuplicateReminderTimeOnSameDay(): void
    {
        $this->expectException(ValidationException::class);

        $dto = CreateHabitRequestDTO::fromArray([
            'title' => 'Beber 2L de água',
            'week_days' => [1],
            'reminders' => [
                ['week_day' => 1, 'time' => '08:00'],
                ['week_day' => 1, 'time' => '08:00'],
            ],
            'created_at' => '2026-11-19 08:00:00',
        ]);

        $this->validationService->validate($dto);
    }

    public function testRejectsReminderForInactiveWeekDay(): void
    {
        $this->expectException(ValidationException::class);

        $dto = CreateHabitRequestDTO::fromArray([
            'title' => 'Beber 2L de água',
            'week_days' => [1, 3],
            'reminders' => [
                ['week_day' => 2, 'time' => '08:00'],
            ],
            'created_at' => '2026-11-19 08:00:00',
        ]);

        $this->validationService->validate($dto);
    }
}
