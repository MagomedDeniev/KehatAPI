<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class ValidEmailToken extends Constraint
{
    public string $message = 'The link is invalid or has expired, please try again.';
}
