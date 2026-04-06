<?php

declare(strict_types=1);

namespace App\Domain\Entity;

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
        string $email,
        string $password,
        string $username,
        string $emailToken,
        \DateTimeImmutable $emailTokenExpiresAt,
    ): self {
        return new self(
            id: null,
            email: $email,
            confirmedEmail: null,
            password: $password,
            username: $username,
            roles: ['ROLE_USER'],
            passwordToken: null,
            passwordTokenExpiresAt: null,
            emailToken: $emailToken,
            emailTokenExpiresAt: $emailTokenExpiresAt,
            registeredAt: new \DateTimeImmutable(),
        );
    }

    public function confirmEmail(): void
    {
        $this->confirmedEmail = $this->email;
        $this->emailToken = null;
        $this->emailTokenExpiresAt = null;
    }

    public function emailTokenIsValid(string $token): bool
    {
        if (null === $this->emailToken || !$this->emailTokenExpiresAt instanceof \DateTimeImmutable) {
            return false;
        }

        if ('' === $token || $this->emailToken !== $token) {
            return false;
        }

        return $this->emailTokenExpiresAt > new \DateTimeImmutable();
    }

    public function assignPasswordToken(?string $token, ?\DateTimeImmutable $tokenExpiresAt): void
    {
        $this->passwordToken = $token;
        $this->passwordTokenExpiresAt = $tokenExpiresAt;
    }

    public function restorePassword(string $password): void
    {
        $this->password = $password;
        $this->passwordToken = null;
        $this->passwordTokenExpiresAt = null;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function saveSettings(string $username, string $email): void
    {
        $this->username = $username;
        $this->email = $email;
    }

    public function saveSettingsWithEmailUpdate(string $username, string $email, string $token, \DateTimeImmutable $tokenExpiresAt): void
    {
        $this->saveSettings($username, $email);
        $this->emailToken = $token;
        $this->emailTokenExpiresAt = $tokenExpiresAt;
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
