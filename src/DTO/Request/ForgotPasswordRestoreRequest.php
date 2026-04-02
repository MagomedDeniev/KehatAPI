<?php

namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class ForgotPasswordRestoreRequest
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 8, max: 4096, minMessage: 'Your password should be at least {{ limit }} characters')]
    public string $newPassword = '';

    #[Assert\NotBlank]
    public string $repeatPassword = '';
}
