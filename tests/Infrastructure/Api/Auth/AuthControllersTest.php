<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Api\Auth;

use App\Application\Auth\ConfirmEmail\ConfirmEmailHandler;
use App\Application\Auth\ForgotPassword\ForgotPasswordHandler;
use App\Application\Auth\Register\RegisterHandler;
use App\Application\Auth\RestorePassword\RestorePasswordHandler;
use App\Application\Contract\PasswordHasherInterface;
use App\Domain\Entity\DomainUser;
use App\Domain\Repository\DomainUserRepositoryInterface;
use App\Infrastructure\Api\Auth\ConfirmEmail\ConfirmEmailController;
use App\Infrastructure\Api\Auth\ConfirmEmail\ConfirmEmailRequest;
use App\Infrastructure\Api\Auth\ForgotPassword\ForgotPasswordController;
use App\Infrastructure\Api\Auth\ForgotPassword\ForgotPasswordRequest;
use App\Infrastructure\Api\Auth\Register\RegisterController;
use App\Infrastructure\Api\Auth\Register\RegisterRequest;
use App\Infrastructure\Api\Auth\RestorePassword\RestorePasswordController;
use App\Infrastructure\Api\Auth\RestorePassword\RestorePasswordRequest;
use App\Infrastructure\Doctrine\Repository\UserRepository;
use App\Infrastructure\Service\JsonResponder;
use App\Infrastructure\Service\MailerService;
use App\Tests\Support\UserFactory;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;

final class AuthControllersTest extends TestCase
{
    public function testRegisterControllerReturnsCreatedPayload(): void
    {
        $repository = $this->createMock(DomainUserRepositoryInterface::class);
        $passwordHasher = $this->createMock(PasswordHasherInterface::class);
        $tokenGenerator = $this->createMock(TokenGeneratorInterface::class);
        $mailer = $this->createMock(MailerInterface::class);
        $userRepository = $this->createMock(UserRepository::class);
        $jwtManager = $this->createMock(JWTTokenManagerInterface::class);
        $ormUser = UserFactory::ormUser(id: 12, email: 'user@example.com', username: 'username');

        $repository->expects($this->once())->method('findUserByEmail')->with('user@example.com')->willReturn(null);
        $repository->expects($this->once())->method('findUserByUsername')->with('username')->willReturn(null);
        $passwordHasher->expects($this->once())->method('hash')->willReturn(UserFactory::VALID_PASSWORD_HASH);
        $tokenGenerator->expects($this->once())->method('generateToken')->willReturn(UserFactory::VALID_EMAIL_TOKEN);
        $repository->expects($this->once())->method('createDomainUser')->willReturn(UserFactory::domainUser(
            id: 12,
            email: 'user@example.com',
            confirmedEmail: null,
            password: UserFactory::VALID_PASSWORD_HASH,
            username: 'username',
            emailToken: UserFactory::VALID_EMAIL_TOKEN,
            emailTokenExpiresAt: new \DateTimeImmutable('+1 hour'),
        ));
        $mailer->expects($this->once())->method('send');
        $userRepository->expects($this->once())->method('findOneBy')->with(['id' => 12])->willReturn($ormUser);
        $jwtManager->expects($this->once())->method('create')->with($ormUser)->willReturn('jwt-token');

        $response = (new RegisterController())->register(
            new RegisterRequest('username', 'male', '1990-05-20', 'user@example.com', '12345678'),
            new RegisterHandler(
                $repository,
                $passwordHasher,
                $tokenGenerator,
                new MailerService($mailer, $this->createStub(LoggerInterface::class), 'no-reply@example.com', 'Kehat'),
            ),
            new JsonResponder(),
            $userRepository,
            $jwtManager,
        );

        self::assertSame(201, $response->getStatusCode());
        self::assertSame([
            'success' => true,
            'data' => [
                'token' => 'jwt-token',
            ],
            'message' => 'User successfully registered, check your email for further instructions.',
        ], $this->decodeResponse($response->getContent()));
    }

