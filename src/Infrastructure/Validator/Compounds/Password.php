<?php

namespace App\Infrastructure\Validator\Compounds;

use App\Domain\Rules\UserRules;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Compound;

#[\Attribute]
final class Password extends Compound
{
    protected function getConstraints(array $options): array
    {
        return [
            new Assert\NotBlank(),
            new Assert\Length(min: UserRules::PASSWORD_MIN, max: UserRules::PASSWORD_MAX)
        ];
    }
}
