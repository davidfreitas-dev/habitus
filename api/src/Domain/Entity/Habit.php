<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Exception\ValidationException;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use JsonSerializable;

class Habit implements JsonSerializable
{
    private const int MAX_TITLE_LENGTH = 255;

    private ?int $id = null;
    private string $title;

    /**
     * @var Collection<int, HabitWeekDay>
     */
    private readonly Collection $habitWeekDays;

    public function __construct(
        string $title,
        private readonly User $user,
        private ?string $reminderTime = null,
        private readonly ?DateTimeImmutable $createdAt = new DateTimeImmutable(),
        private ?DateTimeImmutable $updatedAt = new DateTimeImmutable(),
    ) {
        $this->validateTitle($title);
        $this->title = $title;
        $this->habitWeekDays = new ArrayCollection();
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

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->validateTitle($title);
        $this->title = $title;
        $this->touch();
    }

    public function getReminderTime(): ?string
    {
        return $this->reminderTime;
    }

    public function setReminderTime(?string $reminderTime): void
    {
        $this->reminderTime = $reminderTime;
        $this->touch();
    }

    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @return Collection<int, HabitWeekDay>
     */
    public function getHabitWeekDays(): Collection
    {
        return $this->habitWeekDays;
    }

    public function addHabitWeekDay(HabitWeekDay $habitWeekDay): void
    {
        if (!$this->habitWeekDays->contains($habitWeekDay)) {
            $this->habitWeekDays->add($habitWeekDay);

            $this->touch();
        }
    }

    public function removeHabitWeekDay(HabitWeekDay $habitWeekDay): void
    {
        if ($this->habitWeekDays->removeElement($habitWeekDay)) {
            $this->touch();
        }
    }

    public function clearHabitWeekDays(): void
    {
        $this->habitWeekDays->clear();
        $this->touch();
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
            'title' => $this->title,
            'user_id' => $this->user->getId(),
            'reminder_time' => $this->reminderTime,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public static function fromArray(array $data, \App\Domain\Repository\UserRepositoryInterface $userRepository): self
    {
        $user = $userRepository->findById($data['user_id']);
        if (!$user instanceof \App\Domain\Entity\User) {
            throw new \InvalidArgumentException(sprintf('User with ID %d not found.', $data['user_id']));
        }

        $habit = new self(
            $data['title'],
            $user,
            $data['reminder_time'] ?? null,
            new DateTimeImmutable($data['created_at']),
            new DateTimeImmutable($data['updated_at']),
        );

        if (isset($data['id'])) {
            $habit->setId($data['id']);
        }

        return $habit;
    }

    private function touch(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }

    private function validateTitle(string $title): void
    {
        $trimmedTitle = trim($title);

        if ($trimmedTitle === '' || $trimmedTitle === '0') {
            throw new ValidationException('Habit title cannot be empty.');
        }

        if (mb_strlen($trimmedTitle) > self::MAX_TITLE_LENGTH) {
            throw new ValidationException(
                sprintf('Habit title cannot exceed %d characters.', self::MAX_TITLE_LENGTH),
            );
        }
    }
}
