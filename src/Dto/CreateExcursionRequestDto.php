<?php

declare(strict_types=1);

namespace App\Dto;

/**
 * CreateExcursionRequestDto class.
 */
final readonly class CreateExcursionRequestDto
{
    public function __construct(
        private string $title,
        private string $description,
        private string $city,
        private string $country,
        private string $date,
        private int $capacity,
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
