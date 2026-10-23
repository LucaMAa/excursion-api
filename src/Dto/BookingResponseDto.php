<?php

declare(strict_types=1);

namespace App\Dto;

use App\Entity\Booking;

final readonly class BookingResponseDto
{
    public function __construct(
        public int $bookingId,
        public int $excursionId,
        public int $seats,
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
