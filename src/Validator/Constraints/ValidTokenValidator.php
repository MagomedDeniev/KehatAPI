<?php

namespace App\Validator\Constraints;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class ValidTokenValidator extends ConstraintValidator
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly int $tokenTimeOutSeconds,
    ) {}

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidToken) {
            throw new UnexpectedTypeException($constraint, ValidToken::class);
        }

        $user = $this->userRepository->findOneBy(['token' => $value]);

        if (!$user instanceof User || !$user->hasValidToken($this->tokenTimeOutSeconds)) {
            $this->context
                ->buildViolation($constraint->message)
                ->addViolation()
            ;
        }
    }
}
