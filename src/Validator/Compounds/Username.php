<?php

namespace App\Validator\Compounds;

use App\Service\UserRulesService;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Compound;

#[\Attribute]
final class Username extends Compound
{
    protected function getConstraints(array $options): array
    {
        return [
            new Assert\NotBlank(),
            new Assert\Length(min: UserRulesService::USERNAME_MIN, max: UserRulesService::USERNAME_MAX),
            new Assert\Regex(
                pattern: '/^[a-z0-9._]+$/i',
                message: 'form.username.can.consist.symbols',
                htmlPattern: '^[a-zA-Z0-9._]+$'
            ),
        ];
    }
}
