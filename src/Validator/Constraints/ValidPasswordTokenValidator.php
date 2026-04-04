<?php

namespace App\Validator\Constraints;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class ValidPasswordTokenValidator extends ConstraintValidator
{
    public function __construct(
        private readonly UserRepository $userRepository
    ) {}

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidPasswordToken) {
            throw new UnexpectedTypeException($constraint, ValidPasswordToken::class);
        }

        $user = $this->userRepository->findOneBy(['passwordToken' => $value]);

        if (!$user instanceof User || !$user->tokenIsValid($value, 'password')) {
            $this->context
                ->buildViolation($constraint->message)
                ->addViolation()
            ;
        }
    }
}
