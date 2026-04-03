<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class ValidEmailToken extends Constraint
{
    public string $message = 'Ссылка недействительна или срок её действия истёк, повторите попытку снова.';
}
