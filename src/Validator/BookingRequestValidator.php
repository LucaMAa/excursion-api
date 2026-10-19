<?php

declare(strict_types=1);

namespace App\Validator;

use App\Dto\CreateBookingRequestDto;
use App\Repository\BookingRepository;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class BookingRequestValidator
{
    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly BookingRepository $bookingRepository,
    ) {
    }

    public function validate(CreateBookingRequestDto $request, int $excursionId): void
    {
        $emails = [];

        foreach ($request->getBookingPersonDto() as $index => $person) {
            $violations = $this->validator->validate($person);
            if (count($violations) > 0) {
                $messages = [];
                foreach ($violations as $violation) {
                    $messages[] = sprintf(
                        '%s: %s',
                        $violation->getPropertyPath(),
                        $violation->getMessage(),
                    );
                }

                throw new BadRequestHttpException(sprintf('Invalid booking person at index %d: %s', $index, implode('; ', $messages)));
            }

            $email = strtolower(trim($person->getCustomerEmail()));
            if (isset($emails[$email])) {
                throw new ConflictHttpException('A customer can only have one booking per excursion.');
            }

            $emails[$email] = true;
        }

        if ([] !== $this->bookingRepository->findBookedEmailsForExcursion($excursionId, array_keys($emails))) {
            throw new ConflictHttpException('A customer can only have one booking per excursion.');
        }
    }
}
