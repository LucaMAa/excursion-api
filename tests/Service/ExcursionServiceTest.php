<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Excursion;
use App\Repository\BookingRepository;
use App\Repository\ExcursionRepository;
use App\Service\ExcursionReadService;
use App\Service\GeocodingService;
use App\Service\WeatherForecastService;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class ExcursionServiceTest extends TestCase
{
    public function testFindExcursionsAddsWeatherOnlyWithinTheSixteenDayForecast(): void
    {
        $today = new \DateTimeImmutable('today');
        $forecastDates = [];
        $temperatures = [];
        $precipitation = [];
        $weatherCodes = [];
        for ($day = 0; $day < 16; ++$day) {
            $forecastDates[] = $today->modify(sprintf('+%d days', $day))->format('Y-m-d');
            $temperatures[] = 10 + $day;
            $precipitation[] = 0;
            $weatherCodes[] = 1;
        }

        $httpClient = new MockHttpClient([
            new MockResponse(json_encode([
                'results' => [[
                    'latitude' => 45.808,
                    'longitude' => 9.085,
                ]],
            ], JSON_THROW_ON_ERROR)),
            new MockResponse(json_encode([
                'daily' => [
                    'time' => $forecastDates,
                    'temperature_2m_min' => $temperatures,
                    'temperature_2m_max' => $temperatures,
                    'precipitation_sum' => $precipitation,
                    'weather_code' => $weatherCodes,
                ],
            ], JSON_THROW_ON_ERROR)),
        ]);

        $excursions = [
            $this->createExcursion(1, $today->modify('-1 day')),
            $this->createExcursion(2, $today),
            $this->createExcursion(3, $today->modify('+15 days')),
            $this->createExcursion(4, $today->modify('+16 days')),
        ];
        $excursionRepository = $this->createMock(ExcursionRepository::class);
        $excursionRepository->method('findAll')->willReturn($excursions);
        $bookingRepository = $this->createMock(BookingRepository::class);
        $bookingRepository->method('getReservedSeatsForExcursions')->willReturn([]);

        $service = new ExcursionReadService(
            $bookingRepository,
            $excursionRepository,
            new GeocodingService($httpClient, new NullLogger()),
            new WeatherForecastService($httpClient, new NullLogger()),
        );

        $responses = $service->findExcursions();

        self::assertNull($responses[0]->forecast);
        self::assertNotNull($responses[1]->forecast);
        self::assertNotNull($responses[2]->forecast);
        self::assertNull($responses[3]->forecast);
    }

    private function createExcursion(int $id, \DateTimeImmutable $date): Excursion
    {
        return new Excursion()
            ->setId($id)
            ->setDate(new \DateTime($date->format('Y-m-d')))
            ->setCity('Como')
            ->setCapacity(10)
            ->setCreatedAt(new \DateTimeImmutable());
    }
}
