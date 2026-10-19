<?php

declare(strict_types=1);

namespace App\Dto;

final class CreateBookingRequestDto
{
    /**
     * @param list<BookingPersonDto> $bookingPersonDto
     */
    public function __construct(private readonly array $bookingPersonDto)
    {
    }

    /**
     * @return list<BookingPersonDto>
     */
    public function getBookingPersonDto(): array
    {
        return $this->bookingPersonDto;
    }
}
