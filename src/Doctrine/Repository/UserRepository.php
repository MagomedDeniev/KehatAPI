<?php

declare(strict_types=1);

namespace App\Doctrine\Repository;

use App\Doctrine\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
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

    public function findUserById(int $id): ?User
    {
        $user = $this->findOneBy(['id' => $id]);

        if (!$user instanceof User) {
            return null;
        }

        return $user;
    }

    public function findUserByEmail(string $email): ?User
    {
        $user = $this->findOneBy(['email' => $email]);

        if (!$user instanceof User) {
            return null;
        }

        return $user;
    }

    public function findUserByUsername(string $username): ?User
    {
        $user = $this->findOneBy(['username' => $username]);

        if (!$user instanceof User) {
            return null;
        }

        return $user;
    }

    public function findUserByEmailToken(string $emailToken): ?User
    {
        $user = $this->findOneBy(['emailToken' => $emailToken]);

        if (!$user instanceof User) {
            return null;
        }

        return $user;
    }

    public function findUserByPasswordToken(string $passwordToken): ?User
    {
        $user = $this->findOneBy(['passwordToken' => $passwordToken]);

        if (!$user instanceof User) {
            return null;
        }

        return $user;
    }

    public function createUser(User $user): User
    {
        if (null !== $user->getId()) {
            throw new \LogicException('Cannot create user that already has an id.');
        }

        $em = $this->getEntityManager();
        $em->persist($user);
        $em->flush();

        return $user;
    }

    public function updateUser(User $user): User
    {
        if (null === $user->getId()) {
            throw new \LogicException('Cannot update user without an id.');
        }

        $this->getEntityManager()->flush();

        return $user;
    }
}
