<?php

declare(strict_types=1);

namespace App\Dto;

final class CreateExcursionRequestDto
{
    public function __construct(
        public readonly string $title,
        public readonly string $description,
        public readonly string $city,
        public readonly string $country,
        public readonly string $date,
        public readonly int $capacity,
    ) {
    }
}
