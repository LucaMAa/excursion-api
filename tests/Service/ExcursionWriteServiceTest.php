<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Dto\CreateExcursionRequestDto;
use App\Entity\Excursion;
use App\Service\ExcursionWriteService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class ExcursionWriteServiceTest extends TestCase
{
    public function testCreatePersistsTheExcursion(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('persist')->with(self::isInstanceOf(Excursion::class));
        $entityManager->expects(self::once())->method('flush');

        $service = new ExcursionWriteService($entityManager);

        $excursion = $service->create(
            new CreateExcursionRequestDto(
                'Tour del lago',
                'Descrizione',
                'Como',
                'Italia',
                '2026-11-01',
                10,
            ),
            new \DateTime('2026-11-01'),
        );

        self::assertSame('Tour del lago', $excursion->getTitle());
        self::assertSame('Como', $excursion->getCity());
    }
}
