<?php

declare(strict_types=1);

namespace App\Dto;

final readonly class CoordinatesDto
{
    public function __construct(
        public float $latitude,
        public float $longitude,
    ) {
    }
}
