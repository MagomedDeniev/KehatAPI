<?php

namespace App\Infrastructure\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;

final class ApiExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onException',
        ];
    }

    public function onException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        $validationException = $this->extractValidationException($exception);

        if ($validationException instanceof ValidationFailedException) {
            $event->setResponse(new JsonResponse([
                'success' => false,
                'error' => [
                    'code' => 'validation_failed',
                    'message' => 'Validation failed.',
                    'fields' => array_map(
                        static fn (ConstraintViolationInterface $violation): array => [
                            'field' => $violation->getPropertyPath(),
                            'message' => $violation->getMessage(),
                        ],
                        iterator_to_array($validationException->getViolations())
                    ),
                ],
            ], 422));

            return;
        }

        if ($exception instanceof TransportExceptionInterface) {
            $event->setResponse(new JsonResponse([
                'success' => false,
                'error' => [
                    'code' => 'mail_delivery_failed',
                    'message' => 'Email could not be sent right now.',
                ],
            ], 503));

            return;
        }
    }

    private function extractValidationException(\Throwable $exception): ?ValidationFailedException
    {
        if ($exception instanceof ValidationFailedException) {
            return $exception;
        }

        $previous = $exception->getPrevious();

        while ($previous !== null) {
            if ($previous instanceof ValidationFailedException) {
                return $previous;
            }

            $previous = $previous->getPrevious();
        }

        return null;
    }
}
