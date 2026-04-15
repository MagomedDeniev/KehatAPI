<?php

declare(strict_types=1);

namespace App\Tests\Domain\Entity;

use App\Domain\Entity\DomainUser;
use App\Domain\Enum\GenderEnum;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\EmailToken;
use App\Domain\ValueObject\HashedPassword;
use App\Domain\ValueObject\PasswordToken;
use App\Domain\ValueObject\TokenExpirationTime;
use App\Domain\ValueObject\Username;
use App\Tests\Support\UserFactory;
use PHPUnit\Framework\TestCase;

final class DomainUserTest extends TestCase
{
    public function testRegisterCreatesDefaultState(): void
    {
        $emailTokenExpiresAt = new TokenExpirationTime();
        $birthDate = new \DateTimeImmutable('1990-05-20');

        $user = DomainUser::register(
            email: new Email('user@example.com'),
            password: new HashedPassword(UserFactory::VALID_PASSWORD_HASH),
            username: new Username('username'),
            emailToken: new EmailToken(UserFactory::VALID_EMAIL_TOKEN),
            emailTokenExpiresAt: $emailTokenExpiresAt,
            gender: GenderEnum::FEMALE,
            birthDate: $birthDate,
        );

        self::assertNull($user->getId());
        self::assertSame('user@example.com', $user->getEmail());
        self::assertNull($user->getConfirmedEmail());
        self::assertSame(UserFactory::VALID_PASSWORD_HASH, $user->getPassword());
        self::assertSame('username', $user->getUsername());
        self::assertSame(['ROLE_USER'], $user->getRoles());
        self::assertNull($user->getPasswordToken());
        self::assertNull($user->getPasswordTokenExpiresAt());
        self::assertSame(UserFactory::VALID_EMAIL_TOKEN, $user->getEmailToken());
        self::assertSame($emailTokenExpiresAt->value(), $user->getEmailTokenExpiresAt());
        self::assertSame(GenderEnum::FEMALE, $user->getGender());
        self::assertSame($birthDate, $user->getBirthDate());
        self::assertLessThanOrEqual(2, abs($user->getRegisteredAt()->getTimestamp() - time()));
    }

    public function testConfirmEmailConfirmsAndClearsToken(): void
    {
        $user = UserFactory::domainUser(
            email: 'user@example.com',
            confirmedEmail: null,
            emailToken: UserFactory::VALID_EMAIL_TOKEN,
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
        self::assertFalse(UserFactory::domainUser(emailToken: UserFactory::VALID_EMAIL_TOKEN, emailTokenExpiresAt: null)->hasValidEmailToken());
        self::assertFalse(UserFactory::domainUser(emailToken: UserFactory::VALID_EMAIL_TOKEN, emailTokenExpiresAt: new \DateTimeImmutable('-1 hour'))->hasValidEmailToken());
        self::assertTrue(UserFactory::domainUser(emailToken: UserFactory::VALID_EMAIL_TOKEN, emailTokenExpiresAt: new \DateTimeImmutable('+1 hour'))->hasValidEmailToken());
    }

    public function testPasswordTokenValidationChecksPresenceAndExpiry(): void
    {
        self::assertFalse(UserFactory::domainUser(passwordToken: null, passwordTokenExpiresAt: new \DateTimeImmutable('+1 hour'))->hasValidPasswordToken());
        self::assertFalse(UserFactory::domainUser(passwordToken: UserFactory::VALID_PASSWORD_TOKEN, passwordTokenExpiresAt: null)->hasValidPasswordToken());
        self::assertFalse(UserFactory::domainUser(passwordToken: UserFactory::VALID_PASSWORD_TOKEN, passwordTokenExpiresAt: new \DateTimeImmutable('-1 hour'))->hasValidPasswordToken());
        self::assertTrue(UserFactory::domainUser(passwordToken: UserFactory::VALID_PASSWORD_TOKEN, passwordTokenExpiresAt: new \DateTimeImmutable('+1 hour'))->hasValidPasswordToken());
    }

    public function testAssignPasswordTokenStoresTokenAndExpiration(): void
    {
        $user = UserFactory::domainUser(passwordToken: null, passwordTokenExpiresAt: null);
        $expiresAt = new TokenExpirationTime();

        $user->assignPasswordToken(new PasswordToken(UserFactory::VALID_PASSWORD_TOKEN), $expiresAt);

        self::assertSame(UserFactory::VALID_PASSWORD_TOKEN, $user->getPasswordToken());
        self::assertSame($expiresAt->value(), $user->getPasswordTokenExpiresAt());
    }

    public function testChangePasswordUpdatesPasswordAndClearsResetToken(): void
    {
        $user = UserFactory::domainUser(
            password: UserFactory::VALID_PASSWORD_HASH,
            passwordToken: UserFactory::VALID_PASSWORD_TOKEN,
            passwordTokenExpiresAt: new \DateTimeImmutable('+30 minutes'),
        );

        $newHashedPassword = password_hash('new-password', PASSWORD_BCRYPT);
        $user->changePassword(new HashedPassword($newHashedPassword));

        self::assertSame($newHashedPassword, $user->getPassword());
        self::assertNull($user->getPasswordToken());
        self::assertNull($user->getPasswordTokenExpiresAt());
    }

    public function testSaveSettingsUpdatesProfileFields(): void
    {
        $user = UserFactory::domainUser(email: 'old@example.com', username: 'old_name');
        $birthDate = new \DateTimeImmutable('1993-07-15');

        $user->saveSettings(new Username('new_name'), new Email('new@example.com'), GenderEnum::FEMALE, $birthDate);

        self::assertSame('new_name', $user->getUsername());
        self::assertSame('new@example.com', $user->getEmail());
        self::assertSame(GenderEnum::FEMALE, $user->getGender());
        self::assertSame($birthDate, $user->getBirthDate());
    }

    public function testSaveSettingsWithEmailUpdateAlsoStoresNewEmailToken(): void
    {
        $user = UserFactory::domainUser(email: 'old@example.com', username: 'old_name');
        $expiresAt = new TokenExpirationTime();
        $birthDate = new \DateTimeImmutable('1994-08-11');

        $user->saveSettingsWithEmailUpdate(
            new Username('new_name'),
            new Email('new@example.com'),
            GenderEnum::FEMALE,
            $birthDate,
            new EmailToken(UserFactory::VALID_EMAIL_TOKEN_ALT),
            $expiresAt,
        );

        self::assertSame('new_name', $user->getUsername());
        self::assertSame('new@example.com', $user->getEmail());
        self::assertSame(GenderEnum::FEMALE, $user->getGender());
        self::assertSame($birthDate, $user->getBirthDate());
        self::assertSame(UserFactory::VALID_EMAIL_TOKEN_ALT, $user->getEmailToken());
        self::assertSame($expiresAt->value(), $user->getEmailTokenExpiresAt());
    }

    public function testGetRolesAlwaysContainsSingleRoleUser(): void
    {
        $user = UserFactory::domainUser(roles: ['ROLE_ADMIN', 'ROLE_USER', 'ROLE_ADMIN']);

        self::assertSame(['ROLE_ADMIN', 'ROLE_USER'], $user->getRoles());
    }
}
