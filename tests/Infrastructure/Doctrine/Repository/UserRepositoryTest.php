<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\DomainUser;
use App\Infrastructure\Doctrine\Entity\User;
use App\Infrastructure\Doctrine\Repository\UserRepository;
use App\Tests\Support\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

final class UserRepositoryTest extends TestCase
{
    public function testUpgradePasswordRejectsUnsupportedUser(): void
    {
        $repository = new TestUserRepository($this->createMock(EntityManagerInterface::class));

        $this->expectException(UnsupportedUserException::class);
        $this->expectExceptionMessage(sprintf('Instances of "%s" are not supported.', UnsupportedPasswordUser::class));

        $repository->upgradePassword(new UnsupportedPasswordUser(), 'hashed-password');
    }

    public function testUpgradePasswordPersistsAndFlushesUser(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $repository = new TestUserRepository($entityManager);
        $user = UserFactory::ormUser(password: 'old-password');

        $entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->callback(static function (User $persistedUser): bool {
                self::assertSame('new-password', $persistedUser->getPassword());

                return true;
            }));

        $entityManager->expects($this->once())->method('flush');

        $repository->upgradePassword($user, 'new-password');

        self::assertSame('new-password', $user->getPassword());
    }

    public function testFindUserByReturnsNullWhenOrmUserIsMissing(): void
    {
        $repository = new TestUserRepository($this->createMock(EntityManagerInterface::class));
        $repository->findOneByResult = null;

        self::assertNull($repository->findUserByEmail('missing@example.com'));
    }

    public function testFindUserByMapsOrmUserToDomainUser(): void
    {
        $repository = new TestUserRepository($this->createMock(EntityManagerInterface::class));
        $repository->findOneByResult = UserFactory::ormUser(
            id: 9,
            email: 'user@example.com',
            confirmedEmail: 'user@example.com',
            password: 'hashed-password',
            username: 'username',
            roles: ['ROLE_ADMIN'],
            passwordToken: 'password-token',
            passwordTokenExpiresAt: new \DateTimeImmutable('+1 hour'),
            emailToken: 'email-token',
            emailTokenExpiresAt: new \DateTimeImmutable('+2 hours'),
        );

        $user = $repository->findUserByEmail('user@example.com');

        self::assertInstanceOf(DomainUser::class, $user);
        self::assertSame(9, $user->getId());
        self::assertSame('user@example.com', $user->getEmail());
        self::assertSame('user@example.com', $user->getConfirmedEmail());
        self::assertSame('hashed-password', $user->getPassword());
        self::assertSame('username', $user->getUsername());
        self::assertSame(['ROLE_ADMIN', 'ROLE_USER'], $user->getRoles());
        self::assertSame('password-token', $user->getPasswordToken());
        self::assertSame('email-token', $user->getEmailToken());
    }

    public function testCreateDomainUserRejectsDomainUserWithId(): void
    {
        $repository = new TestUserRepository($this->createMock(EntityManagerInterface::class));

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot create user that already has an id.');

        $repository->createDomainUser(UserFactory::domainUser(id: 5));
    }

    public function testCreateDomainUserPersistsMappedOrmUserAndReturnsMappedDomainUser(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $repository = new TestUserRepository($entityManager);

        $domainUser = UserFactory::domainUser(
            id: null,
            email: 'new@example.com',
            confirmedEmail: null,
            password: 'hashed-password',
            username: 'new_user',
            roles: ['ROLE_ADMIN'],
            passwordToken: 'password-token',
            passwordTokenExpiresAt: new \DateTimeImmutable('+1 hour'),
            emailToken: 'email-token',
            emailTokenExpiresAt: new \DateTimeImmutable('+2 hours'),
        );

        $entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->callback(static function (User $user): bool {
                self::assertSame('new@example.com', $user->getEmail());
                self::assertNull($user->getConfirmedEmail());
                self::assertSame('hashed-password', $user->getPassword());
                self::assertSame('new_user', $user->getUsername());
                self::assertSame(['ROLE_ADMIN', 'ROLE_USER'], $user->getRoles());
                self::assertSame('password-token', $user->getPasswordToken());
                self::assertSame('email-token', $user->getEmailToken());

                UserFactory::forceId($user, 77);

                return true;
            }));

        $entityManager->expects($this->once())->method('flush');

        $createdUser = $repository->createDomainUser($domainUser);

        self::assertSame(77, $createdUser->getId());
        self::assertSame('new@example.com', $createdUser->getEmail());
        self::assertSame(['ROLE_ADMIN', 'ROLE_USER'], $createdUser->getRoles());
        self::assertSame('email-token', $createdUser->getEmailToken());
    }

    public function testUpdateDomainUserRejectsDomainUserWithoutId(): void
    {
        $repository = new TestUserRepository($this->createMock(EntityManagerInterface::class));

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot update user without an id.');

        $repository->updateDomainUser(UserFactory::domainUser(id: null));
    }

    public function testUpdateDomainUserRejectsMissingOrmUser(): void
    {
        $repository = new TestUserRepository($this->createMock(EntityManagerInterface::class));
        $repository->findResult = null;

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('User with id 5 was not found.');

        $repository->updateDomainUser(UserFactory::domainUser(id: 5));
    }

    public function testUpdateDomainUserMapsChangesToExistingOrmUserAndReturnsMappedDomainUser(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $repository = new TestUserRepository($entityManager);
        $existingUser = UserFactory::ormUser(id: 5, email: 'old@example.com', username: 'old_user');
        $repository->findResult = $existingUser;

        $entityManager->expects($this->once())->method('flush');

        $updatedUser = $repository->updateDomainUser(UserFactory::domainUser(
            id: 5,
            email: 'new@example.com',
            confirmedEmail: 'confirmed@example.com',
            password: 'new-password',
            username: 'new_user',
            roles: ['ROLE_ADMIN'],
            passwordToken: 'password-token',
            passwordTokenExpiresAt: new \DateTimeImmutable('+1 hour'),
            emailToken: 'email-token',
            emailTokenExpiresAt: new \DateTimeImmutable('+2 hours'),
        ));

        self::assertSame('new@example.com', $existingUser->getEmail());
        self::assertSame('confirmed@example.com', $existingUser->getConfirmedEmail());
        self::assertSame('new-password', $existingUser->getPassword());
        self::assertSame('new_user', $existingUser->getUsername());
        self::assertSame('password-token', $existingUser->getPasswordToken());
        self::assertSame('email-token', $existingUser->getEmailToken());

        self::assertSame(5, $updatedUser->getId());
        self::assertSame('new@example.com', $updatedUser->getEmail());
        self::assertSame(['ROLE_ADMIN', 'ROLE_USER'], $updatedUser->getRoles());
    }
}

final class UnsupportedPasswordUser implements PasswordAuthenticatedUserInterface
{
    public function getPassword(): string
    {
        return 'password';
    }
}

final class TestUserRepository extends UserRepository
{
    public ?User $findOneByResult = null;
    public ?User $findResult = null;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /** @phpstan-ignore impureMethod.pure */
    public function findOneBy(array $criteria, ?array $orderBy = null): ?User
    {
        return $this->findOneByResult;
    }

    /** @phpstan-ignore impureMethod.pure */
    public function find(mixed $id, \Doctrine\DBAL\LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?User
    {
        return $this->findResult;
    }

    protected function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }
}
