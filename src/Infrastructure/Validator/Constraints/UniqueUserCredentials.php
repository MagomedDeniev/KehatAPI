<?php

declare(strict_types=1);

namespace App\Infrastructure\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class UniqueUserCredentials extends Constraint
{
    public string $emailMessage = 'There is already an account with this email.';
    public string $usernameMessage = 'There is already an account with this username.';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
