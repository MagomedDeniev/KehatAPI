<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\DomainUser;
use App\Domain\Repository\DomainUserRepositoryInterface;
use App\Infrastructure\Doctrine\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface, DomainUserRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * @param array<string, mixed> $criteria
     */
    public function findUserBy(array $criteria): ?DomainUser
    {
        $user = $this->findOneBy($criteria);

        if (!$user instanceof User) {
            return null;
        }

        return $this->mapToDomain($user);
    }

    public function saveDomainUser(DomainUser $domainUser): DomainUser
    {
        $user = null;

        if (null !== $domainUser->getId()) {
            $user = $this->find($domainUser->getId());
        }

        if (!$user instanceof User) {
            $user = new User();
        }

        $this->updateOrmFromDomain($user, $domainUser);

        $em = $this->getEntityManager();
        $em->persist($user);
        $em->flush();

        return $this->mapToDomain($user);
    }

    private function mapToDomain(User $user): DomainUser
    {
        return new DomainUser(
            id: $user->getId(),
            email: $user->getEmail(),
            confirmedEmail: $user->getConfirmedEmail(),
            password: $user->getPassword(),
            username: $user->getUsername(),
            roles: $user->getRoles(),
            passwordToken: $user->getPasswordToken(),
            passwordTokenExpiresAt: $user->getPasswordTokenExpiresAt(),
            emailToken: $user->getEmailToken(),
            emailTokenExpiresAt: $user->getEmailTokenExpiresAt(),
            registeredAt: $user->getRegisteredAt(),
        );
    }

    private function updateOrmFromDomain(User $user, DomainUser $domainUser): void
    {
        $user->setEmail($domainUser->getEmail());
        $user->setConfirmedEmail($domainUser->getConfirmedEmail());
        $user->setPassword($domainUser->getPassword());
        $user->setUsername($domainUser->getUsername());
        $user->setRoles($domainUser->getRoles());
        $user->setPasswordToken($domainUser->getPasswordToken());
        $user->setPasswordTokenExpiresAt($domainUser->getPasswordTokenExpiresAt());
        $user->setEmailToken($domainUser->getEmailToken());
        $user->setEmailTokenExpiresAt($domainUser->getEmailTokenExpiresAt());
        $user->setRegisteredAt($domainUser->getRegisteredAt());
    }
}
