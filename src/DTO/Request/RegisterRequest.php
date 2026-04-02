<?php

namespace App\DTO\Request;

use App\Validator\Compounds as Compound;

final readonly class RegisterRequest
{
    public function __construct(
        #[Compound\Username]
        public string $username,

        #[Compound\Email]
        public string $email,

        #[Compound\Password]
        public string $password,
    ){}
}
