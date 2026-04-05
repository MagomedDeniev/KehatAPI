<?php

namespace App\Infrastructure\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class ValidPasswordToken extends Constraint
{
    public string $message = 'The link is invalid or has expired, please try again.';
}
