<?php

declare(strict_types=1);

namespace App\Dto;

use App\Entity\Excursion;

final readonly class ExcursionResponseDto
{
    public ?int $id;
    public ?string $title;
    public ?string $description;
    public ?string $city;
    public ?string $country;
    public string $date;
    public int $capacity;
    public string $createdAt;

    private function __construct(
        Excursion $excursion,
        public int $availableSeats,
        public ?WeatherForecastDto $forecast,
    ) {
        $this->id = $excursion->getId();
        $this->title = $excursion->getTitle();
        $this->description = $excursion->getDescription();
        $this->city = $excursion->getCity();
        $this->country = $excursion->getCountry();
        $this->date = $excursion->getDate()->format('Y-m-d');
        $this->capacity = $excursion->getCapacity();
        $this->createdAt = $excursion->getCreatedAt()->format('Y-m-d H:i:s');
    }

    public static function fromExcursion(
        Excursion $excursion,
        int $availableSeats,
        ?WeatherForecastDto $forecast = null,
    ): self {
        return new self($excursion, $availableSeats, $forecast);
    }
}
