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
        return $this->findOneBy(['id' => $id]);
    }

    public function findUserByEmail(string $email): ?User
    {
        return $this->findOneBy(['email' => $email]);
    }

    public function findUserByUsername(string $username): ?User
    {
        return $this->findOneBy(['username' => $username]);
    }

    public function findUserByEmailToken(string $emailToken): ?User
    {
        return $this->findOneBy(['emailToken' => $emailToken]);
    }

    public function findUserByPasswordToken(string $passwordToken): ?User
    {
        return $this->findOneBy(['passwordToken' => $passwordToken]);
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
