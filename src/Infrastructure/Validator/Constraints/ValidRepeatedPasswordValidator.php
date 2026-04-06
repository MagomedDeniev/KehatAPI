<?php

namespace App\Infrastructure\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class ValidRepeatedPasswordValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidRepeatedPassword) {
            throw new UnexpectedTypeException($constraint, ValidRepeatedPassword::class);
        }

        if ($value === null) {
            return;
        }

        if (!is_object($value)) {
            return;
        }

        if (!property_exists($value, 'newPassword') || !property_exists($value, 'repeatPassword')) {
            return;
        }

        if ($value->newPassword !== $value->repeatPassword) {
            $this->context
                ->buildViolation($constraint->message)
                ->atPath('repeatPassword')
                ->addViolation();
        }
    }
}
