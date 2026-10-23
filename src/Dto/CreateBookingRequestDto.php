<?php

declare(strict_types=1);

namespace App\Dto;

final readonly class CreateBookingRequestDto
{
    /**
     * @param list<BookingPersonDto> $bookingPersonDto
     */
    public function __construct(private array $bookingPersonDto)
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
