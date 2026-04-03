<?php

namespace App\DTO\Request;

use App\Validator\Compounds as Compound;
use App\Validator\Constraints as Constraint;
use Symfony\Component\Validator\Constraints as Assert;

#[Constraint\ValidRepeatedPassword]
final readonly class EmailVerifyRequest
{
    public function __construct(
        #[Compound\Token]
        public string $token,
    ){}
}
