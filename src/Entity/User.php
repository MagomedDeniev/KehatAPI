<?php

namespace App\Entity;

use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
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

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $token = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $tokenRequestedAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $registeredAt = null;

    #[ORM\PrePersist]
    public function initialize(): void
    {
        $this->registeredAt = new \DateTimeImmutable();
        $this->tokenRequestedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function changePassword(string $hashedPassword): void
    {
        $this->password = $hashedPassword;
    }

    public function refreshConfirmationToken(string $token): void
    {
        $this->token = $token;
        $this->tokenRequestedAt = new \DateTimeImmutable();
    }

    public function hasValidEmailConfirmationToken(int $timeOutSeconds): bool
    {
        if (!$this->token || !$this->tokenRequestedAt) {
            return false;
        }

        $now = new \DateTimeImmutable();

        return ($now->getTimestamp() - $this->tokenRequestedAt->getTimestamp()) <= $timeOutSeconds;
    }

    public function confirmEmail(): void
    {
        $this->confirmedEmail = $this->email;
        $this->token = null;
        $this->tokenRequestedAt = null;
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

    /**
     * @see PasswordAuthenticatedUserInterface
     */
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

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * Ensure the session doesn't contain actual password hashes by CRC32C-hashing them, as supported since Symfony 7.3.
     */
    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0".self::class."\0password"] = hash('crc32c', $this->password);

        return $data;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // @deprecated, to be removed when upgrading to Symfony 8
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(?string $token): static
    {
        $this->token = $token;

        return $this;
    }

    public function getTokenRequestedAt(): ?\DateTimeImmutable
    {
        return $this->tokenRequestedAt;
    }

    public function setTokenRequestedAt(?\DateTimeImmutable $tokenRequestedAt): static
    {
        $this->tokenRequestedAt = $tokenRequestedAt;

        return $this;
    }

    public function getRegisteredAt(): ?\DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function setRegisteredAt(\DateTimeImmutable $registeredAt): static
    {
        $this->registeredAt = $registeredAt;

        return $this;
    }
}
