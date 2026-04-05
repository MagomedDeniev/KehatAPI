<?php

namespace App\Infrastructure\Validator\Compounds;

use App\Domain\Rules\UserRules;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Compound;

#[\Attribute]
final class Username extends Compound
{
    protected function getConstraints(array $options): array
    {
        return [
            new Assert\NotBlank(),
            new Assert\Length(min: UserRules::USERNAME_MIN, max: UserRules::USERNAME_MAX),
            new Assert\Regex(
                pattern: '/^[a-z0-9._]+$/i',
                message: 'form.username.can.consist.symbols',
                htmlPattern: '^[a-zA-Z0-9._]+$'
            ),
        ];
    }
}
