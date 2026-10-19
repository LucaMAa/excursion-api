<?php

declare(strict_types=1);

namespace App\Dto;

/**
 * CreateExcursionRequestDto class.
 */
final class CreateExcursionRequestDto
{
    public function __construct(
        private readonly string $title,
        private readonly string $description,
        private readonly string $city,
        private readonly string $country,
        private readonly string $date,
        private readonly int $capacity,
    ) {
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function getCountry(): string
    {
        return $this->country;
    }

    public function getDate(): string
    {
        return $this->date;
    }

    public function getCapacity(): int
    {
        return $this->capacity;
    }
}
