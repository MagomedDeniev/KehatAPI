<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Validator\Constraints;

use App\Infrastructure\Api\Account\ChangeMySettings\ChangeMySettingsRequest;
use App\Infrastructure\Api\Auth\Register\RegisterRequest;
use App\Infrastructure\Doctrine\Repository\UserRepository;
use App\Infrastructure\Validator\Constraints\UniqueUserCredentials;
use App\Infrastructure\Validator\Constraints\UniqueUserCredentialsValidator;
use App\Tests\Support\UserFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<UniqueUserCredentialsValidator>
 */
final class UniqueUserCredentialsValidatorTest extends ConstraintValidatorTestCase
{
    private UserRepository&MockObject $repository;
    private Security&MockObject $security;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(UserRepository::class);
        $this->security = $this->createMock(Security::class);

        parent::setUp();
    }

    protected function createValidator(): ConstraintValidatorInterface
    {
        return new UniqueUserCredentialsValidator($this->repository, $this->security);
    }

    public function testItRejectsUnexpectedConstraint(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate(new \stdClass(), $this->createMock(Constraint::class));
    }

    public function testItIgnoresUnsupportedValueObjects(): void
    {
        $this->repository->expects($this->never())->method('findOneBy');

        $this->validator->validate(new \stdClass(), new UniqueUserCredentials());

        $this->assertNoViolation();
    }

    public function testItBuildsViolationsForRegisterRequestDuplicates(): void
    {
        $request = new RegisterRequest(' User_Name ', 'male', '1990-05-20', ' User@example.com ', '12345678');

        $this->repository
            ->expects($this->exactly(2))
            ->method('findOneBy')
            ->willReturnCallback(static function (array $criteria): ?object {
                return match ($criteria) {
                    ['email' => 'user@example.com'] => UserFactory::ormUser(id: 2, email: 'user@example.com'),
                    ['username' => 'User_Name'] => UserFactory::ormUser(id: 3, username: 'User_Name'),
                    default => null,
                };
            });

        $this->validator->validate($request, new UniqueUserCredentials());

        $violations = iterator_to_array($this->context->getViolations());

        self::assertCount(2, $violations);
        self::assertSame('There is already an account with this email.', $violations[0]->getMessage());
        self::assertSame('property.path.email', $violations[0]->getPropertyPath());
        self::assertSame('There is already an account with this username.', $violations[1]->getMessage());
        self::assertSame('property.path.username', $violations[1]->getPropertyPath());
    }

    public function testItAllowsUniqueRegisterCredentials(): void
    {
        $request = new RegisterRequest('username', 'male', '1990-05-20', 'user@example.com', '12345678');

        $this->repository->expects($this->exactly(2))->method('findOneBy')->willReturn(null);

        $this->validator->validate($request, new UniqueUserCredentials());

        $this->assertNoViolation();
    }

    public function testItIgnoresChangeMySettingsWhenCurrentUserIsMissing(): void
    {
        $request = new ChangeMySettingsRequest('username', 'male', '1990-05-20', 'user@example.com');

        $this->security->expects($this->once())->method('getUser')->willReturn(null);
        $this->repository->expects($this->never())->method('findOneBy');

        $this->validator->validate($request, new UniqueUserCredentials());

        $this->assertNoViolation();
    }

    public function testItBuildsViolationsWhenChangeMySettingsCredentialsBelongToAnotherUser(): void
    {
        $request = new ChangeMySettingsRequest('new_user', 'male', '1990-05-20', 'new@example.com');

        $this->security->expects($this->once())->method('getUser')->willReturn(UserFactory::ormUser(id: 10, email: 'current@example.com', username: 'current_user'));
        $this->repository
            ->expects($this->exactly(2))
            ->method('findOneBy')
            ->willReturnCallback(static function (array $criteria): ?object {
                return match ($criteria) {
                    ['email' => 'new@example.com'] => UserFactory::ormUser(id: 11, email: 'new@example.com'),
                    ['username' => 'new_user'] => UserFactory::ormUser(id: 12, username: 'new_user'),
                    default => null,
                };
            });

        $this->validator->validate($request, new UniqueUserCredentials());

        $violations = iterator_to_array($this->context->getViolations());

        self::assertCount(2, $violations);
        self::assertSame('There is already an account with this email.', $violations[0]->getMessage());
        self::assertSame('property.path.email', $violations[0]->getPropertyPath());
        self::assertSame('There is already an account with this username.', $violations[1]->getMessage());
        self::assertSame('property.path.username', $violations[1]->getPropertyPath());
    }

    public function testItAllowsChangeMySettingsToReuseOwnCredentials(): void
    {
        $request = new ChangeMySettingsRequest('current_user', 'male', '1990-05-20', 'current@example.com');

        $this->security->expects($this->once())->method('getUser')->willReturn(UserFactory::ormUser(id: 10, email: 'current@example.com', username: 'current_user'));
        $this->repository
            ->expects($this->exactly(2))
            ->method('findOneBy')
            ->willReturnCallback(static function (array $criteria): ?object {
                return match ($criteria) {
                    ['email' => 'current@example.com'] => UserFactory::ormUser(id: 10, email: 'current@example.com'),
                    ['username' => 'current_user'] => UserFactory::ormUser(id: 10, username: 'current_user'),
                    default => null,
                };
            });

        $this->validator->validate($request, new UniqueUserCredentials());

        $this->assertNoViolation();
    }
}
