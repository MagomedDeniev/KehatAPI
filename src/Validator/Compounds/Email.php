<?php

namespace App\Validator\Compounds;

use App\Service\UserRulesService;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Compound;

#[\Attribute]
final class Email extends Compound
{
    protected function getConstraints(array $options): array
    {
        return [
            new Assert\NotBlank(),
            new Assert\Email(),
            new Assert\Length(min: UserRulesService::EMAIL_MIN, max: UserRulesService::EMAIL_MAX),
        ];
    }
}
