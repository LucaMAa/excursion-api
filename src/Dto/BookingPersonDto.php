<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * BookingPersonDto class.
 */
final class BookingPersonDto
{
    public function __construct(
        private readonly string $customerName,
        #[Assert\Email]
        private readonly string $customerEmail,
        #[Assert\Positive]
        private readonly int $seats,
    ) {
    }

    public function getCustomerName(): string
    {
        return $this->customerName;
    }

    public function getCustomerEmail(): string
    {
        return $this->customerEmail;
    }

    public function getSeats(): int
    {
        return $this->seats;
    }
}
