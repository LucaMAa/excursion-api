<?php

declare(strict_types=1);

namespace App\Tests\Dto;

use App\Dto\RegisterRequestDto;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class RegisterRequestDtoTest extends TestCase
{
    public function testValidRegistrationHasNoViolations(): void
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $request = new RegisterRequestDto('mario@example.com', 'secure-password-123');

        self::assertCount(0, $validator->validate($request));
    }

    public function testInvalidEmailAndShortPasswordAreRejected(): void
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $request = new RegisterRequestDto('not-an-email', 'short');

        self::assertCount(2, $validator->validate($request));
    }
}
