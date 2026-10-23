<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Booking;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Booking>
 */
class BookingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Booking::class);
    }

    public function getReservedSeatsForExcursion(int $excursionId): int
    {
        return (int) $this->createQueryBuilder('b')
            ->select('COALESCE(SUM(b.seats), 0)')
            ->andWhere('IDENTITY(b.excursion) = :excursionId')
            ->setParameter('excursionId', $excursionId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param list<string> $emails
     *
     * @return list<string>
     */
    public function findBookedEmailsForExcursion(int $excursionId, array $emails): array
    {
        if ([] === $emails) {
            return [];
        }

        return $this->createQueryBuilder('b')
            ->select('LOWER(b.customerEmail)')
            ->andWhere('IDENTITY(b.excursion) = :excursionId')
            ->andWhere('LOWER(b.customerEmail) IN (:emails)')
            ->setParameter('excursionId', $excursionId)
            ->setParameter('emails', $emails)
            ->getQuery()
            ->getSingleColumnResult();
    }

    /**
     * @param int[] $excursionIds
     *
     * @return array<int, int>
     */
    public function getReservedSeatsForExcursions(array $excursionIds): array
    {
        if ([] === $excursionIds) {
            return [];
        }

        $rows = $this->createQueryBuilder('b')
            ->select('e.id AS excursionId, SUM(b.seats) AS reservedSeats')
            ->join('b.excursion', 'e')
            ->andWhere('e.id IN (:excursionIds)')
            ->setParameter('excursionIds', $excursionIds)
            ->groupBy('e.id')
            ->getQuery()
            ->getScalarResult();

        $reservedSeatsByExcursion = [];
        foreach ($rows as $row) {
            $reservedSeatsByExcursion[(int) $row['excursionId']] = (int) $row['reservedSeats'];
        }

        return $reservedSeatsByExcursion;
    }
}
