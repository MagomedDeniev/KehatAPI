<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Validator\Constraints;

use App\Infrastructure\Doctrine\Repository\UserRepository;
use App\Infrastructure\Validator\Constraints\ValidEmailToken;
use App\Infrastructure\Validator\Constraints\ValidEmailTokenValidator;
use App\Tests\Support\UserFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<ValidEmailTokenValidator>
 */
final class ValidEmailTokenValidatorTest extends ConstraintValidatorTestCase
{
    private UserRepository&MockObject $repository;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(UserRepository::class);

        parent::setUp();
    }

    protected function createValidator(): ConstraintValidatorInterface
    {
        return new ValidEmailTokenValidator($this->repository);
    }

    public function testItRejectsUnexpectedConstraint(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate('token', $this->createMock(Constraint::class));
    }

    public function testItAddsViolationWhenUserIsMissing(): void
    {
        $this->repository->expects($this->once())->method('findOneBy')->with(['emailToken' => 'missing-token'])->willReturn(null);

        $this->validator->validate('missing-token', new ValidEmailToken());

        $this->buildViolation('The link is invalid or has expired, please try again.')
            ->assertRaised();
    }

    public function testItAddsViolationWhenTokenIsExpired(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['emailToken' => 'expired-token'])
            ->willReturn(UserFactory::ormUser(emailToken: 'expired-token', emailTokenExpiresAt: new \DateTimeImmutable('-1 hour')));

        $this->validator->validate('expired-token', new ValidEmailToken());

        $this->buildViolation('The link is invalid or has expired, please try again.')
            ->assertRaised();
    }

    public function testItAllowsValidToken(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['emailToken' => 'valid-token'])
            ->willReturn(UserFactory::ormUser(emailToken: 'valid-token', emailTokenExpiresAt: new \DateTimeImmutable('+1 hour')));

        $this->validator->validate('valid-token', new ValidEmailToken());

        $this->assertNoViolation();
    }
}
