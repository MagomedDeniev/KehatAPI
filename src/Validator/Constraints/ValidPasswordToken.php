<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class ValidPasswordToken extends Constraint
{
    public string $message = 'The link is invalid or has expired, please try again.';
}
