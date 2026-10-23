<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\ExcursionResponseDto;
use App\Dto\WeatherForecastDto;
use App\Entity\Excursion;
use App\Repository\BookingRepository;
use App\Repository\ExcursionRepository;

final readonly class ExcursionReadService
{
    public function __construct(
        private BookingRepository $bookingRepository,
        private ExcursionRepository $excursionRepository,
        private GeocodingService $geocodingService,
        private WeatherForecastService $weatherForecastService,
    ) {
    }

    /**
     * @return list<ExcursionResponseDto>
     */
    public function findExcursions(int $limit = 10, int $offset = 0): array
    {
        $excursions = $this->excursionRepository->findAll($limit, $offset);
        $excursionIds = [];
        foreach ($excursions as $excursion) {
            $id = $excursion->getId();
            if (null === $id) {
                throw new \LogicException('A persisted excursion must have an ID.');
            }

            $excursionIds[] = $id;
        }

        $reservedSeatsByExcursion = $this->bookingRepository->getReservedSeatsForExcursions($excursionIds);
        $weatherByExcursionId = $this->getWeatherByExcursion($excursions);

        $responses = [];
        foreach ($excursions as $excursion) {
            $id = $excursion->getId();
            if (null === $id) {
                throw new \LogicException('A persisted excursion must have an ID.');
            }

            $availableSeats = max(
                0,
                $excursion->getCapacity() - ($reservedSeatsByExcursion[$id] ?? 0),
            );
            $responses[] = ExcursionResponseDto::fromExcursion(
                $excursion,
                $availableSeats,
                $weatherByExcursionId[$id] ?? null,
            );
        }

        return $responses;
    }

    /**
     * @param list<Excursion> $excursions
     *
     * @return array<int, WeatherForecastDto>
     */
    private function getWeatherByExcursion(array $excursions): array
    {
        $today = new \DateTimeImmutable('today');
        $lastForecastDate = $today->modify('+15 days');
        $forecastsByCity = [];
        $weatherByExcursionId = [];

        foreach ($excursions as $excursion) {
            $date = $excursion->getDate();
            $city = trim((string) $excursion->getCity());
            if (
                null === $date
                || $date < $today
                || $date > $lastForecastDate
                || '' === $city
            ) {
                continue;
            }

            $cityKey = strtolower($city);
            if (!array_key_exists($cityKey, $forecastsByCity)) {
                $coordinates = $this->geocodingService->findCoordinates($city);
                $forecastsByCity[$cityKey] = null === $coordinates
                    ? null
                    : $this->weatherForecastService->getForecast($coordinates);
            }

            $forecast = $forecastsByCity[$cityKey][$date->format('Y-m-d')] ?? null;
            if (null !== $forecast) {
                $id = $excursion->getId();
                if (null === $id) {
                    throw new \LogicException('A persisted excursion must have an ID.');
                }

                $weatherByExcursionId[$id] = $forecast;
            }
        }

        return $weatherByExcursionId;
    }
}
