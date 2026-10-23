<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\CoordinatesDto;
use App\Dto\WeatherForecastDto;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class WeatherForecastService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @return array<string, WeatherForecastDto>|null forecasts indexed by local date (Y-m-d)
     */
    public function getForecast(CoordinatesDto $coordinates): ?array
    {
        try {
            $data = $this->httpClient->request(
                'GET',
                'https://api.open-meteo.com/v1/forecast',
                [
                    'query' => [
                        'latitude' => $coordinates->latitude,
                        'longitude' => $coordinates->longitude,
                        'daily' => 'temperature_2m_min,temperature_2m_max,precipitation_sum,weather_code',
                        'forecast_days' => 16,
                        'temperature_unit' => 'celsius',
                        'precipitation_unit' => 'mm',
                        'timezone' => 'auto',
                    ],
                ],
            )->toArray();
        } catch (ExceptionInterface $exception) {
            $this->logger->warning('Open-Meteo forecast request failed.', [
                'latitude' => $coordinates->latitude,
                'longitude' => $coordinates->longitude,
                'exception' => $exception,
            ]);

            return null;
        }

        return $this->parseForecast($data);
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, WeatherForecastDto>|null
     */
    private function parseForecast(array $data): ?array
    {
        $daily = $data['daily'] ?? null;
        if (!$this->hasValidDailyData($daily)) {
            $this->logger->warning('Open-Meteo returned an invalid daily forecast response.');

            return null;
        }

        $forecasts = [];
        foreach ($daily['time'] as $index => $date) {
            $temperatureMin = $daily['temperature_2m_min'][$index] ?? null;
            $temperatureMax = $daily['temperature_2m_max'][$index] ?? null;
            $precipitation = $daily['precipitation_sum'][$index] ?? null;
            $weatherCode = $daily['weather_code'][$index] ?? null;

            if (
                !is_string($date)
                || !is_numeric($temperatureMin)
                || !is_numeric($temperatureMax)
                || !is_numeric($precipitation)
                || !is_numeric($weatherCode)
            ) {
                continue;
            }

            $forecasts[$date] = new WeatherForecastDto(
                (float) $temperatureMin,
                (float) $temperatureMax,
                (float) $precipitation,
                (int) $weatherCode,
            );
        }

        return $forecasts;
    }

    /**
     * @phpstan-assert-if-true array{
     *     time: array<mixed>,
     *     temperature_2m_min: array<mixed>,
     *     temperature_2m_max: array<mixed>,
     *     precipitation_sum: array<mixed>,
     *     weather_code: array<mixed>
     * } $daily
     */
    private function hasValidDailyData(mixed $daily): bool
    {
        if (!is_array($daily)) {
            return false;
        }

        return array_all(['time', 'temperature_2m_min', 'temperature_2m_max', 'precipitation_sum', 'weather_code'], fn ($key) => is_array($daily[$key] ?? null));
    }
}
