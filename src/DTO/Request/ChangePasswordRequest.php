<?php

namespace App\DTO\Request;

use App\Validator\Compounds as Compound;
use App\Validator\Constraints as Constraint;


#[Constraint\ValidRepeatedPassword]
final readonly class ChangePasswordRequest
{
    public function __construct(
        #[Compound\CurrentPassword]
        public string $currentPassword,

        #[Compound\Password]
        public string $newPassword,

        #[Compound\RepeatedPassword]
        public string $repeatPassword,
    ){}
}
