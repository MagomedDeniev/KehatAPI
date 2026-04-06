<?php

declare(strict_types=1);

namespace App\Infrastructure\Validator\Constraints;

use App\Infrastructure\Api\Auth\Register\RegisterRequest;
use App\Infrastructure\Api\Profile\ChangeMySettings\ChangeMySettingsRequest;
use App\Infrastructure\Doctrine\Repository\UserRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class UniqueUserCredentialsValidator extends ConstraintValidator
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly Security $security,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof UniqueUserCredentials) {
            throw new UnexpectedTypeException($constraint, UniqueUserCredentials::class);
        }

        if (!$value instanceof RegisterRequest
            && !$value instanceof ChangeMySettingsRequest) {
            return;
        }

        $email = mb_strtolower(trim($value->email));
        $username = trim($value->username);

        if ($value instanceof ChangeMySettingsRequest) {
            $currentSecurityUser = $this->security->getUser();

            if (null === $currentSecurityUser) {
                return;
            }

            $currentUser = $this->userRepository->findOneBy([
                'email' => $currentSecurityUser->getUserIdentifier(),
            ]);

            if ('' !== $email) {
                $userByEmail = $this->userRepository->findOneBy(['email' => $email]);

                if (null !== $userByEmail && $userByEmail->getId() !== $currentUser?->getId()) {
                    $this->context
                        ->buildViolation($constraint->emailMessage)
                        ->atPath('email')
                        ->addViolation();
                }
            }

            if ('' !== $username) {
                $userByUsername = $this->userRepository->findOneBy(['username' => $username]);

                if (null !== $userByUsername && $userByUsername->getId() !== $currentUser?->getId()) {
                    $this->context
                        ->buildViolation($constraint->usernameMessage)
                        ->atPath('username')
                        ->addViolation();
                }
            }

            return;
        }

        if ('' !== $email && null !== $this->userRepository->findOneBy(['email' => $email])) {
            $this->context
                ->buildViolation($constraint->emailMessage)
                ->atPath('email')
                ->addViolation();
        }

        if ('' !== $username && null !== $this->userRepository->findOneBy(['username' => $username])) {
            $this->context
                ->buildViolation($constraint->usernameMessage)
                ->atPath('username')
                ->addViolation();
        }
    }
}
