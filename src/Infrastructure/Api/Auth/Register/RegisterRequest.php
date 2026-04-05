<?php

namespace App\Infrastructure\Api\Auth\Register;

use App\Infrastructure\Validator\Compounds as Compound;
use App\Infrastructure\Validator\Constraints as Constraint;

#[Constraint\UniqueUserCredentials]
final readonly class RegisterRequest
{
    public function __construct(
        #[Compound\Username]
        public string $username,

        #[Compound\Email]
        public string $email,

        #[Compound\Password]
        public string $password,
    ) {}
}
