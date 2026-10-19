<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    public function testEmailIsNormalizedAndUserRoleIsGrantedByDefault(): void
    {
        $user = (new User())->setEmail('  Mario@Example.COM ');

        self::assertSame('mario@example.com', $user->getEmail());
        self::assertSame('mario@example.com', $user->getUserIdentifier());
        self::assertSame(['ROLE_USER'], $user->getRoles());
    }

    public function testAdminRoleIsPreservedAlongsideTheDefaultUserRole(): void
    {
        $user = (new User())->setRoles(['ROLE_ADMIN']);

        self::assertSame(['ROLE_ADMIN', 'ROLE_USER'], $user->getRoles());
    }

    public function testSettingAnEmptyRoleListKeepsTheDefaultUserRole(): void
    {
        $user = (new User())->setRoles([]);

        self::assertSame(['ROLE_USER'], $user->getRoles());
    }
}
