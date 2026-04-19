<?php

declare(strict_types=1);

namespace App\Doctrine\Entity;

use App\Doctrine\Repository\UserRepository;
use App\Domain\Enum\GenderEnum;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\EmailToken;
use App\Domain\ValueObject\HashedPassword;
use App\Domain\ValueObject\PasswordToken;
use App\Domain\ValueObject\TokenExpirationTime;
use App\Domain\ValueObject\Username;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private string $email;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $confirmedEmail = null;

    #[ORM\Column]
    private string $password;

    #[ORM\Column(length: 180, unique: true)]
    private string $username;

    /** @var list<string> */
    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $passwordToken = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $passwordTokenExpiresAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $emailToken = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $emailTokenExpiresAt = null;

    #[ORM\Column]
    private \DateTimeImmutable $registeredAt;

    #[ORM\Column(enumType: GenderEnum::class)]
    private GenderEnum $gender;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $birthDate;

    public static function register(
        Email $email,
        HashedPassword $password,
        Username $username,
        EmailToken $emailToken,
        TokenExpirationTime $emailTokenExpiresAt,
        GenderEnum $gender,
        \DateTimeImmutable $birthDate,
    ): self {
        $user = new self();
        $user->email = (string) $email;
        $user->confirmedEmail = null;
        $user->password = (string) $password;
        $user->username = (string) $username;
        $user->roles = ['ROLE_USER'];
        $user->passwordToken = null;
        $user->passwordTokenExpiresAt = null;
        $user->emailToken = (string) $emailToken;
        $user->emailTokenExpiresAt = $emailTokenExpiresAt->value();
        $user->registeredAt = new \DateTimeImmutable();
        $user->gender = $gender;
        $user->birthDate = $birthDate;

        return $user;
    }

    public function confirmEmail(): void
    {
        $this->confirmedEmail = $this->email;
        $this->emailToken = null;
        $this->emailTokenExpiresAt = null;
    }

    public function hasValidEmailToken(?string $token = null): bool
    {
        if (null === $this->emailToken || !$this->emailTokenExpiresAt instanceof \DateTimeImmutable) {
            return false;
        }

        if (null !== $token && $token !== $this->emailToken) {
            return false;
        }

        return $this->emailTokenExpiresAt > new \DateTimeImmutable();
    }

    public function hasValidPasswordToken(?string $token = null): bool
    {
        if (null === $this->passwordToken || !$this->passwordTokenExpiresAt instanceof \DateTimeImmutable) {
            return false;
        }

        if (null !== $token && $token !== $this->passwordToken) {
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

    public function saveSettings(Username $username, Email $email, GenderEnum $gender, \DateTimeImmutable $birthDate): void
    {
        $this->username = (string) $username;
        $this->email = (string) $email;
        $this->gender = $gender;
        $this->birthDate = $birthDate;
    }

    public function saveSettingsWithEmailUpdate(Username $username, Email $email, GenderEnum $gender, \DateTimeImmutable $birthDate, EmailToken $token, TokenExpirationTime $tokenExpiresAt): void
    {
        $this->saveSettings($username, $email, $gender, $birthDate);
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

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getConfirmedEmail(): ?string
    {
        return $this->confirmedEmail;
    }

    public function setConfirmedEmail(?string $confirmedEmail): static
    {
        $this->confirmedEmail = $confirmedEmail;

        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function getUserIdentifier(): string
    {
        if ('' === $this->email) {
            throw new \LogicException('User email cannot be empty.');
        }

        return $this->email;
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

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0".self::class."\0password"] = hash('crc32c', $this->password);

        return $data;
    }

    public function eraseCredentials(): void
    {
    }

    public function getRegisteredAt(): \DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function setRegisteredAt(\DateTimeImmutable $registeredAt): static
    {
        $this->registeredAt = $registeredAt;

        return $this;
    }

    public function getPasswordToken(): ?string
    {
        return $this->passwordToken;
    }

    public function setPasswordToken(?string $passwordToken): static
    {
        $this->passwordToken = $passwordToken;

        return $this;
    }

    public function getPasswordTokenExpiresAt(): ?\DateTimeImmutable
    {
        return $this->passwordTokenExpiresAt;
    }

    public function setPasswordTokenExpiresAt(?\DateTimeImmutable $passwordTokenExpiresAt): static
    {
        $this->passwordTokenExpiresAt = $passwordTokenExpiresAt;

        return $this;
    }

    public function getEmailToken(): ?string
    {
        return $this->emailToken;
    }

    public function setEmailToken(?string $emailToken): static
    {
        $this->emailToken = $emailToken;

        return $this;
    }

    public function getEmailTokenExpiresAt(): ?\DateTimeImmutable
    {
        return $this->emailTokenExpiresAt;
    }

    public function setEmailTokenExpiresAt(?\DateTimeImmutable $emailTokenExpiresAt): static
    {
        $this->emailTokenExpiresAt = $emailTokenExpiresAt;

        return $this;
    }

    public function getGender(): GenderEnum
    {
        return $this->gender;
    }

    public function setGender(GenderEnum $gender): static
    {
        $this->gender = $gender;

        return $this;
    }

    public function getBirthDate(): \DateTimeImmutable
    {
        return $this->birthDate;
    }

    public function setBirthDate(\DateTimeImmutable $birthDate): static
    {
        $this->birthDate = $birthDate;

        return $this;
    }
}
