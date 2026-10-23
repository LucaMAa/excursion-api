<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\RegisterRequestDto;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class AuthController extends AbstractController
{
    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(
        Request $request,
        ValidatorInterface $validator,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
    ): JsonResponse {
        $data = $this->bodyDto($request, RegisterRequestDto::class);
        $violations = $validator->validate($data);
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()][] = $violation->getMessage();
            }

            return new JsonResponse(['errors' => $errors], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $email = strtolower(trim($data->getEmail()));
        if (null !== $userRepository->findOneBy(['email' => $email])) {
            return new JsonResponse(
                ['error' => 'An account with this email already exists.'],
                Response::HTTP_CONFLICT,
            );
        }

        $user = new User()->setEmail($email);
        $user->setPassword($passwordHasher->hashPassword($user, $data->getPassword()));
        $entityManager->persist($user);

        try {
            $entityManager->flush();
        } catch (UniqueConstraintViolationException) {
            return new JsonResponse(
                ['error' => 'An account with this email already exists.'],
                Response::HTTP_CONFLICT,
            );
        }

        if (null === $user->getId()) {
            throw new \LogicException('The registered user was not assigned an ID.');
        }

        return new JsonResponse(
            ['id' => $user->getId(), 'email' => $user->getEmail()],
            Response::HTTP_CREATED,
        );
    }
}
