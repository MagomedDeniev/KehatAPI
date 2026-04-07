<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Service;

use App\Infrastructure\Service\MailerService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

final class MailerServiceTest extends TestCase
{
    public function testItBuildsAndSendsTemplatedEmail(): void
    {
        $mailer = $this->createMock(MailerInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        $service = new MailerService($mailer, $logger, 'no-reply@example.com', 'Kehat');

        $mailer
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(static function (TemplatedEmail $email): bool {
                self::assertSame('no-reply@example.com', $email->getFrom()[0]->getAddress());
                self::assertSame('Kehat', $email->getFrom()[0]->getName());
                self::assertSame('user@example.com', $email->getTo()[0]->getAddress());
                self::assertSame('Welcome', $email->getSubject());
                self::assertSame('mailer/template.html.twig', $email->getHtmlTemplate());
                self::assertSame(['userId' => 7], $email->getContext());

                return true;
            }));

        $logger->expects($this->never())->method('error');

        $service->sendTemplate('user@example.com', 'Welcome', 'mailer/template.html.twig', ['userId' => 7]);
    }

    public function testItLogsAndRethrowsTransportException(): void
    {
        $mailer = $this->createMock(MailerInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        $service = new MailerService($mailer, $logger, 'no-reply@example.com', 'Kehat');
        $exception = new TransportException('Transport failed.');

        $mailer->expects($this->once())->method('send')->willThrowException($exception);

        $logger
            ->expects($this->once())
            ->method('error')
            ->with(
                'Failed to send email.',
                $this->callback(static function (array $context) use ($exception): bool {
                    self::assertSame('user@example.com', $context['to']);
                    self::assertSame('Welcome', $context['subject']);
                    self::assertSame('mailer/template.html.twig', $context['template']);
                    self::assertSame($exception, $context['exception']);

                    return true;
                })
            );

        $this->expectExceptionObject($exception);

        $service->sendTemplate(new Address('user@example.com'), 'Welcome', 'mailer/template.html.twig');
    }
}
