<?php

declare(strict_types=1);

namespace App\Dto;

use App\Entity\Excursion;

final class ExcursionResponseDto
{
    public function __construct(
        public readonly ?int $id,
        public readonly ?string $title,
        public readonly ?string $description,
        public readonly ?string $city,
        public readonly ?string $country,
        public readonly string $date,
        public readonly int $capacity,
        public readonly string $created_at,
    ) {
    }

    public static function fromExcursion(Excursion $excursion): self
    {
        return new self(
            id: $excursion->getId(),
            title: $excursion->getTitle(),
            description: $excursion->getDescription(),
            city: $excursion->getCity(),
            country: $excursion->getCountry(),
            date: $excursion->getDate()->format('Y-m-d'),
            capacity: $excursion->getCapacity(),
            created_at: $excursion->getCreatedAt()->format('Y-m-d H:i:s'),
        );
    }

    /**
     * @param Excursion[] $excursions
     *
     * @return self[]
     */
    public static function fromExcursions(array $excursions): array
    {
        return array_map(self::fromExcursion(...), $excursions);
    }
}
