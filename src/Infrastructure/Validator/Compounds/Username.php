<?php

declare(strict_types=1);

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
                pattern: UserRules::USERNAME_PATTERN,
                message: 'username.can.consist.symbols'
            ),
        ];
    }
}
