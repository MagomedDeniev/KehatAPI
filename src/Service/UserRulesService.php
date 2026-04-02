<?php

namespace App\Service;

final class UserRulesService
{
    public const USERNAME_MIN = 6;
    public const USERNAME_MAX = 180;
    public const EMAIL_MIN = 8;
    public const EMAIL_MAX = 180;
    public const PASSWORD_MIN = 8;
    public const PASSWORD_MAX = 4096;
    public const TOKEN_MIN = 32;
    public const TOKEN_MAX = 255;

}
