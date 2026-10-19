<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\CreateBookingRequestDto;
use App\Entity\Booking;
use App\Entity\Excursion;
use App\Repository\BookingRepository;
use App\Validator\BookingRequestValidator;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class BookingService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly BookingRepository $bookingRepository,
        private readonly BookingRequestValidator $bookingRequestValidator,
    ) {
    }

    /**
     * @return list<Booking>
     */
    public function create(int $excursionId, CreateBookingRequestDto $data): array
    {
        return $this->entityManager->wrapInTransaction(function () use ($excursionId, $data): array {
            $excursion = $this->entityManager->find(Excursion::class, $excursionId, LockMode::PESSIMISTIC_WRITE);
            if (!$excursion instanceof Excursion) {
                throw new NotFoundHttpException(sprintf('Excursion %d was not found.', $excursionId));
            }

            $people = $data->getBookingPersonDto();
            $this->bookingRequestValidator->validate($data, $excursionId);

            $requestedSeats = 0;
            foreach ($people as $person) {
                $requestedSeats += $person->getSeats();
            }

            $availableSeats = $excursion->getCapacity()
                - $this->bookingRepository->getReservedSeatsForExcursion($excursionId);

            if ($requestedSeats > $availableSeats) {
                throw new ConflictHttpException('Not enough seats are available for this excursion.');
            }

            $bookings = [];
            foreach ($people as $person) {
                $booking = new Booking();
                $booking
                    ->setCustomerName($person->getCustomerName())
                    ->setCustomerEmail($person->getCustomerEmail())
                    ->setSeats($person->getSeats())
                    ->setCreatedAt(new \DateTimeImmutable());
                $excursion->addBooking($booking);

                $this->entityManager->persist($booking);
                $bookings[] = $booking;
            }

            return $bookings;
        });
    }
}
