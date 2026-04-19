<?php

declare(strict_types=1);

namespace App\Api\Account\SettingsChange;

use App\Validator\Compounds as Compound;
use App\Validator\Constraints as Constraint;

#[Constraint\UniqueUserCredentials]
final readonly class SettingsChangeRequest
{
    public function __construct(
        #[Compound\Username]
        public string $username,

        #[Compound\Gender]
        public string $gender,

        #[Compound\BirthDate]
        public string $birthDate,

        #[Compound\Email]
        public string $email,
    ) {
    }
}
