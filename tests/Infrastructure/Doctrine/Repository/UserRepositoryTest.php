<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\DomainUser;
use App\Infrastructure\Doctrine\Entity\User;
use App\Infrastructure\Doctrine\Repository\UserRepository;
use App\Tests\Support\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
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

    #[DataProvider('findUserProvider')]
    public function testFindUserReturnsNullWhenOrmUserIsMissing(string $method, string|int $value): void
    {
        $repository = new TestUserRepository($this->createMock(EntityManagerInterface::class));
        $repository->findOneByResult = null;

        self::assertNull($repository->{$method}($value));
    }

    #[DataProvider('findUserProvider')]
    public function testFindUserMapsOrmUserToDomainUser(string $method, string|int $value): void
    {
        $repository = new TestUserRepository($this->createMock(EntityManagerInterface::class));
        $repository->findOneByResult = UserFactory::ormUser(
            id: 9,
            email: 'user@example.com',
            confirmedEmail: 'user@example.com',
            password: UserFactory::VALID_PASSWORD_HASH,
            username: 'username',
            roles: ['ROLE_ADMIN'],
            passwordToken: UserFactory::VALID_PASSWORD_TOKEN,
            passwordTokenExpiresAt: new \DateTimeImmutable('+1 hour'),
            emailToken: UserFactory::VALID_EMAIL_TOKEN,
            emailTokenExpiresAt: new \DateTimeImmutable('+2 hours'),
        );

        $user = $repository->{$method}($value);

        self::assertInstanceOf(DomainUser::class, $user);
        self::assertSame(9, $user->getId());
        self::assertSame('user@example.com', $user->getEmail());
        self::assertSame('user@example.com', $user->getConfirmedEmail());
        self::assertSame(UserFactory::VALID_PASSWORD_HASH, $user->getPassword());
        self::assertSame('username', $user->getUsername());
        self::assertSame(['ROLE_ADMIN', 'ROLE_USER'], $user->getRoles());
        self::assertSame(UserFactory::VALID_PASSWORD_TOKEN, $user->getPasswordToken());
        self::assertSame(UserFactory::VALID_EMAIL_TOKEN, $user->getEmailToken());
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
            password: UserFactory::VALID_PASSWORD_HASH,
            username: 'new_user',
            roles: ['ROLE_ADMIN'],
            passwordToken: UserFactory::VALID_PASSWORD_TOKEN,
            passwordTokenExpiresAt: new \DateTimeImmutable('+1 hour'),
            emailToken: UserFactory::VALID_EMAIL_TOKEN,
            emailTokenExpiresAt: new \DateTimeImmutable('+2 hours'),
        );

        $entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->callback(static function (User $user): bool {
                self::assertSame('new@example.com', $user->getEmail());
                self::assertNull($user->getConfirmedEmail());
                self::assertSame(UserFactory::VALID_PASSWORD_HASH, $user->getPassword());
                self::assertSame('new_user', $user->getUsername());
                self::assertSame(['ROLE_ADMIN', 'ROLE_USER'], $user->getRoles());
                self::assertSame(UserFactory::VALID_PASSWORD_TOKEN, $user->getPasswordToken());
                self::assertSame(UserFactory::VALID_EMAIL_TOKEN, $user->getEmailToken());

                UserFactory::forceId($user, 77);

                return true;
            }));

        $entityManager->expects($this->once())->method('flush');

        $createdUser = $repository->createDomainUser($domainUser);

        self::assertSame(77, $createdUser->getId());
        self::assertSame('new@example.com', $createdUser->getEmail());
        self::assertSame(['ROLE_ADMIN', 'ROLE_USER'], $createdUser->getRoles());
        self::assertSame(UserFactory::VALID_EMAIL_TOKEN, $createdUser->getEmailToken());
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
            password: UserFactory::VALID_PASSWORD_HASH_ALT,
            username: 'new_user',
            roles: ['ROLE_ADMIN'],
            passwordToken: UserFactory::VALID_PASSWORD_TOKEN,
            passwordTokenExpiresAt: new \DateTimeImmutable('+1 hour'),
            emailToken: UserFactory::VALID_EMAIL_TOKEN,
            emailTokenExpiresAt: new \DateTimeImmutable('+2 hours'),
        ));

        self::assertSame('new@example.com', $existingUser->getEmail());
        self::assertSame('confirmed@example.com', $existingUser->getConfirmedEmail());
        self::assertSame(UserFactory::VALID_PASSWORD_HASH_ALT, $existingUser->getPassword());
        self::assertSame('new_user', $existingUser->getUsername());
        self::assertSame(UserFactory::VALID_PASSWORD_TOKEN, $existingUser->getPasswordToken());
        self::assertSame(UserFactory::VALID_EMAIL_TOKEN, $existingUser->getEmailToken());

        self::assertSame(5, $updatedUser->getId());
        self::assertSame('new@example.com', $updatedUser->getEmail());
        self::assertSame(['ROLE_ADMIN', 'ROLE_USER'], $updatedUser->getRoles());
    }

    /**
     * @return iterable<string, array{0: string, 1: string|int}>
     */
    public static function findUserProvider(): iterable
    {
        yield 'by id' => ['findUserById', 9];
        yield 'by email' => ['findUserByEmail', 'user@example.com'];
        yield 'by username' => ['findUserByUsername', 'username'];
        yield 'by email token' => ['findUserByEmailToken', UserFactory::VALID_EMAIL_TOKEN];
        yield 'by password token' => ['findUserByPasswordToken', UserFactory::VALID_PASSWORD_TOKEN];
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
