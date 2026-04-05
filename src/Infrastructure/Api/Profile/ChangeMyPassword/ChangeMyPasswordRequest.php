<?php

namespace App\Infrastructure\Api\Profile\ChangeMyPassword;

use App\Infrastructure\Validator\Compounds as Compound;
use App\Infrastructure\Validator\Constraints as Constraint;


#[Constraint\ValidRepeatedPassword]
final readonly class ChangeMyPasswordRequest
{
    public function __construct(
        #[Compound\CurrentPassword]
        public string $currentPassword,

        #[Compound\Password]
        public string $newPassword,

        #[Compound\RepeatedPassword]
        public string $repeatPassword,
    ) {}
}
