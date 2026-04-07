<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Service;

use App\Infrastructure\Service\JsonResponder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

final class JsonResponderTest extends TestCase
{
    public function testSuccessBuildsPayloadWithoutMessageByDefault(): void
    {
        $response = (new JsonResponder())->success(data: ['id' => 1]);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame([
            'success' => true,
            'data' => ['id' => 1],
        ], $this->decodeJson($response->getContent()));
    }

    public function testSuccessBuildsPayloadWithMessageStatusAndHeaders(): void
    {
        $response = (new JsonResponder())->success(
            data: ['id' => 1],
            message: 'Completed.',
            status: Response::HTTP_ACCEPTED,
            headers: ['X-Test' => 'ok'],
        );

        self::assertSame(Response::HTTP_ACCEPTED, $response->getStatusCode());
        self::assertSame('ok', $response->headers->get('X-Test'));
        self::assertSame([
            'success' => true,
            'data' => ['id' => 1],
            'message' => 'Completed.',
        ], $this->decodeJson($response->getContent()));
    }

    public function testCreatedUsesCreatedStatus(): void
    {
        $response = (new JsonResponder())->created(data: ['id' => 2], message: 'Created.');

        self::assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        self::assertSame([
            'success' => true,
            'data' => ['id' => 2],
            'message' => 'Created.',
        ], $this->decodeJson($response->getContent()));
    }

    public function testErrorBuildsPayloadWithOptionalFields(): void
    {
        $response = (new JsonResponder())->error(
            code: 'validation_failed',
            message: 'Validation failed.',
            status: Response::HTTP_UNPROCESSABLE_ENTITY,
            fields: [['field' => 'email', 'message' => 'Required.']],
            headers: ['X-Trace' => '123'],
        );

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
        self::assertSame('123', $response->headers->get('X-Trace'));
        self::assertSame([
            'success' => false,
            'error' => [
                'code' => 'validation_failed',
                'message' => 'Validation failed.',
                'fields' => [['field' => 'email', 'message' => 'Required.']],
            ],
        ], $this->decodeJson($response->getContent()));
    }

    public function testNoContentReturnsEmpty204Response(): void
    {
        $response = (new JsonResponder())->noContent(headers: ['X-Trace' => '123']);

        self::assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        self::assertSame('123', $response->headers->get('X-Trace'));
        self::assertSame('', $response->getContent());
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJson(string|false $content): array
    {
        self::assertIsString($content);

        return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
    }
}
