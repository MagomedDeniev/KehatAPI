<?php

namespace App\DTO\Request;

use App\Validator\Compounds as Compound;
use App\Validator\Constraints as Constraint;
use Symfony\Component\Validator\Constraints as Assert;

#[Constraint\ValidRepeatedPassword]
final readonly class ForgotPasswordRestoreRequest
{
    public function __construct(
        #[Compound\Token]
        public string $token,

        #[Compound\Password]
        public string $newPassword,

        #[Assert\NotBlank]
        public string $repeatPassword,
    ){}
}
