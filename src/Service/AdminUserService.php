<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class AdminUserService
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function createOrPromote(string $email, string $password): User
    {
        $email = strtolower(trim($email));
        $user = $this->userRepository->findOneBy(['email' => $email]);

        if (!$user instanceof User) {
            $user = new User()->setEmail($email);
            $this->entityManager->persist($user);
        }

        $user->setRoles([...$user->getRoles(), 'ROLE_ADMIN']);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        $this->entityManager->flush();

        return $user;
    }
}
