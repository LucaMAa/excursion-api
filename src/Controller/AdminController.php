<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\CreateExcursionRequestDto;
use App\Dto\ExcursionResponseDto;
use App\Service\ExcursionWriteService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin')]
final class AdminController extends AbstractController
{
    public function __construct(
        private readonly ExcursionWriteService $excursionWriteService,
    ) {
    }

    #[Route('/excursions', name: 'admin_create_excursion', methods: ['POST'])]
    public function createExcursion(Request $request): JsonResponse
    {
        $data = $this->bodyDto($request, CreateExcursionRequestDto::class);

        try {
            $date = new \DateTime($data->getDate());
        } catch (\Exception $exception) {
            throw new BadRequestHttpException('Field "date" has an invalid value.', $exception);
        }

        $excursion = $this->excursionWriteService->create($data, $date);

        return $this->dtoResponse(
            ExcursionResponseDto::fromExcursion($excursion, $excursion->getCapacity()),
            Response::HTTP_CREATED,
        );
    }
}
