<?php

namespace App\DTO\Request;

use App\Validator\Compounds as Compound;
use Symfony\Component\Validator\Constraints as Assert;
use App\Validator\Constraints as Constraint;


#[Constraint\ValidRepeatedPassword]
final readonly class ChangePasswordRequest
{
    public function __construct(
        #[Compound\CurrentPassword]
        public string $currentPassword,

        #[Compound\Password]
        public string $newPassword,

        #[Assert\NotBlank]
        public string $repeatPassword,
    ){}
}
