<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Domain\Entity\DomainUser;
use App\Domain\Enum\GenderEnum;
use App\Infrastructure\Doctrine\Entity\User;

final class UserFactory
{
    public const VALID_PASSWORD_HASH = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
    public const VALID_PASSWORD_HASH_ALT = '$2y$10$wH0bR4mF0TAnr0fzf3Vx4u2X6qS32YgFzOU8Y9yQdrPqYv8Jf3M7K';
    public const VALID_EMAIL_TOKEN = 'email-token-1234567890123456789012';
    public const VALID_EMAIL_TOKEN_ALT = 'email-token-abcdefghijklmnopqrstuvwxyz';
    public const VALID_PASSWORD_TOKEN = 'password-token-1234567890123456789';
    public const VALID_PASSWORD_TOKEN_ALT = 'password-token-abcdefghijklmnopqrstuvwxyz';

    /**
     * @param list<string> $roles
     */
    public static function domainUser(
        ?int $id = 1,
        string $email = 'user@example.com',
        ?string $confirmedEmail = 'user@example.com',
        string $password = self::VALID_PASSWORD_HASH,
        string $username = 'username',
        array $roles = ['ROLE_USER'],
        ?string $passwordToken = null,
        ?\DateTimeImmutable $passwordTokenExpiresAt = null,
        ?string $emailToken = null,
        ?\DateTimeImmutable $emailTokenExpiresAt = null,
        ?\DateTimeImmutable $registeredAt = null,
        GenderEnum $gender = GenderEnum::MALE,
        ?\DateTimeImmutable $birthDate = null,
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
            gender: $gender,
            birthDate: $birthDate ?? new \DateTimeImmutable('2000-01-01'),
        );
    }

    /**
     * @param list<string> $roles
     */
    public static function ormUser(
        ?int $id = 1,
        string $email = 'user@example.com',
        ?string $confirmedEmail = 'user@example.com',
        string $password = self::VALID_PASSWORD_HASH,
        string $username = 'username',
        array $roles = ['ROLE_USER'],
        ?string $passwordToken = null,
        ?\DateTimeImmutable $passwordTokenExpiresAt = null,
        ?string $emailToken = null,
        ?\DateTimeImmutable $emailTokenExpiresAt = null,
        ?\DateTimeImmutable $registeredAt = null,
        GenderEnum $gender = GenderEnum::MALE,
        ?\DateTimeImmutable $birthDate = null,
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
            ->setRegisteredAt($registeredAt ?? new \DateTimeImmutable('-1 day'))
            ->setGender($gender)
            ->setBirthDate($birthDate ?? new \DateTimeImmutable('2000-01-01'));

        self::forceId($user, $id);

        return $user;
    }

    public static function forceId(User $user, ?int $id): void
    {
        $property = new \ReflectionProperty($user, 'id');
        $property->setValue($user, $id);
    }
}
