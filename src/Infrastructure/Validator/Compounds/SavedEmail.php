<?php

declare(strict_types=1);

namespace App\Infrastructure\Validator\Compounds;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Compound;

/**
 * Чтобы уже когда-то сохраненная почта принималась без доп. правил валидации.
 */
#[\Attribute]
final class SavedEmail extends Compound
{
    protected function getConstraints(array $options): array
    {
        return [
            new Assert\NotBlank(),
            new Assert\Email(),
        ];
    }
}
