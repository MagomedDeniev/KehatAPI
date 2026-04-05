<?php

namespace App\Infrastructure\Validator\Compounds;

use App\Domain\Rules\UserRules;
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
            new Assert\Length(min: UserRules::EMAIL_MIN, max: UserRules::EMAIL_MAX),
        ];
    }
}
