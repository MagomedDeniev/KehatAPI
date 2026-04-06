<?php

declare(strict_types=1);

namespace App\Infrastructure\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class ValidRepeatedPassword extends Constraint
{
    public string $message = 'Passwords do not match.';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
