<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\EventSubscriber;

use App\Infrastructure\EventSubscriber\ApiExceptionSubscriber;
use App\Infrastructure\Service\JsonResponder;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\ValidationFailedException;

final class ApiExceptionSubscriberTest extends TestCase
{
    public function testItIgnoresSubRequests(): void
    {
        $event = $this->createEvent(new \RuntimeException('Boom'), '/api/test', HttpKernelInterface::SUB_REQUEST);

        (new ApiExceptionSubscriber(new JsonResponder()))->onException($event);

        self::assertNull($event->getResponse());
    }

    public function testItIgnoresNonApiRoutes(): void
    {
        $event = $this->createEvent(new \RuntimeException('Boom'), '/health');

        (new ApiExceptionSubscriber(new JsonResponder()))->onException($event);

        self::assertNull($event->getResponse());
    }

    public function testItConvertsValidationFailedException(): void
    {
        $violations = new ConstraintViolationList([
            new ConstraintViolation('Email is invalid.', null, [], null, 'email', 'bad@example'),
            new ConstraintViolation('Password is too short.', null, [], null, 'password', '123'),
        ]);

        $event = $this->createEvent(new ValidationFailedException([], $violations));

        (new ApiExceptionSubscriber(new JsonResponder()))->onException($event);

        self::assertSame(422, $event->getResponse()?->getStatusCode());
        self::assertSame([
            'success' => false,
            'error' => [
                'code' => 'validation_failed',
                'message' => 'Validation failed.',
                'fields' => [
                    ['field' => 'email', 'message' => 'Email is invalid.'],
                    ['field' => 'password', 'message' => 'Password is too short.'],
                ],
            ],
        ], $this->decodeResponse($event));
    }

    public function testItExtractsWrappedValidationFailedException(): void
    {
        $violations = new ConstraintViolationList([
            new ConstraintViolation('Token is invalid.', null, [], null, 'token', 'bad-token'),
        ]);
        $wrapped = new \RuntimeException('Outer', 0, new ValidationFailedException([], $violations));
        $event = $this->createEvent($wrapped);

        (new ApiExceptionSubscriber(new JsonResponder()))->onException($event);

        self::assertSame(422, $event->getResponse()?->getStatusCode());
        self::assertSame('validation_failed', $this->decodeResponse($event)['error']['code']);
    }

    public function testItConvertsMailerTransportExceptions(): void
    {
        $event = $this->createEvent(new TransportException('Transport failed.'));

        (new ApiExceptionSubscriber(new JsonResponder()))->onException($event);

        self::assertSame(503, $event->getResponse()?->getStatusCode());
        self::assertSame('mail_delivery_failed', $this->decodeResponse($event)['error']['code']);
    }

    #[DataProvider('accessDeniedProvider')]
    public function testItConvertsAccessDeniedExceptions(\Throwable $exception): void
    {
        $event = $this->createEvent($exception);

        (new ApiExceptionSubscriber(new JsonResponder()))->onException($event);

        self::assertSame(403, $event->getResponse()?->getStatusCode());
        self::assertSame([
            'success' => false,
            'error' => [
                'code' => 'access_denied',
                'message' => 'Access denied.',
            ],
        ], $this->decodeResponse($event));
    }

    /**
     * @return iterable<string, array{0: \Throwable}>
     */
    public static function accessDeniedProvider(): iterable
    {
        yield 'security access denied' => [new AccessDeniedException('Nope')];
        yield 'http access denied' => [new AccessDeniedHttpException('Nope')];
    }

    public function testItUsesHttpExceptionMessageForWhitelistedStatuses(): void
    {
        $event = $this->createEvent(new HttpException(400, 'Malformed JSON.', null, ['X-Test' => '1']));

        (new ApiExceptionSubscriber(new JsonResponder()))->onException($event);

        self::assertSame(400, $event->getResponse()?->getStatusCode());
        self::assertSame('1', $event->getResponse()?->headers->get('X-Test'));
        self::assertSame([
            'success' => false,
            'error' => [
                'code' => 'http_error',
                'message' => 'Malformed JSON.',
            ],
        ], $this->decodeResponse($event));
    }

    public function testItUsesGenericStatusTextForOtherHttpExceptions(): void
    {
        $event = $this->createEvent(new HttpException(404, 'Hidden detail.'));

        (new ApiExceptionSubscriber(new JsonResponder()))->onException($event);

        self::assertSame(404, $event->getResponse()?->getStatusCode());
        self::assertSame('Not Found', $this->decodeResponse($event)['error']['message']);
    }

    public function testItConvertsUniqueConstraintViolationExceptions(): void
    {
        $driverException = new class('Duplicate entry', 0) extends \Exception implements \Doctrine\DBAL\Driver\Exception {
            public function getSQLState(): ?string
            {
                return null;
            }
        };

        $event = $this->createEvent(new UniqueConstraintViolationException($driverException, null));

        (new ApiExceptionSubscriber(new JsonResponder()))->onException($event);

        self::assertSame(409, $event->getResponse()?->getStatusCode());
        self::assertSame('unique_constraint_violation', $this->decodeResponse($event)['error']['code']);
    }

    public function testItConvertsInvalidArgumentExceptions(): void
    {
        $event = $this->createEvent(new \InvalidArgumentException('Invalid email.'));

        (new ApiExceptionSubscriber(new JsonResponder()))->onException($event);

        self::assertSame(422, $event->getResponse()?->getStatusCode());
        self::assertSame('invalid_argument', $this->decodeResponse($event)['error']['code']);
        self::assertSame('Invalid email.', $this->decodeResponse($event)['error']['message']);
    }

    public function testItConvertsDomainExceptions(): void
    {
        $event = $this->createEvent(new \DomainException('Business rule failed.'));

        (new ApiExceptionSubscriber(new JsonResponder()))->onException($event);

        self::assertSame(422, $event->getResponse()?->getStatusCode());
        self::assertSame('domain_error', $this->decodeResponse($event)['error']['code']);
        self::assertSame('Business rule failed.', $this->decodeResponse($event)['error']['message']);
    }

    public function testItConvertsUnknownExceptionsToInternalError(): void
    {
        $event = $this->createEvent(new \RuntimeException('Boom'));

        (new ApiExceptionSubscriber(new JsonResponder()))->onException($event);

        self::assertSame(500, $event->getResponse()?->getStatusCode());
        self::assertSame([
            'success' => false,
            'error' => [
                'code' => 'internal_error',
                'message' => 'Internal server error.',
            ],
        ], $this->decodeResponse($event));
    }

    private function createEvent(
        \Throwable $exception,
        string $path = '/api/test',
        int $requestType = HttpKernelInterface::MAIN_REQUEST,
    ): ExceptionEvent {
        return new ExceptionEvent(
            $this->createStub(HttpKernelInterface::class),
            Request::create($path),
            $requestType,
            $exception,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeResponse(ExceptionEvent $event): array
    {
        $content = $event->getResponse()?->getContent();
        self::assertIsString($content);

        return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
    }
}
