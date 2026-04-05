<?php

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
            $currentUser = $this->userRepository->findOneBy(['email' => $this->security->getUser()->getUserIdentifier()]);

            if ($email !== '') {
                $userByEmail = $this->userRepository->findOneBy(['email' => $email]);

                if ($userByEmail !== null && $userByEmail->getId() !== $currentUser?->getId()) {
                    $this->context
                        ->buildViolation($constraint->emailMessage)
                        ->atPath('email')
                        ->addViolation();
                }
            }

            if ($username !== '') {
                $userByUsername = $this->userRepository->findOneBy(['username' => $username]);

                if ($userByUsername !== null && $userByUsername->getId() !== $currentUser?->getId()) {
                    $this->context
                        ->buildViolation($constraint->usernameMessage)
                        ->atPath('username')
                        ->addViolation();
                }
            }

            return;
        }

        if ($email !== '' && $this->userRepository->findOneBy(['email' => $email]) !== null) {
            $this->context
                ->buildViolation($constraint->emailMessage)
                ->atPath('email')
                ->addViolation();
        }

        if ($username !== '' && $this->userRepository->findOneBy(['username' => $username]) !== null) {
            $this->context
                ->buildViolation($constraint->usernameMessage)
                ->atPath('username')
                ->addViolation();
        }
    }
}
