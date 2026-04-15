<?php

declare(strict_types=1);

namespace App\Domain\Rules;

final class UserRules
{
    public const TOKEN_EXPIRATION_SECONDS = 3600;
    public const USERNAME_MIN = 8;
    public const USERNAME_MAX = 180;
    public const USERNAME_PATTERN = '/^[a-z0-9._]+$/i';
    public const EMAIL_MIN = 8;
    public const EMAIL_MAX = 80;
    public const PASSWORD_MIN = 8;
    public const PASSWORD_MAX = 4096;
    public const TOKEN_MIN = 32;
    public const TOKEN_MAX = 255;
}
