<?php

declare(strict_types=1);

namespace App\Validator\Compounds;

use Symfony\Component\Security\Core\Validator\Constraints as SecurityAssert;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Compound;

#[\Attribute]
final class CurrentPassword extends Compound
{
    protected function getConstraints(array $options): array
    {
        return [
            new Assert\NotBlank(),
            new SecurityAssert\UserPassword(message: 'Current password is incorrect.'),
        ];
    }
}
