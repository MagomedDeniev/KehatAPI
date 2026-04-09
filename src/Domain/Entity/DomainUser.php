<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\EmailToken;
use App\Domain\ValueObject\HashedPassword;
use App\Domain\ValueObject\PasswordToken;
use App\Domain\ValueObject\TokenExpirationTime;
use App\Domain\ValueObject\Username;

final class DomainUser
{
    /**
     * @param list<string> $roles
     */
    public function __construct(
        private ?int $id,
        private string $email,
        private ?string $confirmedEmail,
        private string $password,
        private string $username,
        private array $roles,
        private ?string $passwordToken,
        private ?\DateTimeImmutable $passwordTokenExpiresAt,
        private ?string $emailToken,
        private ?\DateTimeImmutable $emailTokenExpiresAt,
        private \DateTimeImmutable $registeredAt,
    ) {
    }

    public static function register(
        Email $email,
        HashedPassword $password,
        Username $username,
        EmailToken $emailToken,
        TokenExpirationTime $emailTokenExpiresAt,
    ): self {
        return new self(
            id: null,
            email: (string) $email,
            confirmedEmail: null,
            password: (string) $password,
            username: (string) $username,
            roles: ['ROLE_USER'],
            passwordToken: null,
            passwordTokenExpiresAt: null,
            emailToken: (string) $emailToken,
            emailTokenExpiresAt: $emailTokenExpiresAt->value(),
            registeredAt: new \DateTimeImmutable(),
        );
    }

    public function confirmEmail(): void
    {
        $this->confirmedEmail = $this->email;
        $this->emailToken = null;
        $this->emailTokenExpiresAt = null;
    }

    public function hasValidEmailToken(): bool
    {
        if (null === $this->emailToken || !$this->emailTokenExpiresAt instanceof \DateTimeImmutable) {
            return false;
        }

        return $this->emailTokenExpiresAt > new \DateTimeImmutable();
    }

    public function hasValidPasswordToken(): bool
    {
        if (null === $this->passwordToken || !$this->passwordTokenExpiresAt instanceof \DateTimeImmutable) {
            return false;
        }

        return $this->passwordTokenExpiresAt > new \DateTimeImmutable();
    }

    public function assignPasswordToken(PasswordToken $token, TokenExpirationTime $tokenExpiresAt): void
    {
        $this->passwordToken = (string) $token;
        $this->passwordTokenExpiresAt = $tokenExpiresAt->value();
    }

    public function changePassword(HashedPassword $password): void
    {
        $this->password = (string) $password;
        $this->passwordToken = null;
        $this->passwordTokenExpiresAt = null;
    }

    public function saveSettings(Username $username, Email $email): void
    {
        $this->username = (string) $username;
        $this->email = (string) $email;
    }

    public function saveSettingsWithEmailUpdate(Username $username, Email $email, EmailToken $token, TokenExpirationTime $tokenExpiresAt): void
    {
        $this->saveSettings($username, $email);
        $this->emailToken = (string) $token;
        $this->emailTokenExpiresAt = $tokenExpiresAt->value();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getConfirmedEmail(): ?string
    {
        return $this->confirmedEmail;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_values(array_unique($roles));
    }

    public function getPasswordToken(): ?string
    {
        return $this->passwordToken;
    }

    public function getPasswordTokenExpiresAt(): ?\DateTimeImmutable
    {
        return $this->passwordTokenExpiresAt;
    }

    public function getEmailToken(): ?string
    {
        return $this->emailToken;
    }

    public function getEmailTokenExpiresAt(): ?\DateTimeImmutable
    {
        return $this->emailTokenExpiresAt;
    }

    public function getRegisteredAt(): \DateTimeImmutable
    {
        return $this->registeredAt;
    }
}
