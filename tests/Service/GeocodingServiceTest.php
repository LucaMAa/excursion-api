<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Dto\CoordinatesDto;
use App\Service\GeocodingService;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class GeocodingServiceTest extends TestCase
{
    public function testFindCoordinatesReturnsTheFirstMatchingCity(): void
    {
        $service = new GeocodingService(
            new MockHttpClient([
                new MockResponse(json_encode([
                    'results' => [[
                        'latitude' => 45.808,
                        'longitude' => 9.085,
                    ]],
                ], JSON_THROW_ON_ERROR)),
            ]),
            new NullLogger(),
        );

        self::assertEquals(new CoordinatesDto(45.808, 9.085), $service->findCoordinates('Como'));
    }

    public function testFindCoordinatesReturnsNullWhenTheCityIsNotFound(): void
    {
        $service = new GeocodingService(
            new MockHttpClient([
                new MockResponse(json_encode(['results' => []], JSON_THROW_ON_ERROR)),
            ]),
            new NullLogger(),
        );

        self::assertNull($service->findCoordinates('Unknown city'));
    }
}
