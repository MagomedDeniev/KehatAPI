<?php

declare(strict_types=1);

namespace App\Validator\Constraints;

use App\Api\Account\SettingsChange\SettingsChangeRequest;
use App\Api\Auth\Register\RegisterRequest;
use App\Doctrine\Entity\User;
use App\Doctrine\Repository\UserRepository;
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
            && !$value instanceof SettingsChangeRequest) {
            return;
        }

        $email = mb_strtolower(trim($value->email));
        $username = trim($value->username);

        if ($value instanceof SettingsChangeRequest) {
            $currentUser = $this->security->getUser();

            if (!$currentUser instanceof User || null === $currentUser->getId()) {
                return;
            }

            $currentUserId = $currentUser->getId();

            if ('' !== $email) {
                $userByEmail = $this->userRepository->findOneBy(['email' => $email]);

                if (null !== $userByEmail && $userByEmail->getId() !== $currentUserId) {
                    $this->context
                        ->buildViolation($constraint->emailMessage)
                        ->atPath('email')
                        ->addViolation();
                }
            }

            if ('' !== $username) {
                $userByUsername = $this->userRepository->findOneBy(['username' => $username]);

                if (null !== $userByUsername && $userByUsername->getId() !== $currentUserId) {
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
