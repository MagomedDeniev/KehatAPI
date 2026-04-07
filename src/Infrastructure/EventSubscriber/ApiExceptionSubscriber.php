<?php

declare(strict_types=1);

namespace App\Infrastructure\EventSubscriber;

use App\Infrastructure\Service\JsonResponder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;

final readonly class ApiExceptionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private JsonResponder $responder,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onException', -100],
        ];
    }

    public function onException(ExceptionEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $exception = $event->getThrowable();
        $path = $request->getPathInfo();

        if ('/api' !== $path && !str_starts_with($path, '/api/')) {
            return;
        }

        $validationException = $this->extractValidationException($exception);

        if ($validationException instanceof ValidationFailedException) {
            $fields = array_values(array_map(
                static fn (ConstraintViolationInterface $violation): array => [
                    'field' => $violation->getPropertyPath(),
                    'message' => (string) $violation->getMessage(),
                ],
                iterator_to_array($validationException->getViolations(), false)
            ));

            $event->setResponse($this->responder->error(
                code: 'validation_failed',
                message: 'Validation failed.',
                status: 422,
                fields: $fields,
            ));

            return;
        }

        if ($exception instanceof TransportExceptionInterface) {
            $event->setResponse($this->responder->error(
                code: 'mail_delivery_failed',
                message: 'Email could not be sent right now.',
                status: 503,
            ));

            return;
        }

        if ($exception instanceof AccessDeniedException || $exception instanceof AccessDeniedHttpException) {
            $event->setResponse($this->responder->error(
                code: 'access_denied',
                message: 'Access denied.',
                status: 403,
            ));

            return;
        }

        if ($exception instanceof HttpExceptionInterface) {
            $status = $exception->getStatusCode();

            $message = match ($status) {
                400, 415, 422 => $exception->getMessage() ?: (Response::$statusTexts[$status] ?? 'HTTP error.'),
                default => Response::$statusTexts[$status] ?? 'HTTP error.',
            };

            /** @var array<string, string|string[]> $headers */
            $headers = $exception->getHeaders();

            $event->setResponse($this->responder->error(
                code: 'http_error',
                message: $message,
                status: $status,
                headers: $headers
            ));

            return;
        }

        $event->setResponse($this->responder->error(
            code: 'internal_error',
            message: 'Internal server error.',
            status: 500,
        ));
    }

    private function extractValidationException(\Throwable $exception): ?ValidationFailedException
    {
        if ($exception instanceof ValidationFailedException) {
            return $exception;
        }

        $previous = $exception->getPrevious();

        while ($previous instanceof \Throwable) {
            if ($previous instanceof ValidationFailedException) {
                return $previous;
            }

            $previous = $previous->getPrevious();
        }

        return null;
    }
}
