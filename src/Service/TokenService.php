<?php

namespace App\Service;

use App\DTO\Request\ChangeMeRequest;
use App\DTO\Request\RegisterRequest;
use App\Entity\Token;
use App\Entity\User;
use App\Repository\TokenRepository;
use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;

final readonly class TokenService
{
    public function __construct(
        private EntityManagerInterface      $em,
        private TokenGeneratorInterface     $tokenGenerator,
        private TokenRepository             $tokenRepository,
        private int                         $tokenTimeOutSeconds
    ){}

    public function createEmailConfirmationToken(User $user): string
    {
        $token = new Token();

        $rawToken = $this->tokenGenerator->generateToken();

        $token
            ->setUserId($user->getId())
            ->setEmail($user->getEmail())
            ->setToken($rawToken)
            ->setExpiresAt((new DateTimeImmutable())->modify(sprintf('+%d seconds', $this->tokenTimeOutSeconds)))
        ;

        $this->em->persist($token);
        $this->em->flush();

        return $rawToken;
    }

    public function createPasswordRecoveryToken(User $user): string
    {
        $token = new Token();

        $rawToken = $this->tokenGenerator->generateToken();

        $token
            ->setUserId($user->getId())
            ->setToken($rawToken)
            ->setExpiresAt((new DateTimeImmutable())->modify(sprintf('+%d seconds', $this->tokenTimeOutSeconds)))
        ;

        $this->em->persist($token);
        $this->em->flush();

        return $rawToken;
    }

    public function getToken($token): Token
    {
        return $this->tokenRepository->findOneBy(['token' => $token]);
    }

    public function removeToken(Token $token, bool $flush = true): void
    {
        $this->em->remove($token);

        if ($flush) {
            $this->em->flush();
        }
    }
}
