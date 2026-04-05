<?php

namespace App\Infrastructure\Api\Profile\ChangeMySettings;

use App\Infrastructure\Validator\Compounds as Compound;
use App\Infrastructure\Validator\Constraints as Constraint;

#[Constraint\UniqueUserCredentials]
final readonly class ChangeMySettingsRequest
{
    public function __construct(
        #[Compound\Username]
        public string $username,

        #[Compound\Email]
        public string $email,
    ) {}
}
