<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use App\Doctrine\Entity\User;
use App\Doctrine\Repository\UserRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class ValidEmailTokenValidator extends ConstraintValidator
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidEmailToken) {
            throw new UnexpectedTypeException($constraint, ValidEmailToken::class);
        }

        $user = $this->userRepository->findOneBy(['emailToken' => $value]);

        if (!$user instanceof User || !$user->hasValidEmailToken($value)) {
            $this->context
                ->buildViolation($constraint->message)
                ->addViolation()
            ;
        }
    }
}
