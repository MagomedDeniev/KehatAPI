<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Domain\Entity\DomainUser;
use App\Infrastructure\Doctrine\Entity\User;

final class UserFactory
{
    /**
     * @param list<string> $roles
     */
    public static function domainUser(
        ?int $id = 1,
        string $email = 'user@example.com',
        ?string $confirmedEmail = 'user@example.com',
        string $password = 'hashed-password',
        string $username = 'username',
        array $roles = ['ROLE_USER'],
        ?string $passwordToken = null,
        ?\DateTimeImmutable $passwordTokenExpiresAt = null,
        ?string $emailToken = null,
        ?\DateTimeImmutable $emailTokenExpiresAt = null,
        ?\DateTimeImmutable $registeredAt = null,
    ): DomainUser {
        return new DomainUser(
            id: $id,
            email: $email,
            confirmedEmail: $confirmedEmail,
            password: $password,
            username: $username,
            roles: $roles,
            passwordToken: $passwordToken,
            passwordTokenExpiresAt: $passwordTokenExpiresAt,
            emailToken: $emailToken,
            emailTokenExpiresAt: $emailTokenExpiresAt,
            registeredAt: $registeredAt ?? new \DateTimeImmutable('-1 day'),
        );
    }

    /**
     * @param list<string> $roles
     */
    public static function ormUser(
        ?int $id = 1,
        string $email = 'user@example.com',
        ?string $confirmedEmail = 'user@example.com',
        string $password = 'hashed-password',
        string $username = 'username',
        array $roles = ['ROLE_USER'],
        ?string $passwordToken = null,
        ?\DateTimeImmutable $passwordTokenExpiresAt = null,
        ?string $emailToken = null,
        ?\DateTimeImmutable $emailTokenExpiresAt = null,
        ?\DateTimeImmutable $registeredAt = null,
    ): User {
        $user = (new User())
            ->setEmail($email)
            ->setConfirmedEmail($confirmedEmail)
            ->setPassword($password)
            ->setUsername($username)
            ->setRoles($roles)
            ->setPasswordToken($passwordToken)
            ->setPasswordTokenExpiresAt($passwordTokenExpiresAt)
            ->setEmailToken($emailToken)
            ->setEmailTokenExpiresAt($emailTokenExpiresAt)
            ->setRegisteredAt($registeredAt ?? new \DateTimeImmutable('-1 day'));

        self::forceId($user, $id);

        return $user;
    }

    public static function forceId(User $user, ?int $id): void
    {
        $property = new \ReflectionProperty($user, 'id');
        $property->setValue($user, $id);
    }
}
