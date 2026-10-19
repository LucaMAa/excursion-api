<?php

declare(strict_types=1);

namespace App\Dto;

use App\Entity\Booking;

final class BookingResponseDto
{
    public function __construct(
        public readonly int $bookingId,
        public readonly int $excursionId,
        public readonly int $seats,
    ) {
    }

    public static function fromBooking(Booking $booking): self
    {
        $bookingId = $booking->getId();
        $excursionId = $booking->getExcursion()?->getId();
        $seats = $booking->getSeats();

        if (null === $bookingId || null === $excursionId || null === $seats) {
            throw new \LogicException('A persisted booking must have an ID, excursion, and seat count.');
        }

        return new self(
            bookingId: $bookingId,
            excursionId: $excursionId,
            seats: $seats,
        );
    }

    /**
     * @param list<Booking> $bookings
     *
     * @return list<self>
     */
    public static function fromBookings(array $bookings): array
    {
        return array_map(self::fromBooking(...), $bookings);
    }
}
