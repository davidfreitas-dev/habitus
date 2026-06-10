<?php

declare(strict_types=1);

namespace Tests\Unit\Application\UseCase;

use App\Application\DTO\Habit\HabitsSummaryResponseDTO;
use App\Application\UseCase\GetHabitsSummaryUseCase;
use App\Domain\Repository\HabitRepositoryInterface;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class GetHabitsSummaryUseCaseTest extends TestCase
{
    private HabitRepositoryInterface&MockObject $habitRepository;

    private GetHabitsSummaryUseCase $getHabitsSummaryUseCase;

    public function testShouldReturnHabitSummarySuccessfully(): void
    {
        $userId = 1;
        $summaryData = [
            'date' => new DateTimeImmutable()->format('Y-m-d'), // Use current date for testability
            'completed' => 5,
            'total' => 10,
        ];

        $this->habitRepository->expects($this->once())->method('getHabitsSummary')->with($userId)->willReturn([$summaryData]);

        $response = $this->getHabitsSummaryUseCase->execute($userId);

        $this->assertInstanceOf(HabitsSummaryResponseDTO::class, $response);
        $this->assertCount(1, $response->items);
        $this->assertEquals($summaryData['date'], $response->items[0]->date);
        $this->assertEquals(5, $response->items[0]->completed);
        $this->assertEquals(10, $response->items[0]->total);
    }

    public function testShouldReturnZeroSummaryIfNoSummaryFound(): void
    {
        $userId = 1;

        $this->habitRepository->expects($this->once())->method('getHabitsSummary')->with($userId)->willReturn([]);

        $response = $this->getHabitsSummaryUseCase->execute($userId);

        $this->assertInstanceOf(HabitsSummaryResponseDTO::class, $response);
        $this->assertEmpty($response->items);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->habitRepository = $this->createMock(HabitRepositoryInterface::class);

        $this->getHabitsSummaryUseCase = new GetHabitsSummaryUseCase(
            $this->habitRepository,
        );
    }
}
