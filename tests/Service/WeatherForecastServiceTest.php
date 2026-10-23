<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Dto\CoordinatesDto;
use App\Dto\WeatherForecastDto;
use App\Service\WeatherForecastService;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class WeatherForecastServiceTest extends TestCase
{
    public function testGetForecastReturnsDailyWeatherIndexedByDate(): void
    {
        $query = [];
        $service = new WeatherForecastService(
            new MockHttpClient(
                function (string $method, string $url, array $options) use (&$query): MockResponse {
                    parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

                    return new MockResponse(json_encode([
                        'daily' => [
                            'time' => ['2026-10-23'],
                            'temperature_2m_min' => [8.2],
                            'temperature_2m_max' => [16.7],
                            'precipitation_sum' => [1.4],
                            'weather_code' => [61],
                        ],
                    ], JSON_THROW_ON_ERROR));
                },
            ),
            new NullLogger(),
        );

        self::assertEquals(
            ['2026-10-23' => new WeatherForecastDto(8.2, 16.7, 1.4, 61)],
            $service->getForecast(new CoordinatesDto(45.808, 9.085)),
        );
        self::assertSame('temperature_2m_min,temperature_2m_max,precipitation_sum,weather_code', $query['daily']);
        self::assertSame('16', $query['forecast_days']);
        self::assertSame('celsius', $query['temperature_unit']);
        self::assertSame('mm', $query['precipitation_unit']);
    }

    public function testGetForecastReturnsNullWhenTheProviderFails(): void
    {
        $service = new WeatherForecastService(
            new MockHttpClient([
                new MockResponse('', ['http_code' => 503]),
            ]),
            new NullLogger(),
        );

        self::assertNull($service->getForecast(new CoordinatesDto(45.808, 9.085)));
    }
}
