<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\BookingPersonDto;
use App\Dto\BookingResponseDto;
use App\Dto\CreateBookingRequestDto;
use App\Dto\QueryDto;
use App\Service\BookingService;
use App\Service\ExcursionReadService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class ExcursionController extends AbstractController
{
    public function __construct(
        private readonly ExcursionReadService $excursionReadService,
        private readonly BookingService $bookingService,
    ) {
    }

    #[Route('/api/excursions', name: 'get_excursions', methods: ['GET'])]
    public function getExcursions(Request $request): JsonResponse
    {
        $query = $this->queryDto($request, QueryDto::class);
        if ($query->getLimit() < 1 || $query->getOffset() < 0) {
            throw new BadRequestHttpException('The limit must be positive and the offset cannot be negative.');
        }

        $excursions = $this->excursionReadService->findExcursions(
            $query->getLimit(),
            $query->getOffset(),
        );

        return $this->dtoResponse([
            'data' => $excursions,
        ]);
    }

    #[Route(
        '/api/excursions/{id}/booking',
        name: 'create_excursion_booking',
        methods: ['POST'],
        requirements: ['id' => '\d+'],
    )]
    public function createBooking(int $id, Request $request): JsonResponse
    {
        $data = new CreateBookingRequestDto(
            $this->bodyDtoList($request, BookingPersonDto::class),
        );
        $bookings = $this->bookingService->create($id, $data);

        return $this->dtoResponse(
            BookingResponseDto::fromBookings($bookings),
            Response::HTTP_CREATED,
        );
    }
}
