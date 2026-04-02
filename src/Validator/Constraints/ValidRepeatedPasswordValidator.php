<?php

namespace App\Validator\Constraints;

use App\DTO\Request\ForgotPasswordRestoreRequest;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class ValidRepeatedPasswordValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidRepeatedPassword) {
            throw new UnexpectedTypeException($constraint, ValidRepeatedPassword::class);
        }

        if (!$value instanceof ForgotPasswordRestoreRequest) {
            throw new UnexpectedValueException($value, ForgotPasswordRestoreRequest::class);
        }

        if ($value->newPassword !== $value->repeatPassword) {
            $this->context
                ->buildViolation($constraint->message)
                ->atPath('repeatPassword')
                ->addViolation();
        }
    }
}
