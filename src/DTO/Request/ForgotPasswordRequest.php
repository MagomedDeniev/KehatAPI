<?php

namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class ForgotPasswordRequest
{
    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email;
}
