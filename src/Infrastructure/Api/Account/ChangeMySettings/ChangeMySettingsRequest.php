<?php

declare(strict_types=1);

namespace App\Infrastructure\Api\Account\ChangeMySettings;

use App\Infrastructure\Validator\Compounds as Compound;
use App\Infrastructure\Validator\Constraints as Constraint;

#[Constraint\UniqueUserCredentials]
final readonly class ChangeMySettingsRequest
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
