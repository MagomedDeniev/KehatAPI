<?php

namespace App\DTO\Request;

use App\Validator\Compounds as Compound;

final readonly class ChangeMeRequest
{
    public function __construct(
        #[Compound\Username]
        public string $username,

        #[Compound\Email]
        public string $email,
    ){}
}
