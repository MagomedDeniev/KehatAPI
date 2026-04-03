<?php

namespace App\Validator\Compounds;

use App\Service\UserRulesService;
use App\Validator\Constraints as Constraint;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Compound;

#[\Attribute]
final class PasswordToken extends Compound
{
    protected function getConstraints(array $options): array
    {
        return [
            new Assert\NotBlank(),
            new Assert\Length(min: UserRulesService::TOKEN_MIN, max: UserRulesService::TOKEN_MAX),
            new Constraint\ValidPasswordToken()
        ];
    }
}
