<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\DomainUser;

interface DomainUserRepositoryInterface
{
    /**
     * @param array<string, mixed> $criteria
     */
    public function findUserBy(array $criteria): ?DomainUser;

    public function createDomainUser(DomainUser $domainUser): DomainUser;

    public function updateDomainUser(DomainUser $domainUser): DomainUser;
}
