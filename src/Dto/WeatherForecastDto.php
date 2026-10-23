<?php

declare(strict_types=1);

namespace App\Dto;

final readonly class WeatherForecastDto
{
    public function __construct(
        public float $temperatureMin,
        public float $temperatureMax,
        public float $precipitation,
        public int $weatherCode,
    ) {
    }
}
