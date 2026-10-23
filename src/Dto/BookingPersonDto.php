<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * BookingPersonDto class.
 */
final readonly class BookingPersonDto
{
    public function __construct(
        private string $customerName,
        #[Assert\Email]
        private string $customerEmail,
        #[Assert\Positive]
        private int $seats,
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
