<?php

namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class ChangeMeRequest
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 6, max: 180)]
    public string $username = '';

    #[Assert\NotBlank]
    #[Assert\Email]
    #[Assert\Length(min: 8, max: 180)]
    public string $email;
}
