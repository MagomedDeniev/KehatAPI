<?php

namespace App\Entity;

use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use phpDocumentor\Reflection\PseudoTypes\EnumString;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
#[UniqueEntity(fields: ['username'], message: 'There is already an account with this username')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\NotBlank]
    #[Assert\Email]
    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $confirmedEmail = null;

    #[ORM\Column]
    private ?string $password = null;

    #[Assert\NotBlank]
    #[Assert\Length(min: 6, max: 180)]
    #[ORM\Column(length: 180, unique: true)]
    private ?string $username = null;

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
    private ?\DateTimeImmutable $registeredAt = null;

    public function tokenIsValid(string $token, string $type): bool
    {
        // Определяем, с каким токеном и сроком действия работаем
        [$storedToken, $storedTokenExpiresAt] = match ($type) {
            'email' => [$this->emailToken, $this->emailTokenExpiresAt],
            'password' => [$this->passwordToken, $this->passwordTokenExpiresAt],
            default => [null, null], // если $type неизвестен — токен сразу невалиден
        };

        // Если переданный $type пустой, либо у сущности нет токена и/или времени истечения токена
        if ($storedToken === null || $storedTokenExpiresAt === null) {
            return false;
        }

        // Переданный $token пустой или не совпадает с сохраненным
        if ($token === '' || $storedToken !== $token) {
            return false;
        }

        // Если срок не истек — токен валиден
        return $storedTokenExpiresAt > new \DateTimeImmutable();
    }

    #[ORM\PrePersist]
    public function initialize(): void
    {
        $this->registeredAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
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

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getUsername(): ?string
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
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

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

    public function eraseCredentials(): void {}

    public function getRegisteredAt(): ?\DateTimeImmutable
    {
        return $this->registeredAt;
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
}
