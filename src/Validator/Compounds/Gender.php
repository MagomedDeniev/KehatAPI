<?php

declare(strict_types=1);

namespace App\Validator\Compounds;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Compound;

#[\Attribute]
final class Gender extends Compound
{
    protected function getConstraints(array $options): array
    {
        return [
            new Assert\NotBlank(),
            new Assert\Choice(
                choices: ['male', 'female'],
                message: 'The selected gender is invalid.',
            ),
        ];
    }
}
