<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\CreateExcursionRequestDto;
use App\Entity\Excursion;
use Doctrine\ORM\EntityManagerInterface;

final class ExcursionService
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function create(CreateExcursionRequestDto $data, \DateTime $date): Excursion
    {
        $excursion = new Excursion();
        $excursion->setTitle($data->title);
        $excursion->setDescription($data->description);
        $excursion->setCity($data->city);
        $excursion->setCountry($data->country);
        $excursion->setDate($date);
        $excursion->setCapacity($data->capacity);
        $excursion->setCreatedAt(new \DateTime());

        $this->entityManager->persist($excursion);
        $this->entityManager->flush();

        return $excursion;
    }
}
