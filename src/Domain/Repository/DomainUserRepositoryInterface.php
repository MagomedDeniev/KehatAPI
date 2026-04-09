<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\DomainUser;

interface DomainUserRepositoryInterface
{
    public function findUserById(int $id): ?DomainUser;

    public function findUserByEmail(string $email): ?DomainUser;

    public function findUserByUsername(string $username): ?DomainUser;

    public function findUserByEmailToken(string $emailToken): ?DomainUser;

    public function findUserByPasswordToken(string $passwordToken): ?DomainUser;

    public function createDomainUser(DomainUser $domainUser): DomainUser;

    public function updateDomainUser(DomainUser $domainUser): DomainUser;
}
