<?php

namespace App\Validator\Compounds;

use App\Service\UserRulesService;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Compound;

#[\Attribute]
final class Password extends Compound
{
    protected function getConstraints(array $options): array
    {
        return [
            new Assert\NotBlank(),
            new Assert\Length(min: UserRulesService::PASSWORD_MIN, max: UserRulesService::PASSWORD_MAX)
        ];
    }
}
