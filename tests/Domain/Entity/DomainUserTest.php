<?php

declare(strict_types=1);

namespace App\Tests\Domain\Entity;

use App\Domain\Entity\DomainUser;
use App\Tests\Support\UserFactory;
use PHPUnit\Framework\TestCase;

final class DomainUserTest extends TestCase
{
    public function testRegisterCreatesDefaultState(): void
    {
        $expiresAt = new \DateTimeImmutable('+1 hour');

        $user = DomainUser::register(
            email: 'user@example.com',
            password: 'hashed-password',
            username: 'username',
            emailToken: 'email-token',
            emailTokenExpiresAt: $expiresAt,
        );

        self::assertNull($user->getId());
        self::assertSame('user@example.com', $user->getEmail());
        self::assertNull($user->getConfirmedEmail());
        self::assertSame('hashed-password', $user->getPassword());
        self::assertSame('username', $user->getUsername());
        self::assertSame(['ROLE_USER'], $user->getRoles());
        self::assertNull($user->getPasswordToken());
        self::assertNull($user->getPasswordTokenExpiresAt());
        self::assertSame('email-token', $user->getEmailToken());
        self::assertSame($expiresAt, $user->getEmailTokenExpiresAt());
        self::assertLessThanOrEqual(2, abs($user->getRegisteredAt()->getTimestamp() - time()));
    }

    public function testConfirmEmailConfirmsAndClearsToken(): void
    {
        $user = UserFactory::domainUser(
            email: 'user@example.com',
            confirmedEmail: null,
            emailToken: 'email-token',
            emailTokenExpiresAt: new \DateTimeImmutable('+1 hour'),
        );

        $user->confirmEmail();

        self::assertSame('user@example.com', $user->getConfirmedEmail());
        self::assertNull($user->getEmailToken());
        self::assertNull($user->getEmailTokenExpiresAt());
    }

    public function testEmailTokenValidationChecksPresenceAndExpiry(): void
    {
        self::assertFalse(UserFactory::domainUser(emailToken: null, emailTokenExpiresAt: new \DateTimeImmutable('+1 hour'))->hasValidEmailToken());
        self::assertFalse(UserFactory::domainUser(emailToken: 'token', emailTokenExpiresAt: null)->hasValidEmailToken());
        self::assertFalse(UserFactory::domainUser(emailToken: 'token', emailTokenExpiresAt: new \DateTimeImmutable('-1 hour'))->hasValidEmailToken());
        self::assertTrue(UserFactory::domainUser(emailToken: 'token', emailTokenExpiresAt: new \DateTimeImmutable('+1 hour'))->hasValidEmailToken());
    }

    public function testPasswordTokenValidationChecksPresenceAndExpiry(): void
    {
        self::assertFalse(UserFactory::domainUser(passwordToken: null, passwordTokenExpiresAt: new \DateTimeImmutable('+1 hour'))->hasValidPasswordToken());
        self::assertFalse(UserFactory::domainUser(passwordToken: 'token', passwordTokenExpiresAt: null)->hasValidPasswordToken());
        self::assertFalse(UserFactory::domainUser(passwordToken: 'token', passwordTokenExpiresAt: new \DateTimeImmutable('-1 hour'))->hasValidPasswordToken());
        self::assertTrue(UserFactory::domainUser(passwordToken: 'token', passwordTokenExpiresAt: new \DateTimeImmutable('+1 hour'))->hasValidPasswordToken());
    }

    public function testAssignPasswordTokenStoresTokenAndExpiration(): void
    {
        $user = UserFactory::domainUser(passwordToken: null, passwordTokenExpiresAt: null);
        $expiresAt = new \DateTimeImmutable('+30 minutes');

        $user->assignPasswordToken('password-token', $expiresAt);

        self::assertSame('password-token', $user->getPasswordToken());
        self::assertSame($expiresAt, $user->getPasswordTokenExpiresAt());
    }

    public function testChangePasswordUpdatesPasswordAndClearsResetToken(): void
    {
        $user = UserFactory::domainUser(
            password: 'old-password',
            passwordToken: 'password-token',
            passwordTokenExpiresAt: new \DateTimeImmutable('+30 minutes'),
        );

        $user->changePassword('new-password');

        self::assertSame('new-password', $user->getPassword());
        self::assertNull($user->getPasswordToken());
        self::assertNull($user->getPasswordTokenExpiresAt());
    }

    public function testSaveSettingsUpdatesUsernameAndEmail(): void
    {
        $user = UserFactory::domainUser(email: 'old@example.com', username: 'old_name');

        $user->saveSettings('new_name', 'new@example.com');

        self::assertSame('new_name', $user->getUsername());
        self::assertSame('new@example.com', $user->getEmail());
    }

    public function testSaveSettingsWithEmailUpdateAlsoStoresNewEmailToken(): void
    {
        $user = UserFactory::domainUser(email: 'old@example.com', username: 'old_name');
        $expiresAt = new \DateTimeImmutable('+1 hour');

        $user->saveSettingsWithEmailUpdate('new_name', 'new@example.com', 'new-token', $expiresAt);

        self::assertSame('new_name', $user->getUsername());
        self::assertSame('new@example.com', $user->getEmail());
        self::assertSame('new-token', $user->getEmailToken());
        self::assertSame($expiresAt, $user->getEmailTokenExpiresAt());
    }

    public function testGetRolesAlwaysContainsSingleRoleUser(): void
    {
        $user = UserFactory::domainUser(roles: ['ROLE_ADMIN', 'ROLE_USER', 'ROLE_ADMIN']);

        self::assertSame(['ROLE_ADMIN', 'ROLE_USER'], $user->getRoles());
    }
}
