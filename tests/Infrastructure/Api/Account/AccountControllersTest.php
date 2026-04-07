<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Api\Account;

use App\Application\Account\ChangeMyPassword\ChangeMyPasswordHandler;
use App\Application\Account\ChangeMySettings\ChangeMySettingsHandler;
use App\Application\Contract\PasswordHasherInterface;
use App\Domain\Entity\DomainUser;
use App\Domain\Repository\DomainUserRepositoryInterface;
use App\Infrastructure\Api\Account\ChangeMyPassword\ChangeMyPasswordController;
use App\Infrastructure\Api\Account\ChangeMyPassword\ChangeMyPasswordRequest;
use App\Infrastructure\Api\Account\ChangeMySettings\ChangeMySettingsController;
use App\Infrastructure\Api\Account\ChangeMySettings\ChangeMySettingsRequest;
use App\Infrastructure\Api\Account\ShowMyProfile\ShowMyProfileController;
use App\Infrastructure\Service\JsonResponder;
use App\Infrastructure\Service\MailerService;
use App\Tests\Support\UserFactory;
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
        $currentDomainUser = UserFactory::domainUser(id: 5, email: 'user@example.com', confirmedEmail: 'user@example.com', username: 'old_name');

        $repository
            ->expects($this->exactly(3))
            ->method('findUserBy')
            ->willReturnCallback(static function (array $criteria) use ($currentDomainUser): ?DomainUser {
                return match ($criteria) {
                    ['id' => 5], ['email' => 'user@example.com'], ['username' => 'new_name'] => $currentDomainUser,
                    default => null,
                };
            });

        $tokenGenerator->expects($this->never())->method('generateToken');
        $mailer->expects($this->never())->method('send');
        $repository->expects($this->once())->method('updateDomainUser')->with($this->isInstanceOf(DomainUser::class))->willReturnCallback(static fn (DomainUser $user): DomainUser => $user);

        $response = (new ChangeMySettingsController())->changeMySettings(
            UserFactory::ormUser(id: 5, email: 'user@example.com', username: 'old_name'),
            new ChangeMySettingsRequest('new_name', 'user@example.com'),
            new ChangeMySettingsHandler(
                $repository,
                $tokenGenerator,
                new MailerService($mailer, $this->createStub(LoggerInterface::class), 'no-reply@example.com', 'Kehat'),
            ),
            new JsonResponder(),
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame([
            'success' => true,
            'data' => [],
            'message' => 'Your settings updated successfully.',
        ], $this->decodeResponse($response->getContent()));
    }

    public function testChangeMySettingsControllerRejectsUserWithoutId(): void
    {
        $controller = new ChangeMySettingsController();

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('User not have id.');

        $controller->changeMySettings(
            UserFactory::ormUser(id: null),
            new ChangeMySettingsRequest('username', 'user@example.com'),
            new ChangeMySettingsHandler(
                $this->createMock(DomainUserRepositoryInterface::class),
                $this->createMock(TokenGeneratorInterface::class),
                new MailerService($this->createMock(MailerInterface::class), $this->createStub(LoggerInterface::class), 'no-reply@example.com', 'Kehat'),
            ),
            new JsonResponder(),
        );
    }

    public function testChangeMyPasswordControllerReturnsSuccessPayload(): void
    {
        $repository = $this->createMock(DomainUserRepositoryInterface::class);
        $passwordHasher = $this->createMock(PasswordHasherInterface::class);
        $domainUser = UserFactory::domainUser(id: 5, password: 'stored-hash');

        $repository->expects($this->once())->method('findUserBy')->with(['id' => 5])->willReturn($domainUser);
        $passwordHasher->expects($this->once())->method('verify')->with('stored-hash', 'current-password')->willReturn(true);
        $passwordHasher->expects($this->once())->method('hash')->with('12345678')->willReturn('new-password');
        $repository->expects($this->once())->method('updateDomainUser')->with($this->isInstanceOf(DomainUser::class))->willReturnCallback(static fn (DomainUser $user): DomainUser => $user);

        $response = (new ChangeMyPasswordController())->changePassword(
            UserFactory::ormUser(id: 5, password: 'stored-hash'),
            new ChangeMyPasswordRequest('current-password', '12345678'),
            new ChangeMyPasswordHandler($repository, $passwordHasher),
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
        $controller = new ChangeMyPasswordController();

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('User is not authenticated.');

        $controller->changePassword(
            null,
            new ChangeMyPasswordRequest('current-password', '12345678'),
            new ChangeMyPasswordHandler(
                $this->createMock(DomainUserRepositoryInterface::class),
                $this->createMock(PasswordHasherInterface::class),
            ),
            new JsonResponder(),
        );
    }

    public function testShowMyProfileControllerReturnsCurrentUserData(): void
    {
        $response = (new ShowMyProfileController())->me(
            UserFactory::ormUser(
                id: 5,
                email: 'user@example.com',
                username: 'username',
                roles: ['ROLE_ADMIN'],
                registeredAt: new \DateTimeImmutable('2024-01-02T03:04:05+00:00'),
            ),
            new JsonResponder(),
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame([
            'success' => true,
            'data' => [
                'id' => 5,
                'email' => 'user@example.com',
                'username' => 'username',
                'roles' => ['ROLE_ADMIN', 'ROLE_USER'],
                'registeredAt' => '2024-01-02T03:04:05+00:00',
            ],
        ], $this->decodeResponse($response->getContent()));
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeResponse(string|false $content): array
    {
        self::assertIsString($content);

        return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
    }
}
