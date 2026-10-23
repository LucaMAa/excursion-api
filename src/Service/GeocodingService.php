<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\CoordinatesDto;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class GeocodingService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
    ) {
    }

    public function findCoordinates(string $city): ?CoordinatesDto
    {
        try {
            $data = $this->httpClient->request(
                'GET',
                'https://geocoding-api.open-meteo.com/v1/search',
                [
                    'query' => [
                        'name' => $city,
                        'count' => 1,
                        'language' => 'it',
                        'format' => 'json',
                    ],
                ],
            )->toArray();
        } catch (ExceptionInterface $exception) {
            $this->logger->warning('Open-Meteo geocoding request failed.', [
                'city' => $city,
                'exception' => $exception,
            ]);

            return null;
        }

        $result = $data['results'][0] ?? null;
        if (
            !is_array($result)
            || !isset($result['latitude'], $result['longitude'])
            || !is_numeric($result['latitude'])
            || !is_numeric($result['longitude'])
        ) {
            return null;
        }

        return new CoordinatesDto(
            (float) $result['latitude'],
            (float) $result['longitude'],
        );
    }
}