    public function testForgotPasswordControllerReturnsGenericSuccessPayload(): void
    {
        $repository = $this->createMock(DomainUserRepositoryInterface::class);
        $tokenGenerator = $this->createMock(TokenGeneratorInterface::class);
        $mailer = $this->createMock(MailerInterface::class);
        $request = Request::create('/api/forgot/password', 'POST', server: ['REMOTE_ADDR' => '127.0.0.1']);

        $repository->expects($this->once())->method('findUserByEmail')->with('user@example.com')->willReturn(null);
        $tokenGenerator->expects($this->never())->method('generateToken');
        $mailer->expects($this->never())->method('send');

        $response = (new ForgotPasswordController(
            $this->createNoLimitFactory('forgot-password-ip'),
            $this->createNoLimitFactory('forgot-password-email'),
        ))->forgotPassword(
            $request,
            new ForgotPasswordRequest('user@example.com'),
            new ForgotPasswordHandler(
                $repository,
                $tokenGenerator,
                new MailerService($mailer, $this->createStub(LoggerInterface::class), 'no-reply@example.com', 'Kehat'),
            ),
            new JsonResponder(),
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame([
            'success' => true,
            'data' => ['email' => 'user@example.com'],
            'message' => 'If email is valid, you will receive a link to reset your password.',
        ], $this->decodeResponse($response->getContent()));
    }

    public function testRestorePasswordControllerReturnsUserIdPayload(): void
    {
        $repository = $this->createMock(DomainUserRepositoryInterface::class);
        $passwordHasher = $this->createMock(PasswordHasherInterface::class);
        $user = UserFactory::domainUser(
            id: 5,
            passwordToken: 'valid-token',
            passwordTokenExpiresAt: new \DateTimeImmutable('+1 hour'),
        );

        $repository->expects($this->once())->method('findUserByPasswordToken')->with('valid-token')->willReturn($user);
        $newHashedPassword = password_hash('12345678', PASSWORD_BCRYPT);
        $passwordHasher->expects($this->once())->method('hash')->with('12345678')->willReturn($newHashedPassword);
        $repository->expects($this->once())->method('updateDomainUser')->with($this->isInstanceOf(DomainUser::class))->willReturnCallback(static fn (DomainUser $updatedUser): DomainUser => $updatedUser);

        $response = (new RestorePasswordController())->restorePassword(
            new RestorePasswordRequest('valid-token', '12345678'),
            new RestorePasswordHandler($passwordHasher, $repository),
            new JsonResponder(),
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame([
            'success' => true,
            'data' => ['userId' => 5],
            'message' => 'Your password has been restored, you can login now.',
        ], $this->decodeResponse($response->getContent()));
    }

    public function testConfirmEmailControllerReturnsEmailPayload(): void
    {
        $repository = $this->createMock(DomainUserRepositoryInterface::class);
        $user = UserFactory::domainUser(
            email: 'user@example.com',
            confirmedEmail: null,
            emailToken: 'valid-token',
            emailTokenExpiresAt: new \DateTimeImmutable('+1 hour'),
        );

        $repository->expects($this->once())->method('findUserByEmailToken')->with('valid-token')->willReturn($user);
        $repository->expects($this->once())->method('updateDomainUser')->with($this->isInstanceOf(DomainUser::class))->willReturnCallback(static fn (DomainUser $updatedUser): DomainUser => $updatedUser);

        $response = (new ConfirmEmailController())->confirmEmail(
            new ConfirmEmailRequest('valid-token'),
            new ConfirmEmailHandler($repository),
            new JsonResponder(),
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame([
            'success' => true,
            'data' => ['email' => 'user@example.com'],
            'message' => 'Your email has been verified.',
        ], $this->decodeResponse($response->getContent()));
    }

    /**
     * @return array<mixed>
     */
    private function decodeResponse(string|false $content): array
    {
        self::assertIsString($content);

        $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($decoded);

        return $decoded;
    }

    private function createNoLimitFactory(string $id): RateLimiterFactory
    {
        return new RateLimiterFactory(
            ['id' => $id, 'policy' => 'no_limit'],
            new InMemoryStorage(),
        );
    }
}
