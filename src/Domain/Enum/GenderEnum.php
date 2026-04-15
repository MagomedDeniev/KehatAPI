<?php

declare(strict_types=1);

namespace App\Domain\Enum;

enum GenderEnum: string
{
    case MALE = 'male';
    case FEMALE = 'female';
}
