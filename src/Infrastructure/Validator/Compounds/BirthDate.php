<?php

declare(strict_types=1);

namespace App\Infrastructure\Validator\Compounds;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Compound;

#[\Attribute]
final class BirthDate extends Compound
{
    protected function getConstraints(array $options): array
    {
        return [
            new Assert\NotBlank(message: 'The birth date cannot be empty.'),
            new Assert\Date(),
        ];
    }
}
