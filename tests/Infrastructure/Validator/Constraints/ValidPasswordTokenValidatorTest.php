<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Validator\Constraints;

use App\Infrastructure\Doctrine\Repository\UserRepository;
use App\Infrastructure\Validator\Constraints\ValidPasswordToken;
use App\Infrastructure\Validator\Constraints\ValidPasswordTokenValidator;
use App\Tests\Support\UserFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

final class ValidPasswordTokenValidatorTest extends ConstraintValidatorTestCase
{
    private UserRepository&MockObject $repository;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(UserRepository::class);

        parent::setUp();
    }

    protected function createValidator(): ConstraintValidatorInterface
    {
        return new ValidPasswordTokenValidator($this->repository);
    }

    public function testItRejectsUnexpectedConstraint(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate('token', $this->createMock(Constraint::class));
    }

    public function testItAddsViolationWhenUserIsMissing(): void
    {
        $this->repository->expects($this->once())->method('findOneBy')->with(['passwordToken' => 'missing-token'])->willReturn(null);

        $this->validator->validate('missing-token', new ValidPasswordToken());

        $this->buildViolation('The link is invalid or has expired, please try again.')
            ->assertRaised();
    }

    public function testItAddsViolationWhenTokenIsExpired(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['passwordToken' => 'expired-token'])
            ->willReturn(UserFactory::ormUser(passwordToken: 'expired-token', passwordTokenExpiresAt: new \DateTimeImmutable('-1 hour')));

        $this->validator->validate('expired-token', new ValidPasswordToken());

        $this->buildViolation('The link is invalid or has expired, please try again.')
            ->assertRaised();
    }

    public function testItAllowsValidToken(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['passwordToken' => 'valid-token'])
            ->willReturn(UserFactory::ormUser(passwordToken: 'valid-token', passwordTokenExpiresAt: new \DateTimeImmutable('+1 hour')));

        $this->validator->validate('valid-token', new ValidPasswordToken());

        $this->assertNoViolation();
    }
}
