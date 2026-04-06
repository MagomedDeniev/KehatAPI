<?php

declare(strict_types=1);

namespace App\Infrastructure\Service;

use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

final class MailerService
{
    private Address $from;

    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly LoggerInterface $logger,
        string $fromAddress,
        string $fromName,
    ) {
        $this->from = new Address($fromAddress, $fromName);
    }

    /**
     * @param array<string, mixed> $context
     *
     * @throws TransportExceptionInterface
     */
    public function sendTemplate(
        string|Address $to,
        string $subject,
        string $template,
        array $context = [],
    ): void {
        $email = (new TemplatedEmail())
            ->from($this->from)
            ->to($to)
            ->subject($subject)
            ->htmlTemplate($template)
            ->context($context);

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            $this->logger->error('Failed to send email.', [
                'to' => is_string($to) ? $to : $to->getAddress(),
                'subject' => $subject,
                'template' => $template,
                'exception' => $e,
            ]);

            throw $e;
        }
    }
}
