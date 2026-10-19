<?php

declare(strict_types=1);

namespace App\Tests\Dto;

use App\Dto\BookingPersonDto;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class BookingPersonDtoTest extends TestCase
{
    public function testValidBookingPersonHasNoViolations(): void
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $person = new BookingPersonDto('Mario Rossi', 'mario@example.com', 2);

        self::assertCount(0, $validator->validate($person));
    }

    public function testInvalidEmailAndSeatCountAreRejected(): void
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $person = new BookingPersonDto('Mario Rossi', 'not-an-email', 0);

        self::assertCount(2, $validator->validate($person));
    }
}
