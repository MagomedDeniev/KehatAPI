<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class ValidRepeatedPassword extends Constraint
{
    public string $message = 'Пароли не совпадают.';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
