<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\CreateExcursionRequestDto;
use App\Dto\ExcursionQueryDto;
use App\Dto\ExcursionResponseDto;
use App\Repository\ExcursionRepository;
use App\Service\ExcursionService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class ExcursionController extends AbstractController
{
    public function __construct(
        private readonly ExcursionRepository $excursionRepository,
        private readonly ExcursionService $excursionService,
    ) {
    }

    #[Route('/api/excursions', name: 'get_excursions', methods: ['GET'])]
    public function getExcursions(Request $request): JsonResponse
    {
        $query = $this->queryDto($request, ExcursionQueryDto::class);
        if ($query->getLimit() < 1 || $query->getOffset() < 0) {
            throw new BadRequestHttpException('The limit must be positive and the offset cannot be negative.');
        }

        $excursions = $this->excursionRepository->findAll($query->getLimit(), $query->getOffset());

        return $this->dtoResponse([
            'data' => ExcursionResponseDto::fromExcursions($excursions),
        ]);
    }

    #[Route('/api/excursions', name: 'create_excursion', methods: ['POST'])]
    public function createExcursion(Request $request): JsonResponse
    {
        $data = $this->bodyDto($request, CreateExcursionRequestDto::class);

        try {
            $date = new \DateTime($data->date);
        } catch (\Exception $exception) {
            throw new BadRequestHttpException('Field "date" has an invalid value.', $exception);
        }

        $excursion = $this->excursionService->create($data, $date);

        return $this->dtoResponse(
            ExcursionResponseDto::fromExcursion($excursion),
            Response::HTTP_CREATED,
        );
    }
}