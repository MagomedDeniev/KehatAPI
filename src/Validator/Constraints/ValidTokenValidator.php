<?php

namespace App\Validator\Constraints;

use App\Entity\Token;
use App\Repository\TokenRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class ValidTokenValidator extends ConstraintValidator
{
    public function __construct(
        private readonly TokenRepository $tokenRepository
    ) {}

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidToken) {
            throw new UnexpectedTypeException($constraint, ValidToken::class);
        }

        $token = $this->tokenRepository->findOneBy(['token' => $value]);

        if (!$token instanceof Token || !$token->isValid()) {
            $this->context
                ->buildViolation($constraint->message)
                ->addViolation()
            ;
        }
    }
}
