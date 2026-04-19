<?php

declare(strict_types=1);

namespace App\Validator\Compounds;

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
            new Assert\Length(
                min: UserRules::EMAIL_MIN,
                max: UserRules::EMAIL_MAX,
                minMessage: 'Email should have {{ min }} characters or more.',
                maxMessage: 'Email should have {{ min }} characters or more.',
            ),
        ];
    }
}
