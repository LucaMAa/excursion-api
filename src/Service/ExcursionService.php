<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\CreateExcursionRequestDto;
use App\Dto\ExcursionResponseDto;
use App\Entity\Excursion;
use App\Repository\BookingRepository;
use App\Repository\ExcursionRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * ExcursionService class.
 */
final class ExcursionService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly BookingRepository $bookingRepository,
        private readonly ExcursionRepository $excursionRepository,
    ) {
    }

    /**
     * @return list<ExcursionResponseDto>
     */
    public function findExcursions(int $limit = 10, int $offset = 0): array
    {
        $excursions = $this->excursionRepository->findAll($limit, $offset);
        $excursionIds = [];
        foreach ($excursions as $excursion) {
            $id = $excursion->getId();
            if (null === $id) {
                throw new \LogicException('A persisted excursion must have an ID.');
            }

            $excursionIds[] = $id;
        }

        $reservedSeatsByExcursion = $this->bookingRepository->getReservedSeatsForExcursions($excursionIds);

        $responses = [];
        foreach ($excursions as $excursion) {
            $id = $excursion->getId();
            if (null === $id) {
                throw new \LogicException('A persisted excursion must have an ID.');
            }

            $availableSeats = max(
                0,
                $excursion->getCapacity() - ($reservedSeatsByExcursion[$id] ?? 0),
            );
            $responses[] = ExcursionResponseDto::fromExcursion($excursion, $availableSeats);
        }

        return $responses;
    }

    public function create(CreateExcursionRequestDto $data, \DateTime $date): Excursion
    {
        $excursion = new Excursion();
        $excursion->setTitle($data->getTitle());
        $excursion->setDescription($data->getDescription());
        $excursion->setCity($data->getCity());
        $excursion->setCountry($data->getCountry());
        $excursion->setDate($date);
        $excursion->setCapacity($data->getCapacity());

        $this->entityManager->persist($excursion);
        $this->entityManager->flush();

        return $excursion;
    }
}
