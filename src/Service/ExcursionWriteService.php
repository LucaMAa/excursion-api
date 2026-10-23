<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\CreateExcursionRequestDto;
use App\Entity\Excursion;
use Doctrine\ORM\EntityManagerInterface;

final readonly class ExcursionWriteService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
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
