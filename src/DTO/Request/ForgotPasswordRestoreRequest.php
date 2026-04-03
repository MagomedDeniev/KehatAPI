<?php

namespace App\DTO\Request;

use App\Validator\Compounds as Compound;
use App\Validator\Constraints as Constraint;

#[Constraint\ValidRepeatedPassword]
final readonly class ForgotPasswordRestoreRequest
{
    public function __construct(
        #[Compound\PasswordToken]
        public string $token,

        #[Compound\Password]
        public string $newPassword,

        #[Compound\RepeatedPassword]
        public string $repeatPassword,
    ){}
}
