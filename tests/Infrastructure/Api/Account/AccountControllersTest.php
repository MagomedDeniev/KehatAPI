<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Api\Account;

use App\Application\Account\PasswordChange\PasswordChangeHandler;
use App\Application\Account\SettingsChange\SettingsChangeHandler;
use App\Application\Contract\PasswordHasherInterface;
use App\Domain\Entity\DomainUser;
use App\Domain\Repository\DomainUserRepositoryInterface;
use App\Infrastructure\Api\Account\PasswordChange\PasswordChangeController;
use App\Infrastructure\Api\Account\PasswordChange\PasswordChangeRequest;
use App\Infrastructure\Api\Account\SettingsChange\SettingsChangeController;
use App\Infrastructure\Api\Account\SettingsChange\SettingsChangeRequest;
use App\Infrastructure\Api\Account\CurrentUser\CurrentUserController;
use App\Infrastructure\Service\JsonResponder;
use App\Domain\Enum\GenderEnum;
use App\Infrastructure\Service\MailerService;
use App\Tests\Support\UserFactory;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;

final class AccountControllersTest extends TestCase
{
    public function testChangeMySettingsControllerReturnsSuccessPayload(): void
    {
        $repository = $this->createMock(DomainUserRepositoryInterface::class);
        $tokenGenerator = $this->createMock(TokenGeneratorInterface::class);
        $mailer = $this->createMock(MailerInterface::class);
        $jwtManager = $this->createMock(JWTTokenManagerInterface::class);
        $currentDomainUser = UserFactory::domainUser(id: 5, email: 'user@example.com', confirmedEmail: 'user@example.com', username: 'old_name');

        $repository->expects($this->once())->method('findUserById')->with(5)->willReturn($currentDomainUser);
        $repository->expects($this->once())->method('findUserByEmail')->with('user@example.com')->willReturn($currentDomainUser);
        $repository->expects($this->once())->method('findUserByUsername')->with('new_name')->willReturn($currentDomainUser);

        $tokenGenerator->expects($this->never())->method('generateToken');
        $mailer->expects($this->never())->method('send');
        $jwtManager->expects($this->never())->method('create');
        $repository->expects($this->once())->method('updateDomainUser')->with($this->isInstanceOf(DomainUser::class))->willReturnCallback(static fn (DomainUser $user): DomainUser => $user);

        $response = (new SettingsChangeController())->changeMySettings(
            UserFactory::ormUser(id: 5, email: 'user@example.com', username: 'old_name'),
            new SettingsChangeRequest('new_name', 'female', '1990-05-20', 'user@example.com'),
            new SettingsChangeHandler(
                $repository,
                $tokenGenerator,
                new MailerService($mailer, $this->createStub(LoggerInterface::class), 'no-reply@example.com', 'Kehat'),
            ),
            new JsonResponder(),
            $jwtManager,
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame([
            'success' => true,
            'data' => ['token' => null],
            'message' => 'Your settings updated successfully.',
        ], $this->decodeResponse($response->getContent()));
    }

    public function testChangeMySettingsControllerRejectsUserWithoutId(): void
    {
        $controller = new SettingsChangeController();

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('User not have id.');

        $controller->changeMySettings(
            UserFactory::ormUser(id: null),
            new SettingsChangeRequest('username', 'male', '1990-05-20', 'user@example.com'),
            new SettingsChangeHandler(
                $this->createMock(DomainUserRepositoryInterface::class),
                $this->createMock(TokenGeneratorInterface::class),
                new MailerService($this->createMock(MailerInterface::class), $this->createStub(LoggerInterface::class), 'no-reply@example.com', 'Kehat'),
            ),
            new JsonResponder(),
            $this->createMock(JWTTokenManagerInterface::class),
        );
    }

    public function testChangeMyPasswordControllerReturnsSuccessPayload(): void
    {
        $repository = $this->createMock(DomainUserRepositoryInterface::class);
        $passwordHasher = $this->createMock(PasswordHasherInterface::class);
        $storedHash = password_hash('current-password', PASSWORD_BCRYPT);
        $domainUser = UserFactory::domainUser(id: 5, password: $storedHash);
        $newHashedPassword = password_hash('12345678', PASSWORD_BCRYPT);

        $repository->expects($this->once())->method('findUserById')->with(5)->willReturn($domainUser);
        $passwordHasher->expects($this->once())->method('verify')->with($storedHash, 'current-password')->willReturn(true);
        $passwordHasher->expects($this->once())->method('hash')->with('12345678')->willReturn($newHashedPassword);
        $repository->expects($this->once())->method('updateDomainUser')->with($this->isInstanceOf(DomainUser::class))->willReturnCallback(static fn (DomainUser $user): DomainUser => $user);

        $response = (new PasswordChangeController())->changePassword(
            UserFactory::ormUser(id: 5, password: $storedHash),
            new PasswordChangeRequest('current-password', '12345678'),
            new PasswordChangeHandler($repository, $passwordHasher),
            new JsonResponder(),
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame([
            'success' => true,
            'data' => [],
            'message' => 'Password updated successfully',
        ], $this->decodeResponse($response->getContent()));
    }

    public function testChangeMyPasswordControllerRejectsUnauthenticatedUser(): void
    {
        $controller = new PasswordChangeController();

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('User is not authenticated.');

        $controller->changePassword(
            null,
            new PasswordChangeRequest('current-password', '12345678'),
            new PasswordChangeHandler(
                $this->createMock(DomainUserRepositoryInterface::class),
                $this->createMock(PasswordHasherInterface::class),
            ),
            new JsonResponder(),
        );
    }

    public function testShowMyProfileControllerReturnsCurrentUserData(): void
    {
        $response = (new CurrentUserController())->me(
            UserFactory::ormUser(
                id: 5,
                email: 'user@example.com',
                username: 'username',
                roles: ['ROLE_ADMIN'],
                gender: GenderEnum::FEMALE,
                birthDate: new \DateTimeImmutable('1990-05-20'),
                registeredAt: new \DateTimeImmutable('2024-01-02T03:04:05+00:00'),
            ),
            new JsonResponder(),
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame([
            'success' => true,
            'data' => [
                'username' => 'username',
                'email' => 'user@example.com',
                'gender' => 'female',
                'birthDate' => '1990-05-20',
                'roles' => ['ROLE_ADMIN', 'ROLE_USER'],
                'registeredAt' => '2024-01-02',
            ],
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
}
