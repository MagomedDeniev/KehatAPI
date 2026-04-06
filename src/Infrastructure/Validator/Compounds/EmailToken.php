<?php

declare(strict_types=1);

namespace App\Infrastructure\Validator\Compounds;

use App\Domain\Rules\UserRules;
use App\Infrastructure\Validator\Constraints as Constraint;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Compound;

#[\Attribute]
final class EmailToken extends Compound
{
    protected function getConstraints(array $options): array
    {
        return [
            new Assert\NotBlank(),
            new Assert\Length(min: UserRules::TOKEN_MIN, max: UserRules::TOKEN_MAX),
            new Constraint\ValidEmailToken(),
        ];
    }
}
